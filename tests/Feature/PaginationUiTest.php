<?php

namespace Tests\Feature;

use App\Models\PlanRecord;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaginationUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_pagination_view_uses_bootstrap_five(): void
    {
        $paginator = new LengthAwarePaginator([1, 2], 30, 15, 1, ['path' => '/plans']);
        $html = $paginator->links()->toHtml();

        // Must contain Bootstrap 5 pagination classes
        $this->assertStringContainsString('pagination', $html);
        $this->assertStringContainsString('page-item', $html);
        $this->assertStringContainsString('page-link', $html);

        // Must NOT contain unstyled Tailwind SVG icon classes
        $this->assertStringNotContainsString('w-5 h-5', $html);
    }

    public function test_plans_page_renders_bootstrap_five_pagination_when_records_exceed_per_page(): void
    {
        $role = Role::firstOrCreate(['name' => 'Marshall']);
        $user = User::factory()->create();
        $user->assignRole($role);

        // Create 16 plans (page limit is 15)
        for ($i = 1; $i <= 16; $i++) {
            PlanRecord::create([
                'title' => "Plan Record {$i}",
                'executor_id' => $user->id,
                'status' => 'Open',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDays(5)->toDateString(),
            ]);
        }

        $response = $this->actingAs($user)->get(route('plans.index'));

        $response->assertStatus(200);
        $response->assertSee('pagination');
        $response->assertSee('page-item');
        $response->assertSee('page-link');
        $response->assertDontSee('w-5 h-5');
    }

    public function test_projects_page_renders_bootstrap_five_pagination(): void
    {
        $role = Role::firstOrCreate(['name' => 'Marshall']);
        $user = User::factory()->create();
        $user->assignRole($role);

        // Create 13 projects (page limit is 12)
        for ($i = 1; $i <= 13; $i++) {
            Project::create([
                'name' => "Project {$i}",
                'status' => 'Active',
            ]);
        }

        $response = $this->actingAs($user)->get(route('projects.index'));

        $response->assertStatus(200);
        $response->assertSee('pagination');
        $response->assertSee('page-item');
        $response->assertSee('page-link');
        $response->assertDontSee('w-5 h-5');
    }
}
