<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\CommentAttachment;
use App\Models\Resource;
use App\Models\RiskManagement;
use App\Models\TargetObjective;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class CommentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'commentable_type' => 'required|string|in:target_objective,resource,risk_management',
            'commentable_id' => 'required|integer',
            'body' => 'required|string|max:1000',
            'attachment_id' => 'nullable|integer|exists:comment_attachments,id',
            'attachment_ids' => 'nullable|array|max:5',
            'attachment_ids.*' => 'integer|exists:comment_attachments,id',
        ]);

        $modelClass = match ($validated['commentable_type']) {
            'target_objective' => TargetObjective::class,
            'resource' => Resource::class,
            'risk_management' => RiskManagement::class,
        };

        $item = $modelClass::findOrFail($validated['commentable_id']);
        $plan = $item->planRecord;

        $user = Auth::user();
        if (! $user->hasAnyRole(['Marshall', 'Admin'])) {
            if ($plan && $plan->executor_id !== $user->id) {
                abort(403, 'Unauthorized action.');
            }
            if ($plan && $plan->status !== 'Open') {
                abort(403, 'Executors cannot input remarks when the plan record is not Open.');
            }
        }

        $comment = $item->comments()->create([
            'user_id' => Auth::id(),
            'body' => $validated['body'],
        ]);

        $attachmentIds = $validated['attachment_ids'] ?? [];
        if (! empty($validated['attachment_id'])) {
            $attachmentIds[] = $validated['attachment_id'];
        }
        $attachmentIds = array_unique(array_slice($attachmentIds, 0, 5));

        if (! empty($attachmentIds)) {
            CommentAttachment::whereIn('id', $attachmentIds)
                ->whereNull('comment_id')
                ->update(['comment_id' => $comment->id]);
        }

        if ($request->expectsJson() || $request->ajax()) {
            $comment->load(['user', 'attachments']);

            return response()->json([
                'success' => true,
                'message' => 'Remark / Comment added successfully.',
                'comment' => [
                    'id' => $comment->id,
                    'body' => $comment->body,
                    'created_at_formatted' => $comment->created_at->format('M d, Y h:i A'),
                    'created_at_human' => $comment->created_at->diffForHumans(),
                    'user' => [
                        'id' => $comment->user->id,
                        'name' => $comment->user->name,
                        'initial' => strtoupper(substr($comment->user->name, 0, 1)),
                        'is_marshall' => $comment->user->hasRole('Marshall'),
                        'is_executor' => $comment->user->hasRole('Executor'),
                        'is_admin' => $comment->user->hasRole('Admin'),
                    ],
                    'can_delete' => Auth::id() === $comment->user_id || Auth::user()->hasAnyRole(['Marshall', 'Admin']),
                    'delete_url' => route('comments.destroy', $comment),
                    'attachments' => $comment->attachments->map(fn ($att) => [
                        'id' => $att->id,
                        'original_name' => $att->original_name,
                        'file_size' => $att->formatted_size,
                        'is_image' => $att->is_image,
                        'url' => $att->url,
                        'icon_class' => $att->icon_class,
                        'download_url' => route('comments.attachments.download', $att),
                    ])->values(),
                ],
                'comments_count' => $item->comments()->count(),
                'commentable_type' => $validated['commentable_type'],
                'commentable_id' => (int) $validated['commentable_id'],
            ]);
        }

        return redirect()->route('plans.show', $item->plan_record_id)->with('success', 'Remark / Comment added successfully.');
    }

    public function destroy(Comment $comment, Request $request)
    {
        // Allow the comment author or a Marshall / Admin to delete a comment
        if (Auth::id() !== $comment->user_id && ! Auth::user()->hasAnyRole(['Marshall', 'Admin'])) {
            abort(403, 'Unauthorized action.');
        }

        $plan = $comment->commentable?->planRecord;
        if (! Auth::user()->hasAnyRole(['Marshall', 'Admin']) && $plan && $plan->status !== 'Open') {
            abort(403, 'Executors cannot delete remarks when the plan record is not Open.');
        }

        $planId = $comment->commentable->plan_record_id ?? null;

        // Clean up any uploaded attachment files
        foreach ($comment->attachments as $attachment) {
            $path = storage_path('app/public/'.$attachment->file_path);
            if (File::exists($path)) {
                File::delete($path);
            }
        }

        $comment->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Comment deleted successfully.',
            ]);
        }

        return redirect()->back()->with('success', 'Comment deleted successfully.');
    }
}
