<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LoginScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_login_screen_does_not_contain_demo_account_options(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertDontSee('DEMO CREDENTIALS');
        $response->assertDontSee('executor@plantrack.test');
        $response->assertDontSee('marshall@plantrack.test');
        $response->assertDontSee('value="password"', false);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        Role::firstOrCreate(['name' => 'Executor']);

        $user = User::factory()->create([
            'email' => 'operative@example.com',
            'password' => bcrypt('secret-password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'operative@example.com',
            'password' => 'secret-password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/home');
    }
}
