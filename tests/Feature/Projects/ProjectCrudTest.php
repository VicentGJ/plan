<?php

namespace Tests\Feature\Projects;

use App\Enums\ProjectRole;
use App\Enums\StateGroup;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProjectCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_project_with_default_workflow(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('projects.store'), [
            'name' => 'Website',
            'identifier' => 'web',
            'description' => 'Marketing site',
        ]);

        $project = Project::firstOrFail();

        $response->assertRedirect(route('projects.show', $project));
        $this->assertSame('WEB', $project->identifier);
        $this->assertTrue($project->lead->is($user));
        $this->assertSame(5, $project->states()->count());
        $this->assertSame(StateGroup::Unstarted, $project->defaultState->group);
        $this->assertTrue($project->defaultState->is_default);
        $this->assertSame(ProjectRole::Admin, $project->members()->whereBelongsTo($user)->firstOrFail()->role);
    }

    public function test_guest_cannot_create_project(): void
    {
        $response = $this->post(route('projects.store'), [
            'name' => 'Website',
            'identifier' => 'WEB',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertSame(0, Project::count());
    }

    public function test_non_member_cannot_view_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $this->actingAs($user)
            ->get(route('projects.show', $project))
            ->assertForbidden();
    }

    public function test_project_member_can_view_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        ProjectMember::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => ProjectRole::Viewer,
        ]);

        $this->actingAs($user)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('projects/Show'));
    }

    public function test_project_admin_can_update_project(): void
    {
        [$admin, $project] = $this->createProjectAdmin();

        $this->actingAs($admin)
            ->patch(route('projects.update', $project), [
                'name' => 'API',
                'identifier' => 'api',
                'description' => 'Backend work',
            ])
            ->assertRedirect(route('projects.show', $project));

        $project->refresh();

        $this->assertSame('API', $project->name);
        $this->assertSame('API', $project->identifier);
        $this->assertSame('Backend work', $project->description);
    }

    public function test_project_admin_can_archive_project(): void
    {
        [$admin, $project] = $this->createProjectAdmin();

        $this->actingAs($admin)
            ->delete(route('projects.destroy', $project))
            ->assertRedirect(route('projects.index'));

        $this->assertNotNull($project->refresh()->archived_at);
    }

    /**
     * @return array{User, Project}
     */
    private function createProjectAdmin(): array
    {
        $admin = User::factory()->create();
        $project = Project::factory()->create();

        ProjectMember::factory()->create([
            'project_id' => $project->id,
            'user_id' => $admin->id,
            'role' => ProjectRole::Admin,
        ]);

        return [$admin, $project];
    }
}
