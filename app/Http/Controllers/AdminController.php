<?php

namespace App\Http\Controllers;

use App\Models\PlanRecord;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Ensure only users with Admin role can proceed.
     */
    protected function authorizeAdmin(): void
    {
        if (! Auth::user()->hasRole('Admin')) {
            abort(403, 'Unauthorized. System Administrator access required.');
        }
    }

    /**
     * Display the Admin Console and Users directory.
     */
    public function index(Request $request): View
    {
        $this->authorizeAdmin();

        $query = User::with(['roles'])->withCount('planRecords');

        // Filter by role
        if ($request->filled('role')) {
            $role = $request->role;
            if (in_array($role, ['Admin', 'Marshall', 'Executor'])) {
                $query->whereHas('roles', function ($q) use ($role) {
                    $q->where('name', $role);
                });
            }
        }

        // Search by operative name or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(15)->withQueryString();

        $metrics = [
            'total_users' => User::count(),
            'total_admins' => User::role('Admin')->count(),
            'total_marshalls' => User::role('Marshall')->count(),
            'total_executors' => User::role('Executor')->count(),
            'total_plans' => PlanRecord::count(),
            'total_projects' => Project::count(),
        ];

        return view('admin.index', compact('users', 'metrics'));
    }

    /**
     * Show the form for creating a new Operative (Marshall or Executor).
     */
    public function createUser(Request $request): View
    {
        $this->authorizeAdmin();

        $role = $request->query('role');
        $defaultRole = in_array($role, ['Marshall', 'Executor']) ? $role : 'Marshall';
        $suggestedPassword = Str::random(10);

        return view('admin.create-user', [
            'selectedRole' => $defaultRole,
            'suggestedPassword' => $suggestedPassword,
        ]);
    }

    /**
     * Store a newly provisioned Operative (Marshall or Executor).
     */
    public function storeUser(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'string', 'in:Marshall,Executor'],
            'temporary_password' => ['nullable', 'string', 'min:8', 'max:128'],
        ]);

        $roleName = $validated['role'];
        $temporaryPassword = ! empty($validated['temporary_password'])
            ? $validated['temporary_password']
            : Str::random(10);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($temporaryPassword),
            'must_reset_password' => true,
        ]);

        $role = Role::firstOrCreate(['name' => $roleName]);
        $user->assignRole($role);

        $flashData = [
            'success' => "{$roleName} operative account created successfully for {$user->name} ({$user->email}).",
            'created_user_role' => $roleName,
            'created_user_name' => $user->name,
            'created_user_email' => $user->email,
            'created_user_password' => $temporaryPassword,
        ];

        if ($roleName === 'Marshall') {
            $flashData['created_marshall_name'] = $user->name;
            $flashData['created_marshall_email'] = $user->email;
            $flashData['created_marshall_password'] = $temporaryPassword;
        } else {
            $flashData['created_executor_name'] = $user->name;
            $flashData['created_executor_email'] = $user->email;
            $flashData['created_executor_password'] = $temporaryPassword;
        }

        return redirect()->route('admin.index')->with($flashData);
    }

    /**
     * Show form for creating a new Marshall.
     */
    public function createMarshall(): View
    {
        $this->authorizeAdmin();

        $suggestedPassword = Str::random(10);

        return view('admin.create-user', [
            'selectedRole' => 'Marshall',
            'suggestedPassword' => $suggestedPassword,
        ]);
    }

    /**
     * Store a newly created Marshall.
     */
    public function storeMarshall(Request $request): RedirectResponse
    {
        $request->merge(['role' => 'Marshall']);

        return $this->storeUser($request);
    }

    /**
     * Show form for creating a new Executor.
     */
    public function createExecutor(): View
    {
        $this->authorizeAdmin();

        $suggestedPassword = Str::random(10);

        return view('admin.create-user', [
            'selectedRole' => 'Executor',
            'suggestedPassword' => $suggestedPassword,
        ]);
    }

    /**
     * Store a newly created Executor.
     */
    public function storeExecutor(Request $request): RedirectResponse
    {
        $request->merge(['role' => 'Executor']);

        return $this->storeUser($request);
    }
}
