<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create
                            {--name= : The full name of the admin user}
                            {--email= : The email address of the admin user}
                            {--password= : The password for the admin user (generates random password if omitted)}
                            {--reset : Require password reset upon initial login}
                            {--force : Grant Admin role if user already exists}';

    /**
     * Alternative signatures / aliases for this command.
     *
     * @var array<int, string>
     */
    protected $aliases = ['make:admin'];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new administrator user or assign Admin role to an existing user.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->option('name');
        $email = $this->option('email');
        $password = $this->option('password');
        $mustReset = (bool) $this->option('reset');
        $force = (bool) $this->option('force');

        // Prompt interactively if options were not supplied in an interactive terminal
        $prompted = false;
        if (! $name && $this->input->isInteractive()) {
            $name = $this->ask('Enter the administrator full name');
            $prompted = true;
        }

        if (! $email && $this->input->isInteractive()) {
            $email = $this->ask('Enter the administrator email address');
            $prompted = true;
        }

        $generatedPassword = false;
        if (! $password && $prompted && $this->input->isInteractive()) {
            $password = $this->secret('Enter administrator password (leave empty to generate a random key)');
        }

        if (! $password) {
            $password = Str::random(12);
            $generatedPassword = true;
        }

        // Validate basic formats
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:128'],
        ]);

        if ($validator->fails()) {
            $this->newLine();
            $this->error('Failed to create admin user due to validation errors:');
            foreach ($validator->errors()->all() as $error) {
                $this->line("  • <fg=red>{$error}</>");
            }
            $this->newLine();

            return Command::FAILURE;
        }

        $adminRole = Role::firstOrCreate(['name' => 'Admin']);

        // Check if user already exists
        $existingUser = User::where('email', $email)->first();

        if ($existingUser) {
            if (! $force) {
                $this->newLine();
                $this->error("A user with email [{$email}] already exists.");
                $this->comment('Use the --force option to grant the Admin role to this existing user.');
                $this->newLine();

                return Command::FAILURE;
            }

            // Update existing user with Admin role
            $existingUser->assignRole($adminRole);

            if ($this->option('name')) {
                $existingUser->name = $name;
            }

            if ($this->option('password')) {
                $existingUser->password = Hash::make($password);
            }

            if ($mustReset) {
                $existingUser->must_reset_password = true;
            }

            $existingUser->save();

            $this->newLine();
            $this->info("✓ Successfully granted Admin role to existing operative [{$existingUser->name}] ({$existingUser->email}).");
            $this->table(
                ['Field', 'Value'],
                [
                    ['ID', $existingUser->id],
                    ['Name', $existingUser->name],
                    ['Email', $existingUser->email],
                    ['Role', 'Admin'],
                    ['Password', $this->option('password') ? '(Updated to supplied password)' : '(Unchanged)'],
                    ['Must Reset Key', $existingUser->must_reset_password ? 'Yes' : 'No'],
                ]
            );
            $this->newLine();

            return Command::SUCCESS;
        }

        // Create new Admin user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'must_reset_password' => $mustReset,
        ]);

        $user->assignRole($adminRole);

        $this->newLine();
        $this->info("✓ Administrator [{$user->name}] successfully provisioned.");

        $this->table(
            ['Field', 'Value'],
            [
                ['ID', $user->id],
                ['Name', $user->name],
                ['Email', $user->email],
                ['Role', 'Admin'],
                ['Password', $password],
                ['Password Type', $generatedPassword ? 'Auto-generated (Temporary/Random)' : 'Custom provided'],
                ['Must Reset Key', $user->must_reset_password ? 'Yes (Enforced on 1st login)' : 'No'],
            ]
        );

        if ($generatedPassword) {
            $this->warn("⚠ Make sure to securely save or transmit the temporary password above.");
        }

        $this->newLine();

        return Command::SUCCESS;
    }
}
