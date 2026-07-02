<?php

namespace App\Http\Controllers;

use App\Http\Requests\Projects\ReorderStatesRequest;
use App\Http\Requests\Projects\StoreStateRequest;
use App\Http\Requests\Projects\UpdateStateRequest;
use App\Http\Resources\StateResource;
use App\Models\Project;
use App\Models\State;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class StateController extends Controller
{
    public function index(Project $project): AnonymousResourceCollection
    {
        $this->authorize('view', $project);

        return StateResource::collection($project->states()->orderBy('sequence')->get());
    }

    public function store(StoreStateRequest $request, Project $project): RedirectResponse
    {
        $state = State::query()->create([
            ...$request->validated(),
            'project_id' => $project->id,
        ]);

        if ($state->is_default) {
            $this->setProjectDefaultState($project, $state);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('State created.')]);

        return to_route('projects.show', $project);
    }

    public function update(UpdateStateRequest $request, Project $project, State $state): RedirectResponse
    {
        $state->update($request->validated());

        if ($state->is_default) {
            $this->setProjectDefaultState($project, $state);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('State updated.')]);

        return to_route('projects.show', $project);
    }

    public function destroy(Project $project, State $state): RedirectResponse
    {
        $this->authorize('manageStates', $project);

        if ($project->default_state_id === $state->id || $state->is_default) {
            throw ValidationException::withMessages([
                'state' => __('The default state cannot be deleted.'),
            ]);
        }

        $state->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('State deleted.')]);

        return to_route('projects.show', $project);
    }

    public function reorder(ReorderStatesRequest $request, Project $project): RedirectResponse
    {
        DB::transaction(function () use ($request, $project) {
            foreach ($request->validated('states') as $state) {
                $project->states()
                    ->whereKey($state['id'])
                    ->update(['sequence' => $state['sequence']]);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('States reordered.')]);

        return to_route('projects.show', $project);
    }

    public function setDefault(Project $project, State $state): RedirectResponse
    {
        $this->authorize('manageStates', $project);

        $this->setProjectDefaultState($project, $state);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Default state updated.')]);

        return to_route('projects.show', $project);
    }

    private function setProjectDefaultState(Project $project, State $state): void
    {
        DB::transaction(function () use ($project, $state) {
            $project->states()->update(['is_default' => false]);
            $state->update(['is_default' => true]);
            $project->update(['default_state_id' => $state->id]);
        });
    }
}
