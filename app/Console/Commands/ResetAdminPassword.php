<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class ResetAdminPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:reset-password
                            {email? : The email address of the admin user}
                            {--email= : The email address of the admin user}
                            {--password= : The new password for the admin user (generates random password if omitted)}
                            {--reset : Require password reset upon initial login}
                            {--force : Grant Admin role and reset password if the user is not currently an administrator}';

    /**
     * Alternative signatures / aliases for this command.
     *
     * @var array<int, string>
     */
    protected $aliases = ['admin:password', 'admin:reset'];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset the password of an existing administrator user.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email') ?: $this->option('email');
        $password = $this->option('password');
        $mustReset = (bool) $this->option('reset');
        $force = (bool) $this->option('force');

        $prompted = false;
        // Prompt interactively if email was not supplied
        if (! $email && $this->input->isInteractive()) {
            $prompted = true;
            $adminEmails = User::role('Admin')->pluck('email')->toArray();

            if (! empty($adminEmails)) {
                $options = array_merge($adminEmails, ['Enter email manually...']);
                $choice = $this->choice('Select the administrator account to reset', $options, 0);

                if ($choice === 'Enter email manually...') {
                    $email = $this->ask('Enter administrator email address');
                } else {
                    $email = $choice;
                }
            } else {
                $email = $this->ask('Enter administrator email address');
            }
        }

        if (! $email) {
            $this->newLine();
            $this->error('The admin email address is required. Provide it as an argument or using --email.');
            $this->newLine();

            return Command::FAILURE;
        }

        $generatedPassword = false;
        if (! $password && $prompted && $this->input->isInteractive()) {
            $password = $this->secret('Enter new administrator password (leave empty to generate a random key)');
        }

        if (! $password) {
            $password = Str::random(12);
            $generatedPassword = true;
        }

        // Validate basic formats
        $validator = Validator::make([
            'email' => $email,
            'password' => $password,
        ], [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:128'],
        ]);

        if ($validator->fails()) {
            $this->newLine();
            $this->error('Failed to reset admin password due to validation errors:');
            foreach ($validator->errors()->all() as $error) {
                $this->line("  • <fg=red>{$error}</>");
            }
            $this->newLine();

            return Command::FAILURE;
        }

        // Look up the user
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->newLine();
            $this->error("No user found with email [{$email}].");
            $this->newLine();

            return Command::FAILURE;
        }

        // Verify Admin role
        if (! $user->hasRole('Admin')) {
            if (! $force) {
                $this->newLine();
                $this->error("User [{$user->name}] ({$user->email}) is not an administrator.");
                $this->comment('Use the --force option to assign the Admin role and reset the password.');
                $this->newLine();

                return Command::FAILURE;
            }

            $adminRole = Role::firstOrCreate(['name' => 'Admin']);
            $user->assignRole($adminRole);
        }

        // Update password and reset flag
        $user->password = Hash::make($password);
        $user->must_reset_password = $mustReset;
        $user->save();

        $this->newLine();
        $this->info("✓ Successfully reset password for administrator [{$user->name}] ({$user->email}).");

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
            $this->warn('⚠ Make sure to securely save or transmit the temporary password above.');
        }

        $this->newLine();

        return Command::SUCCESS;
    }
}
