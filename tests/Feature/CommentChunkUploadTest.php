<?php

namespace Tests\Feature;

use App\Models\CommentAttachment;
use App\Models\PlanRecord;
use App\Models\TargetObjective;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CommentChunkUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $executor;

    private User $marshall;

    private PlanRecord $plan;

    private TargetObjective $target;

    protected function setUp(): void
    {
        parent::setUp();

        $executorRole = Role::firstOrCreate(['name' => 'Executor']);
        $marshallRole = Role::firstOrCreate(['name' => 'Marshall']);

        $this->executor = User::factory()->create([
            'name' => 'Test Executor',
            'email' => 'executor_upload@plantrack.test',
        ]);
        $this->executor->assignRole($executorRole);

        $this->marshall = User::factory()->create([
            'name' => 'Test Marshall',
            'email' => 'marshall_upload@plantrack.test',
        ]);
        $this->marshall->assignRole($marshallRole);

        $this->plan = PlanRecord::create([
            'title' => 'Upload Test Plan',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $this->target = $this->plan->targetObjectives()->create([
            'description' => 'Target for chunk upload verification',
            'quantity' => '100 units',
            'priority' => 'high',
        ]);
    }

    protected function tearDown(): void
    {
        // Clean up test staging and attachment files if any remain
        if (File::isDirectory(storage_path('app/staging/test_upload_123'))) {
            File::deleteDirectory(storage_path('app/staging/test_upload_123'));
        }

        parent::tearDown();
    }

    public function test_authenticated_user_can_upload_chunk_to_staging(): void
    {
        $uploadId = 'test_chunk_upload_'.uniqid();
        $chunk = UploadedFile::fake()->createWithContent('chunk_0.bin', 'first-half-of-content');

        $response = $this->actingAs($this->executor)->post(route('comments.upload.chunk'), [
            'upload_id' => $uploadId,
            'chunk_index' => 0,
            'total_chunks' => 2,
            'file' => $chunk,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'upload_id' => $uploadId,
                'chunk_index' => 0,
            ]);

        $stagedFile = storage_path("app/staging/{$uploadId}/chunk_0");
        $this->assertFileExists($stagedFile);
        $this->assertEquals('first-half-of-content', file_get_contents($stagedFile));

        // Clean up
        File::deleteDirectory(storage_path("app/staging/{$uploadId}"));
    }

    public function test_can_assemble_staged_chunks_and_transfer_to_permanent_storage(): void
    {
        $uploadId = 'test_assemble_'.uniqid();

        // Upload chunk 0
        $chunk0 = UploadedFile::fake()->createWithContent('chunk_0.bin', 'Part One: Verification. ');
        $this->actingAs($this->executor)->post(route('comments.upload.chunk'), [
            'upload_id' => $uploadId,
            'chunk_index' => 0,
            'total_chunks' => 2,
            'file' => $chunk0,
        ])->assertStatus(200);

        // Upload chunk 1
        $chunk1 = UploadedFile::fake()->createWithContent('chunk_1.bin', 'Part Two: Completed.');
        $this->actingAs($this->executor)->post(route('comments.upload.chunk'), [
            'upload_id' => $uploadId,
            'chunk_index' => 1,
            'total_chunks' => 2,
            'file' => $chunk1,
        ])->assertStatus(200);

        // Assemble chunks from staging to permanent
        $assembleResponse = $this->actingAs($this->executor)->postJson(route('comments.upload.assemble'), [
            'upload_id' => $uploadId,
            'filename' => 'audit_report.txt',
            'total_chunks' => 2,
        ]);

        $assembleResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'original_name' => 'audit_report.txt',
            ]);

        $data = $assembleResponse->json();
        $attachmentId = $data['attachment_id'];

        // Verify staging folder was cleaned up
        $this->assertDirectoryDoesNotExist(storage_path("app/staging/{$uploadId}"));

        // Verify permanent attachment record exists
        $attachment = CommentAttachment::find($attachmentId);
        $this->assertNotNull($attachment);
        $this->assertEquals('audit_report.txt', $attachment->original_name);

        // Verify file transferred to permanent directory and content matches
        $permanentPath = storage_path('app/public/'.$attachment->file_path);
        $this->assertFileExists($permanentPath);
        $this->assertEquals('Part One: Verification. Part Two: Completed.', file_get_contents($permanentPath));

        // Clean up permanent file
        File::delete($permanentPath);
    }

    public function test_cannot_assemble_if_chunks_are_missing_in_staging(): void
    {
        $uploadId = 'test_missing_chunk_'.uniqid();

        // Only upload chunk 0 out of 2
        $chunk0 = UploadedFile::fake()->createWithContent('chunk_0.bin', 'Incomplete');
        $this->actingAs($this->executor)->post(route('comments.upload.chunk'), [
            'upload_id' => $uploadId,
            'chunk_index' => 0,
            'total_chunks' => 2,
            'file' => $chunk0,
        ])->assertStatus(200);

        $response = $this->actingAs($this->executor)->postJson(route('comments.upload.assemble'), [
            'upload_id' => $uploadId,
            'filename' => 'test.txt',
            'total_chunks' => 2,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        File::deleteDirectory(storage_path("app/staging/{$uploadId}"));
    }

    public function test_can_link_attachment_to_comment_and_download(): void
    {
        $uploadId = 'test_link_'.uniqid();

        // Upload and assemble
        $chunk = UploadedFile::fake()->createWithContent('chunk_0.bin', 'Attachment payload content');
        $this->actingAs($this->executor)->post(route('comments.upload.chunk'), [
            'upload_id' => $uploadId,
            'chunk_index' => 0,
            'total_chunks' => 1,
            'file' => $chunk,
        ]);

        $assembleResponse = $this->actingAs($this->executor)->postJson(route('comments.upload.assemble'), [
            'upload_id' => $uploadId,
            'filename' => 'system_diagram.png',
            'total_chunks' => 1,
        ]);

        $attachmentId = $assembleResponse->json('attachment_id');

        // Post a remark with the attachment_id
        $this->actingAs($this->executor)->post(route('comments.store'), [
            'commentable_type' => 'target_objective',
            'commentable_id' => $this->target->id,
            'body' => 'Here is the diagram attachment.',
            'attachment_id' => $attachmentId,
        ])->assertRedirect(route('plans.show', $this->plan));

        $attachment = CommentAttachment::find($attachmentId);
        $this->assertNotNull($attachment->comment_id);
        $this->assertEquals($this->target->comments->first()->id, $attachment->comment_id);

        // Download attachment
        $downloadResponse = $this->actingAs($this->executor)->get(route('comments.attachments.download', $attachment));
        $downloadResponse->assertStatus(200);
        $downloadResponse->assertDownload('system_diagram.png');

        // Delete comment and verify attached file is cleaned up
        $comment = $attachment->comment;
        $filePath = storage_path('app/public/'.$attachment->file_path);
        $this->assertFileExists($filePath);

        $this->actingAs($this->executor)->delete(route('comments.destroy', $comment));
        $this->assertFileDoesNotExist($filePath);
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_can_attach_up_to_5_files_to_a_comment(): void
    {
        $attachmentIds = [];
        for ($i = 1; $i <= 5; $i++) {
            $att = CommentAttachment::create([
                'comment_id' => null,
                'upload_id' => 'multi_up_'.$i,
                'original_name' => "document_{$i}.pdf",
                'file_path' => "comments/attachments/doc_{$i}.pdf",
                'mime_type' => 'application/pdf',
                'file_size' => 1024 * 100, // 100 KB
            ]);
            $attachmentIds[] = $att->id;
        }

        $response = $this->actingAs($this->executor)->post(route('comments.store'), [
            'commentable_type' => 'target_objective',
            'commentable_id' => $this->target->id,
            'body' => 'Here are 5 attached documents.',
            'attachment_ids' => $attachmentIds,
        ]);

        $response->assertRedirect(route('plans.show', $this->plan));

        $comment = $this->target->comments()->latest()->first();
        $this->assertNotNull($comment);
        $this->assertCount(5, $comment->attachments);
    }

    public function test_cannot_attach_more_than_5_files_to_a_comment(): void
    {
        $attachmentIds = [];
        for ($i = 1; $i <= 6; $i++) {
            $att = CommentAttachment::create([
                'comment_id' => null,
                'upload_id' => 'over_limit_'.$i,
                'original_name' => "doc_{$i}.pdf",
                'file_path' => "comments/attachments/over_{$i}.pdf",
                'mime_type' => 'application/pdf',
                'file_size' => 1024,
            ]);
            $attachmentIds[] = $att->id;
        }

        $response = $this->actingAs($this->executor)->post(route('comments.store'), [
            'commentable_type' => 'target_objective',
            'commentable_id' => $this->target->id,
            'body' => 'Attempting to attach 6 files.',
            'attachment_ids' => $attachmentIds,
        ]);

        $response->assertSessionHasErrors(['attachment_ids']);
    }

    public function test_cannot_assemble_file_exceeding_5mb(): void
    {
        $uploadId = 'oversized_test_'.uniqid();
        $stagingDir = storage_path('app/staging/'.$uploadId);
        File::ensureDirectoryExists($stagingDir);

        // Create staged chunk totaling > 5MB (e.g. 5.2 MB)
        $chunkPath = $stagingDir.'/chunk_0';
        $fp = fopen($chunkPath, 'wb');
        // Write 5.2 MB of zeroes
        $chunkData = str_repeat('A', 1024 * 1024); // 1MB
        for ($i = 0; $i < 5; $i++) {
            fwrite($fp, $chunkData);
        }
        fwrite($fp, str_repeat('B', 300 * 1024)); // additional 300KB
        fclose($fp);

        $response = $this->actingAs($this->executor)->postJson(route('comments.upload.assemble'), [
            'upload_id' => $uploadId,
            'filename' => 'large_video.mp4',
            'total_chunks' => 1,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertStringContainsString('5MB', $response->json('message'));
        $this->assertDirectoryDoesNotExist($stagingDir);
    }

    public function test_comment_ajax_response_includes_image_and_slideshow_metadata(): void
    {
        $att = CommentAttachment::create([
            'comment_id' => null,
            'upload_id' => 'photo_up_test',
            'original_name' => 'screenshot.png',
            'file_path' => 'comments/attachments/screenshot.png',
            'mime_type' => 'image/png',
            'file_size' => 1024 * 50,
        ]);

        $response = $this->actingAs($this->executor)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('comments.store'), [
                'commentable_type' => 'target_objective',
                'commentable_id' => $this->target->id,
                'body' => 'Remark with photo attachment',
                'attachment_ids' => [$att->id],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'comment' => [
                    'attachments' => [
                        [
                            'id' => $att->id,
                            'is_image' => true,
                            'original_name' => 'screenshot.png',
                        ],
                    ],
                ],
            ]);

        $this->assertNotNull($response->json('comment.attachments.0.url'));
        $this->assertNotNull($response->json('comment.attachments.0.icon_class'));
    }

    public function test_attachment_thumbnail_and_slideshow_rendered_in_plan_view(): void
    {
        $comment = $this->target->comments()->create([
            'user_id' => $this->executor->id,
            'body' => 'Look at this photo',
        ]);

        $comment->attachments()->create([
            'upload_id' => 'photo_render_test',
            'original_name' => 'diagram.jpg',
            'file_path' => 'comments/attachments/diagram.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024 * 80,
        ]);

        $response = $this->actingAs($this->marshall)->get(route('plans.show', $this->plan));

        $response->assertStatus(200);
        $response->assertSee('attachment-thumbnail-trigger');
        $response->assertSee('attachmentSlideshowModal');
        $response->assertSee('slideshowZoomInBtn');
        $response->assertSee('slideshowRotateCWBtn');
        $response->assertSee('slideshowRotateCCWBtn');
    }
}
