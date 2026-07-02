<?php

namespace App\Http\Controllers;

use App\Enums\ProjectRole;
use App\Enums\StateGroup;
use App\Http\Requests\Projects\StoreProjectRequest;
use App\Http\Requests\Projects\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\State;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use LogicException;

class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Project::class);

        $user = $request->user();

        $projects = Project::query()
            ->where(function (Builder $query) use ($user) {
                $query->where('lead_id', $user->id)
                    ->orWhereHas('members', function (Builder $query) use ($user) {
                        $query->where('user_id', $user->id)
                            ->where('is_active', true);
                    });
            })
            ->whereNull('archived_at')
            ->with('lead')
            ->latest()
            ->get();

        return Inertia::render('projects/Index', [
            'projects' => ProjectResource::collection($projects),
        ]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = DB::transaction(function () use ($request) {
            $project = Project::query()->create([
                ...$request->validated(),
                'lead_id' => $request->user()->id,
            ]);

            ProjectMember::query()->create([
                'project_id' => $project->id,
                'user_id' => $request->user()->id,
                'role' => ProjectRole::Admin,
            ]);

            $defaultState = $this->createDefaultStates($project);

            $project->update(['default_state_id' => $defaultState->id]);

            return $project;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project created.')]);

        return to_route('projects.show', $project);
    }

    public function show(Project $project): Response
    {
        $this->authorize('view', $project);

        $project->load([
            'lead',
            'members.user',
            'states' => fn ($query) => $query->orderBy('sequence'),
        ]);

        return Inertia::render('projects/Show', [
            'project' => ProjectResource::make($project),
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project updated.')]);

        return to_route('projects.show', $project);
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        $project->update(['archived_at' => now()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project archived.')]);

        return to_route('projects.index');
    }

    private function createDefaultStates(Project $project): State
    {
        $states = [
            ['Backlog', 'backlog', '#6b7280', 10000, StateGroup::Backlog, false],
            ['Todo', 'todo', '#3b82f6', 20000, StateGroup::Unstarted, true],
            ['In Progress', 'in-progress', '#f59e0b', 30000, StateGroup::Started, false],
            ['Done', 'done', '#22c55e', 40000, StateGroup::Completed, false],
            ['Cancelled', 'cancelled', '#ef4444', 50000, StateGroup::Cancelled, false],
        ];

        $defaultState = null;

        foreach ($states as [$name, $slug, $color, $sequence, $group, $isDefault]) {
            $state = State::query()->create([
                'project_id' => $project->id,
                'name' => $name,
                'slug' => $slug,
                'color' => $color,
                'sequence' => $sequence,
                'group' => $group,
                'is_default' => $isDefault,
            ]);

            if ($isDefault) {
                $defaultState = $state;
            }
        }

        return $defaultState ?? throw new LogicException('Default states must include one default state.');
    }
}
