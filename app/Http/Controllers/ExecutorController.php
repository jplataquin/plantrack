<?php

namespace App\Http\Controllers;

use App\Models\PlanRecord;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class ExecutorController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of executors.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $isMarshall = $user->hasAnyRole(['Marshall', 'Admin']);
        $isExecutor = $user->hasRole('Executor');
        $isAdmin = $user->hasRole('Admin');

        // Fetch Executors with their assigned plan records and components
        $executorsQuery = User::whereHas('roles', function ($q) {
            $q->where('name', 'Executor');
        })->orWhereHas('planRecords');

        // Search executor by name or email
        if ($request->filled('search')) {
            $search = $request->search;
            $executorsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $executors = $executorsQuery->with([
            'planRecords' => fn ($q) => $q->latest(),
            'planRecords.targetObjectives',
            'planRecords.resources',
            'planRecords.riskManagements',
            'planRecords.budgets',
        ])->get();

        $stats = [
            'total_plans' => PlanRecord::count(),
            'open_plans' => PlanRecord::where('status', 'Open')->count(),
            'review_plans' => PlanRecord::where('status', 'Review')->count(),
            'close_plans' => PlanRecord::where('status', 'Close')->count(),
            'void_plans' => PlanRecord::where('status', 'Void')->count(),
            'total_executors' => User::whereHas('roles', function ($q) {
                $q->where('name', 'Executor');
            })->orWhereHas('planRecords')->count(),
        ];

        return view('executors.index', compact('executors', 'stats', 'isMarshall', 'isExecutor'));
    }

    /**
     * Ensure only users with Marshall or Admin role can proceed.
     */
    protected function authorizeMarshall(): void
    {
        if (! Auth::user()->hasAnyRole(['Marshall', 'Admin'])) {
            abort(403, 'Unauthorized. Only Marshalls are authorized to create Executor accounts.');
        }
    }

    /**
     * Show the form for creating a new Executor account.
     */
    public function create(): View
    {
        $this->authorizeMarshall();

        // Suggest a secure random temporary password for convenience
        $suggestedPassword = Str::random(10);

        return view('executors.create', compact('suggestedPassword'));
    }

    /**
     * Store a newly created Executor account in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeMarshall();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'temporary_password' => ['nullable', 'string', 'min:8', 'max:128'],
        ]);

        // Use custom temporary password or generate a random one if omitted
        $temporaryPassword = ! empty($validated['temporary_password'])
            ? $validated['temporary_password']
            : Str::random(10);

        $executor = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($temporaryPassword),
            'must_reset_password' => true,
        ]);

        // Ensure Executor role exists and assign it
        $executorRole = Role::firstOrCreate(['name' => 'Executor']);
        $executor->assignRole($executorRole);

        return redirect()->route('executors.show', $executor)->with([
            'success' => "Executor account created successfully for {$executor->name} ({$executor->email}).",
            'created_executor_name' => $executor->name,
            'created_executor_email' => $executor->email,
            'created_executor_password' => $temporaryPassword,
        ]);
    }
}
