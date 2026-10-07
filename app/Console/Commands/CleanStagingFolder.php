<?php

namespace App\Console\Commands;

use App\Models\CommentAttachment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CleanStagingFolder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'staging:clean {--hours=24 : Age in hours of stale staging files to prune}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up stale temporary upload chunks and directories from the staging folder.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        if ($hours < 0) {
            $hours = 0;
        }

        $cutoff = now()->subHours($hours)->getTimestamp();
        $stagingPath = storage_path('app/staging');

        $deletedDirs = 0;
        $deletedFiles = 0;
        $deletedOrphans = 0;

        if (File::isDirectory($stagingPath)) {
            // 1. Clean up stale directories in staging
            $directories = File::directories($stagingPath);
            foreach ($directories as $dir) {
                $dirModifiedTime = filemtime($dir);

                // Also check if any file inside has a newer modification time
                $files = File::allFiles($dir);
                $latestTime = $dirModifiedTime;
                foreach ($files as $file) {
                    $latestTime = max($latestTime, $file->getMTime());
                }

                if ($latestTime < $cutoff) {
                    File::deleteDirectory($dir);
                    $deletedDirs++;
                }
            }

            // 2. Clean up any loose files directly under staging directory
            $looseFiles = File::files($stagingPath);
            foreach ($looseFiles as $file) {
                if ($file->getMTime() < $cutoff) {
                    File::delete($file->getRealPath());
                    $deletedFiles++;
                }
            }
        }

        // 3. Clean up orphaned CommentAttachment records (never attached to a comment after $hours)
        $staleOrphanAttachments = CommentAttachment::whereNull('comment_id')
            ->where('created_at', '<', now()->subHours($hours))
            ->get();

        foreach ($staleOrphanAttachments as $attachment) {
            $path = storage_path('app/public/'.$attachment->file_path);
            if (File::exists($path)) {
                File::delete($path);
            }
            $attachment->delete();
            $deletedOrphans++;
        }

        $this->info("Staging cleanup completed. Pruned {$deletedDirs} stale directories, {$deletedFiles} loose files, and {$deletedOrphans} orphaned attachments.");

        return Command::SUCCESS;
    }
}
