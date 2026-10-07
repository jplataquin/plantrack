<?php

namespace Tests\Feature;

use App\Models\CommentAttachment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class StagingCleanupTest extends TestCase
{
    use RefreshDatabase;

    private string $stagingBase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stagingBase = storage_path('app/staging');
        File::ensureDirectoryExists($this->stagingBase);
    }

    protected function tearDown(): void
    {
        // Clean up test directories
        if (File::isDirectory($this->stagingBase)) {
            File::deleteDirectory($this->stagingBase);
        }

        parent::tearDown();
    }

    public function test_staging_clean_command_prunes_stale_directories_and_files(): void
    {
        // 1. Create a stale staging directory (> 24 hours old)
        $staleDir = $this->stagingBase.'/stale_upload_dir';
        File::ensureDirectoryExists($staleDir);
        file_put_contents($staleDir.'/chunk_0', 'stale chunk content');
        touch($staleDir.'/chunk_0', time() - (25 * 3600));
        touch($staleDir, time() - (25 * 3600));

        // 2. Create a recent staging directory (< 24 hours old)
        $recentDir = $this->stagingBase.'/recent_upload_dir';
        File::ensureDirectoryExists($recentDir);
        file_put_contents($recentDir.'/chunk_0', 'recent chunk content');
        touch($recentDir.'/chunk_0', time() - (2 * 3600));
        touch($recentDir, time() - (2 * 3600));

        // 3. Create a stale loose file
        $staleLooseFile = $this->stagingBase.'/stale_file.tmp';
        file_put_contents($staleLooseFile, 'stale loose file');
        touch($staleLooseFile, time() - (30 * 3600));

        // Run the command
        $exitCode = Artisan::call('staging:clean', ['--hours' => 24]);
        $this->assertEquals(0, $exitCode);

        // Assert stale directory and loose file were deleted
        $this->assertDirectoryDoesNotExist($staleDir);
        $this->assertFileDoesNotExist($staleLooseFile);

        // Assert recent directory is preserved
        $this->assertDirectoryExists($recentDir);
        $this->assertFileExists($recentDir.'/chunk_0');
    }

    public function test_staging_clean_command_prunes_orphaned_comment_attachments(): void
    {
        // Create an orphaned CommentAttachment (comment_id is null) older than 24 hours
        $permanentDir = storage_path('app/public/comments/attachments');
        File::ensureDirectoryExists($permanentDir);
        $dummyFilePath = 'comments/attachments/stale_orphan_test.txt';
        file_put_contents(storage_path('app/public/'.$dummyFilePath), 'orphan payload');

        $orphan = CommentAttachment::create([
            'comment_id' => null,
            'upload_id' => 'orphan_up_1',
            'original_name' => 'stale_orphan.txt',
            'file_path' => $dummyFilePath,
            'mime_type' => 'text/plain',
            'file_size' => 14,
        ]);

        // Manually backdate created_at
        $orphan->created_at = now()->subHours(26);
        $orphan->saveQuietly();

        // Create a recent unattached CommentAttachment (< 24 hours)
        $recentFilePath = 'comments/attachments/recent_orphan_test.txt';
        file_put_contents(storage_path('app/public/'.$recentFilePath), 'recent payload');
        $recent = CommentAttachment::create([
            'comment_id' => null,
            'upload_id' => 'recent_up_1',
            'original_name' => 'recent_orphan.txt',
            'file_path' => $recentFilePath,
            'mime_type' => 'text/plain',
            'file_size' => 14,
        ]);

        // Run cleanup
        Artisan::call('staging:clean', ['--hours' => 24]);

        // The stale orphan record and file should be deleted
        $this->assertDatabaseMissing('comment_attachments', ['id' => $orphan->id]);
        $this->assertFileDoesNotExist(storage_path('app/public/'.$dummyFilePath));

        // The recent unattached record and file should be kept
        $this->assertDatabaseHas('comment_attachments', ['id' => $recent->id]);
        $this->assertFileExists(storage_path('app/public/'.$recentFilePath));

        // Clean up
        @unlink(storage_path('app/public/'.$recentFilePath));
    }

    public function test_staging_clean_handles_empty_staging_folder_gracefully(): void
    {
        $exitCode = Artisan::call('staging:clean');
        $this->assertEquals(0, $exitCode);
    }
}
