<?php

namespace App\Http\Controllers;

use App\Models\CommentAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CommentUploadController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Store an individual chunk into the temporary staging folder.
     */
    public function uploadChunk(Request $request)
    {
        $request->validate([
            'upload_id' => 'required|string|regex:/^[a-zA-Z0-9_\-]+$/',
            'chunk_index' => 'required|integer|min:0',
            'total_chunks' => 'required|integer|min:1',
            'file' => 'required|file|max:5120',
        ]);

        $uploadId = $request->input('upload_id');
        $chunkIndex = (int) $request->input('chunk_index');
        $stagingDir = storage_path('app/staging/'.$uploadId);

        File::ensureDirectoryExists($stagingDir);

        $request->file('file')->move($stagingDir, 'chunk_'.$chunkIndex);

        return response()->json([
            'success' => true,
            'upload_id' => $uploadId,
            'chunk_index' => $chunkIndex,
        ]);
    }

    /**
     * Assemble all staged chunks and transfer the complete file to permanent storage.
     */
    public function assemble(Request $request)
    {
        $request->validate([
            'upload_id' => 'required|string|regex:/^[a-zA-Z0-9_\-]+$/',
            'filename' => 'required|string|max:255',
            'total_chunks' => 'required|integer|min:1',
        ]);

        $uploadId = $request->input('upload_id');
        $filename = $request->input('filename');
        $totalChunks = (int) $request->input('total_chunks');

        $stagingDir = storage_path('app/staging/'.$uploadId);

        if (! File::isDirectory($stagingDir)) {
            return response()->json([
                'success' => false,
                'message' => 'Staging directory not found for this upload.',
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

        // Sanitize filename
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $rawBasename = pathinfo($filename, PATHINFO_FILENAME);
        $sanitizedBase = Str::slug($rawBasename) ?: 'file';
        $safeFilename = $sanitizedBase.($extension ? '.'.strtolower($extension) : '');

        // Assemble chunks in staging folder
        $stagedMergedFile = $stagingDir.DIRECTORY_SEPARATOR.'assembled_'.$safeFilename;
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

        // Transfer assembled file from staging folder to permanent folder
        $permanentDir = storage_path('app/public/comments/attachments');
        File::ensureDirectoryExists($permanentDir);

        $permanentName = Str::uuid()->toString().'_'.$safeFilename;
        $permanentFullPath = $permanentDir.DIRECTORY_SEPARATOR.$permanentName;

        File::move($stagedMergedFile, $permanentFullPath);

        // Clean up temporary staging directory
        File::deleteDirectory($stagingDir);

        $fileSize = file_exists($permanentFullPath) ? filesize($permanentFullPath) : 0;

        if ($fileSize > 5 * 1024 * 1024) {
            File::delete($permanentFullPath);

            return response()->json([
                'success' => false,
                'message' => 'The file "'.$filename.'" exceeds the maximum allowed size of 5MB.',
            ], 422);
        }

        $mimeType = file_exists($permanentFullPath) ? (mime_content_type($permanentFullPath) ?: 'application/octet-stream') : 'application/octet-stream';

        $attachment = CommentAttachment::create([
            'comment_id' => null,
            'upload_id' => $uploadId,
            'original_name' => $filename,
            'file_path' => 'comments/attachments/'.$permanentName,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
        ]);

        return response()->json([
            'success' => true,
            'attachment_id' => $attachment->id,
            'upload_id' => $attachment->upload_id,
            'original_name' => $attachment->original_name,
            'file_size' => $attachment->formatted_size,
            'mime_type' => $attachment->mime_type,
            'is_image' => $attachment->is_image,
            'url' => $attachment->url,
        ]);
    }

    /**
     * Download an attachment.
     */
    public function download(CommentAttachment $attachment)
    {
        $fullPath = storage_path('app/public/'.$attachment->file_path);

        if (! File::exists($fullPath)) {
            abort(404, 'Attachment file not found on server.');
        }

        return response()->download($fullPath, $attachment->original_name);
    }
}
