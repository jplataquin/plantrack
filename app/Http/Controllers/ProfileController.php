<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Assemble staged chunks for a cropped profile picture and update user's profile_picture.
     */
    public function updateAvatar(Request $request)
    {
        $validated = $request->validate([
            'upload_id' => 'required|string|regex:/^[a-zA-Z0-9_\-]+$/',
            'filename' => 'nullable|string|max:255',
            'total_chunks' => 'required|integer|min:1',
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        $authUser = Auth::user();
        $targetUser = $authUser;

        // If target user_id is provided and differs from authenticated user, only Marshall or Admin can update
        if (! empty($validated['user_id']) && (int) $validated['user_id'] !== $authUser->id) {
            if (! $authUser->hasAnyRole(['Marshall', 'Admin'])) {
                abort(403, 'Unauthorized action.');
            }
            $targetUser = User::findOrFail($validated['user_id']);
        }

        $uploadId = $validated['upload_id'];
        $totalChunks = (int) $validated['total_chunks'];
        $stagingDir = storage_path('app/staging/'.$uploadId);

        if (! File::isDirectory($stagingDir)) {
            return response()->json([
                'success' => false,
                'message' => 'Staging directory not found for this avatar upload.',
            ], 404);
        }

        // Verify that all chunks are present in the staging folder
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkFile = $stagingDir.DIRECTORY_SEPARATOR.'chunk_'.$i;
            if (! File::exists($chunkFile)) {
                return response()->json([
                    'success' => false,
                    'message' => "Missing chunk {$i} of {$totalChunks} in staging folder.",
                ], 422);
            }
        }

        // Assemble chunks in staging folder
        $stagedMergedFile = $stagingDir.DIRECTORY_SEPARATOR.'assembled_avatar.tmp';
        $out = fopen($stagedMergedFile, 'wb');

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = $stagingDir.DIRECTORY_SEPARATOR.'chunk_'.$i;
            $in = fopen($chunkPath, 'rb');
            while (! feof($in)) {
                fwrite($out, fread($in, 8192));
            }
            fclose($in);
            @unlink($chunkPath);
        }
        fclose($out);

        // Check file size (5MB max)
        $fileSize = file_exists($stagedMergedFile) ? filesize($stagedMergedFile) : 0;
        if ($fileSize > 5 * 1024 * 1024) {
            File::deleteDirectory($stagingDir);

            return response()->json([
                'success' => false,
                'message' => 'Profile picture exceeds the maximum allowed size of 5MB.',
            ], 422);
        }

        // Verify image type
        $mimeType = mime_content_type($stagedMergedFile);
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (! in_array($mimeType, $allowedMimes)) {
            File::deleteDirectory($stagingDir);

            return response()->json([
                'success' => false,
                'message' => 'Uploaded file is not a valid image (JPEG, PNG, or WEBP required).',
            ], 422);
        }

        $extension = match ($mimeType) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'jpg',
        };

        // Move to permanent avatars storage
        $permanentDir = storage_path('app/public/avatars');
        File::ensureDirectoryExists($permanentDir);

        $permanentFilename = 'avatars/avatar_'.$targetUser->id.'_'.Str::random(12).'.'.$extension;
        $permanentFullPath = storage_path('app/public/'.$permanentFilename);

        File::move($stagedMergedFile, $permanentFullPath);

        // Clean up temporary staging directory
        File::deleteDirectory($stagingDir);

        // Remove old avatar file if present
        if ($targetUser->profile_picture && File::exists(storage_path('app/public/'.$targetUser->profile_picture))) {
            File::delete(storage_path('app/public/'.$targetUser->profile_picture));
        }

        // Update target user
        $targetUser->update([
            'profile_picture' => $permanentFilename,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profile picture updated successfully.',
            'user_id' => $targetUser->id,
            'profile_picture' => $targetUser->profile_picture,
            'profile_picture_url' => asset('storage/'.$targetUser->profile_picture),
        ]);
    }

    /**
     * Delete user profile picture.
     */
    public function deleteAvatar(Request $request)
    {
        $authUser = Auth::user();
        $targetUser = $authUser;

        if ($request->filled('user_id') && (int) $request->input('user_id') !== $authUser->id) {
            if (! $authUser->hasAnyRole(['Marshall', 'Admin'])) {
                abort(403, 'Unauthorized action.');
            }
            $targetUser = User::findOrFail($request->input('user_id'));
        }

        if ($targetUser->profile_picture) {
            $path = storage_path('app/public/'.$targetUser->profile_picture);
            if (File::exists($path)) {
                File::delete($path);
            }
            $targetUser->update(['profile_picture' => null]);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Profile picture removed successfully.',
                'user_id' => $targetUser->id,
            ]);
        }

        return redirect()->back()->with('success', 'Profile picture removed successfully.');
    }

    /**
     * Show the user profile page.
     */
    public function show(?User $user = null)
    {
        $authUser = Auth::user();
        $targetUser = ($user && $user->exists) ? $user : $authUser;
        $isOwnProfile = ($targetUser->id === $authUser->id);
        $user = $targetUser;
        $isAdmin = $user->hasRole('Admin');
        $isMarshall = $user->hasRole('Marshall');
        $isExecutor = $user->hasRole('Executor');

        $assignedPlans = $user->planRecords()
            ->with(['project', 'targetObjectives', 'resources', 'riskManagements', 'budgets'])
            ->latest()
            ->get();

        $assignedPlansCount = $assignedPlans->count();
        $openPlansCount = $assignedPlans->where('status', 'Open')->count();
        $reviewPlansCount = $assignedPlans->where('status', 'Review')->count();
        $closedPlans = $assignedPlans->where('status', 'Close');
        $closedPlansCount = $closedPlans->count();
        $voidPlansCount = $assignedPlans->where('status', 'Void')->count();

        // Calculate average score considering only Plan records that have the status "Close"
        $averageScore = $closedPlansCount > 0
            ? round($closedPlans->avg(fn ($plan) => $plan->evaluation_score), 2)
            : 0.0;

        // Calculate Component Distribution for Closed Plans
        $componentStats = [
            'targets' => ['hit' => 0, 'missed' => 0, 'void' => 0, 'total' => 0],
            'resources' => ['hit' => 0, 'missed' => 0, 'void' => 0, 'total' => 0],
            'risks' => ['hit' => 0, 'missed' => 0, 'void' => 0, 'total' => 0],
            'budgets' => ['hit' => 0, 'missed' => 0, 'void' => 0, 'total' => 0],
        ];

        foreach ($closedPlans as $plan) {
            foreach (['Hit' => 'hit', 'Missed' => 'missed', 'Void' => 'void'] as $status => $key) {
                $componentStats['targets'][$key] += $plan->targetObjectives->where('status', $status)->count();
                $componentStats['resources'][$key] += $plan->resources->where('status', $status)->count();
                $componentStats['risks'][$key] += $plan->riskManagements->where('status', $status)->count();
                $componentStats['budgets'][$key] += $plan->budgets->where('status', $status)->count();
            }
        }

        foreach ($componentStats as $type => &$stats) {
            $stats['total'] = $stats['hit'] + $stats['missed'] + $stats['void'];
            $stats['hit_pct'] = $stats['total'] > 0 ? round(($stats['hit'] / $stats['total']) * 100, 1) : 0;
            $stats['missed_pct'] = $stats['total'] > 0 ? round(($stats['missed'] / $stats['total']) * 100, 1) : 0;
            $stats['void_pct'] = $stats['total'] > 0 ? round(($stats['void'] / $stats['total']) * 100, 1) : 0;
        }

        return view('profile.show', compact(
            'user',
            'isOwnProfile',
            'isAdmin',
            'isMarshall',
            'isExecutor',
            'assignedPlans',
            'assignedPlansCount',
            'openPlansCount',
            'reviewPlansCount',
            'closedPlansCount',
            'voidPlansCount',
            'averageScore',
            'componentStats'
        ));
    }

    /**
     * Update user profile information (Name, Email).
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
        ]);

        $user->update($validated);

        return redirect()->route('profile.show')->with('success', 'Profile information updated successfully.');
    }

    /**
     * Update user password.
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('profile.show')->with('success', 'Password updated successfully.');
    }
}
