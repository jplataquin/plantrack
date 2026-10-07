<?php

namespace Tests\Feature;

use App\Models\PlanRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProfilePictureTest extends TestCase
{
    use RefreshDatabase;

    private User $executor;

    private User $marshall;

    protected function setUp(): void
    {
        parent::setUp();

        $executorRole = Role::firstOrCreate(['name' => 'Executor']);
        $marshallRole = Role::firstOrCreate(['name' => 'Marshall']);

        $this->executor = User::factory()->create([
            'name' => 'John Profile',
            'email' => 'john.profile@test.com',
        ]);
        $this->executor->assignRole($executorRole);

        $this->marshall = User::factory()->create([
            'name' => 'Mary Marshall',
            'email' => 'mary.marshall@test.com',
        ]);
        $this->marshall->assignRole($marshallRole);
    }

    /**
     * Helper to create a small valid 100x100 JPEG image payload.
     */
    private function createValidJpegData(): string
    {
        $im = imagecreatetruecolor(100, 100);
        $bg = imagecolorallocate($im, 0, 240, 255);
        imagefilledrectangle($im, 0, 0, 100, 100, $bg);
        ob_start();
        imagejpeg($im, null, 80);
        $data = ob_get_clean();
        imagedestroy($im);

        return $data;
    }

    public function test_user_can_upload_profile_picture_using_staging_and_chunks(): void
    {
        $uploadId = 'avatar_test_'.uniqid();
        $jpegData = $this->createValidJpegData();

        // Upload chunk 0
        $half = (int) (strlen($jpegData) / 2);
        $part1 = substr($jpegData, 0, $half);
        $part2 = substr($jpegData, $half);

        $chunk0 = UploadedFile::fake()->createWithContent('chunk_0.bin', $part1);
        $this->actingAs($this->executor)->post(route('comments.upload.chunk'), [
            'upload_id' => $uploadId,
            'chunk_index' => 0,
            'total_chunks' => 2,
            'file' => $chunk0,
        ])->assertStatus(200);

        $chunk1 = UploadedFile::fake()->createWithContent('chunk_1.bin', $part2);
        $this->actingAs($this->executor)->post(route('comments.upload.chunk'), [
            'upload_id' => $uploadId,
            'chunk_index' => 1,
            'total_chunks' => 2,
            'file' => $chunk1,
        ])->assertStatus(200);

        // Assemble avatar
        $response = $this->actingAs($this->executor)->postJson(route('profile.avatar.update'), [
            'upload_id' => $uploadId,
            'filename' => 'avatar.jpg',
            'total_chunks' => 2,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'user_id' => $this->executor->id,
            ]);

        $this->executor->refresh();
        $this->assertNotNull($this->executor->profile_picture);
        $this->assertStringStartsWith('avatars/', $this->executor->profile_picture);

        $permanentPath = storage_path('app/public/'.$this->executor->profile_picture);
        $this->assertFileExists($permanentPath);

        // Staging directory should be cleaned up
        $this->assertDirectoryDoesNotExist(storage_path("app/staging/{$uploadId}"));

        // Clean up file
        File::delete($permanentPath);
    }

    public function test_cannot_assemble_profile_picture_exceeding_5mb(): void
    {
        $uploadId = 'oversized_avatar_'.uniqid();
        $stagingDir = storage_path('app/staging/'.$uploadId);
        File::ensureDirectoryExists($stagingDir);

        // Write a 5.2MB file in staging
        $chunkPath = $stagingDir.'/chunk_0';
        $fp = fopen($chunkPath, 'wb');
        for ($i = 0; $i < 5; $i++) {
            fwrite($fp, str_repeat('X', 1024 * 1024));
        }
        fwrite($fp, str_repeat('Y', 300 * 1024));
        fclose($fp);

        $response = $this->actingAs($this->executor)->postJson(route('profile.avatar.update'), [
            'upload_id' => $uploadId,
            'filename' => 'avatar.jpg',
            'total_chunks' => 1,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertStringContainsString('5MB', $response->json('message'));
        $this->assertDirectoryDoesNotExist($stagingDir);
    }

    public function test_cannot_assemble_non_image_file_as_avatar(): void
    {
        $uploadId = 'non_image_avatar_'.uniqid();
        $stagingDir = storage_path('app/staging/'.$uploadId);
        File::ensureDirectoryExists($stagingDir);

        file_put_contents($stagingDir.'/chunk_0', 'This is plain text and not an image.');

        $response = $this->actingAs($this->executor)->postJson(route('profile.avatar.update'), [
            'upload_id' => $uploadId,
            'filename' => 'avatar.jpg',
            'total_chunks' => 1,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertStringContainsString('valid image', $response->json('message'));
        $this->assertDirectoryDoesNotExist($stagingDir);
    }

    public function test_marshall_can_update_another_user_avatar(): void
    {
        $uploadId = 'marshall_updating_'.uniqid();
        $stagingDir = storage_path('app/staging/'.$uploadId);
        File::ensureDirectoryExists($stagingDir);

        file_put_contents($stagingDir.'/chunk_0', $this->createValidJpegData());

        $response = $this->actingAs($this->marshall)->postJson(route('profile.avatar.update'), [
            'upload_id' => $uploadId,
            'filename' => 'avatar.jpg',
            'total_chunks' => 1,
            'user_id' => $this->executor->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'user_id' => $this->executor->id,
            ]);

        $this->executor->refresh();
        $this->assertNotNull($this->executor->profile_picture);

        // Clean up
        File::delete(storage_path('app/public/'.$this->executor->profile_picture));
    }

    public function test_executor_cannot_update_another_user_avatar(): void
    {
        $response = $this->actingAs($this->executor)->postJson(route('profile.avatar.update'), [
            'upload_id' => 'unauth_'.uniqid(),
            'filename' => 'avatar.jpg',
            'total_chunks' => 1,
            'user_id' => $this->marshall->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_their_profile_picture(): void
    {
        $dummyPath = 'avatars/delete_test.jpg';
        File::ensureDirectoryExists(storage_path('app/public/avatars'));
        file_put_contents(storage_path('app/public/'.$dummyPath), 'avatar-content');

        $this->executor->update(['profile_picture' => $dummyPath]);

        $response = $this->actingAs($this->executor)->deleteJson(route('profile.avatar.destroy'));
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->executor->refresh();
        $this->assertNull($this->executor->profile_picture);
        $this->assertFileDoesNotExist(storage_path('app/public/'.$dummyPath));
    }

    public function test_profile_picture_is_rendered_in_executor_cards_and_sidebar(): void
    {
        $avatarPath = 'avatars/rendered_test.jpg';
        File::ensureDirectoryExists(storage_path('app/public/avatars'));
        file_put_contents(storage_path('app/public/'.$avatarPath), 'avatar-payload');

        $this->executor->update(['profile_picture' => $avatarPath]);

        $plan = PlanRecord::create([
            'title' => 'Test Plan with Avatar',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        // 1. Executors Directory cards view
        $indexResponse = $this->actingAs($this->marshall)->get(route('executors.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('storage/'.$avatarPath);

        // 2. Executor Show sidebar view
        $showExecutorResponse = $this->actingAs($this->marshall)->get(route('executors.show', $this->executor));
        $showExecutorResponse->assertStatus(200);
        $showExecutorResponse->assertSee('storage/'.$avatarPath);

        // 3. Plan Show sidebar view
        $showPlanResponse = $this->actingAs($this->marshall)->get(route('plans.show', $plan));
        $showPlanResponse->assertStatus(200);
        $showPlanResponse->assertSee('storage/'.$avatarPath);

        // Clean up
        File::delete(storage_path('app/public/'.$avatarPath));
    }

    public function test_storage_avatar_file_can_be_directly_requested_and_served(): void
    {
        $avatarPath = 'avatars/served_test.jpg';
        File::ensureDirectoryExists(storage_path('app/public/avatars'));
        file_put_contents(storage_path('app/public/'.$avatarPath), $this->createValidJpegData());

        $response = $this->get('/storage/'.$avatarPath);
        $response->assertStatus(200);
        $this->assertEquals('image/jpeg', $response->headers->get('Content-Type'));

        // Path traversal attack should 404
        $traversalResponse = $this->get('/storage/../../.env');
        $traversalResponse->assertStatus(404);

        // Non-existent file should 404
        $notFoundResponse = $this->get('/storage/avatars/non_existent_file.jpg');
        $notFoundResponse->assertStatus(404);

        File::delete(storage_path('app/public/'.$avatarPath));
    }
}
