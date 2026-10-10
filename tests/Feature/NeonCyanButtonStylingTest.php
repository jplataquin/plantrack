<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NeonCyanButtonStylingTest extends TestCase
{
    use RefreshDatabase;

    public function test_retro_neon_scss_defines_black_or_dark_text_color_for_btn_neon_cyan(): void
    {
        $scssContent = file_get_contents(resource_path('sass/retro-neon.scss'));

        $this->assertNotFalse($scssContent);

        // Verify .btn-neon-cyan rules exist
        $this->assertStringContainsString('.btn-neon-cyan', $scssContent);

        // Verify black/dark text color is explicitly applied to .btn-neon-cyan
        $this->assertMatchesRegularExpression(
            '/\.btn-neon-cyan[^{]*\{[^}]*color:\s*(#000000|#0a0814|black)[^;]*!important/s',
            $scssContent
        );

        // Verify hover/focus/active states maintain black/dark text color
        $this->assertMatchesRegularExpression(
            '/\.btn-neon-cyan:hover[^{]*\{[^}]*color:\s*(#000000|#0a0814|black)[^;]*!important/s',
            $scssContent
        );

        // Verify child elements maintain black/dark text color
        $this->assertMatchesRegularExpression(
            '/(\*|span|i)[^}]*color:\s*(#000000|#0a0814|black)[^;]*!important/s',
            $scssContent
        );
    }

    public function test_pages_with_btn_neon_cyan_render_correctly(): void
    {
        $role = Role::firstOrCreate(['name' => 'Marshall']);
        $user = User::factory()->create();
        $user->assignRole($role);

        // Test plans index which has the "Apply Filters" button with .btn-neon-cyan
        $response = $this->actingAs($user)->get(route('plans.index'));

        $response->assertStatus(200);
        $response->assertSee('btn-neon-cyan');

        // Test executors index which has .btn-neon-cyan action buttons
        $response = $this->actingAs($user)->get(route('executors.index'));

        $response->assertStatus(200);
        $response->assertSee('btn-neon-cyan');
    }
}
