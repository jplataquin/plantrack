<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Authorize Marshall or Admin role for restricted project operations.
     */
    protected function authorizeMarshall(): void
    {
        if (! Auth::user()->hasAnyRole(['Marshall', 'Admin'])) {
            abort(403, 'Unauthorized. Only Marshalls are authorized to manage projects.');
        }
    }

    /**
     * Display a listing of projects.
     */
    public function index(Request $request): View
    {
        $query = Project::query()->withCount('planRecords');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('status') && in_array($request->status, ['Active', 'Deactive'])) {
            $query->where('status', $request->status);
        }

        $projects = $query->latest()->paginate(12)->withQueryString();

        $stats = [
            'total' => Project::count(),
            'active' => Project::where('status', 'Active')->count(),
            'deactive' => Project::where('status', 'Deactive')->count(),
        ];

        $isMarshall = Auth::user()->hasAnyRole(['Marshall', 'Admin']);

        return view('projects.index', compact('projects', 'stats', 'isMarshall'));
    }

    /**
     * Show the form for creating a new project.
     */
    public function create(): View
    {
        $this->authorizeMarshall();

        return view('projects.create');
    }

    /**
     * Store a newly created project in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeMarshall();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:projects,name',
            ],
            'status' => [
                'required',
                Rule::in(['Active', 'Deactive']),
            ],
        ]);

        $project = Project::create($validated);

        return redirect()->route('projects.index')->with('success', "Project \"{$project->name}\" created successfully.");
    }

    /**
     * Display the specified project.
     */
    public function show(Project $project): View
    {
        $project->load([
            'planRecords' => fn ($q) => $q->orderBy('start_date', 'desc')->latest('id'),
            'planRecords.executor',
        ]);
        $isMarshall = Auth::user()->hasAnyRole(['Marshall', 'Admin']);

        return view('projects.show', compact('project', 'isMarshall'));
    }

    /**
     * Show the form for editing the specified project.
     */
    public function edit(Project $project): View
    {
        $this->authorizeMarshall();

        return view('projects.edit', compact('project'));
    }

    /**
     * Update the specified project in storage.
     */
    public function update(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeMarshall();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('projects', 'name')->ignore($project->id),
            ],
            'status' => [
                'required',
                Rule::in(['Active', 'Deactive']),
            ],
        ]);

        $project->update($validated);

        return redirect()->route('projects.index')->with('success', "Project \"{$project->name}\" updated successfully.");
    }

    /**
     * Remove the specified project from storage (soft delete).
     */
    public function destroy(Project $project): RedirectResponse
    {
        $this->authorizeMarshall();

        $projectName = $project->name;
        $project->delete();

        return redirect()->route('projects.index')->with('success', "Project \"{$projectName}\" soft-deleted successfully.");
    }
}
