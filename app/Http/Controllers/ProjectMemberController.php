<?php

namespace App\Http\Controllers;

use App\Http\Requests\Projects\StoreProjectMemberRequest;
use App\Http\Requests\Projects\UpdateProjectMemberRequest;
use App\Http\Resources\ProjectMemberResource;
use App\Models\Project;
use App\Models\ProjectMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;

class ProjectMemberController extends Controller
{
    public function index(Project $project): AnonymousResourceCollection
    {
        $this->authorize('view', $project);

        return ProjectMemberResource::collection($project->members()->with('user')->get());
    }

    public function store(StoreProjectMemberRequest $request, Project $project): RedirectResponse
    {
        ProjectMember::create([
            ...$request->validated(),
            'project_id' => $project->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member added.')]);

        return to_route('projects.show', $project);
    }

    public function update(UpdateProjectMemberRequest $request, Project $project, ProjectMember $member): RedirectResponse
    {
        $member->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member updated.')]);

        return to_route('projects.show', $project);
    }

    public function destroy(Project $project, ProjectMember $member): RedirectResponse
    {
        $this->authorize('manageMembers', $project);

        $member->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member removed.')]);

        return to_route('projects.show', $project);
    }
}
