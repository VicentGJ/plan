<?php

namespace Tests\Feature\Projects;

use App\Enums\ProjectRole;
use App\Enums\StateGroup;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StateCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_admin_can_create_state(): void
    {
        [$admin, $project] = $this->createProjectAdmin();

        $this->actingAs($admin)
            ->post(route('projects.states.store', $project), [
                'name' => 'Code Review',
                'color' => '#8b5cf6',
                'sequence' => 25000,
                'group' => StateGroup::Started->value,
            ])
            ->assertRedirect(route('projects.show', $project));

        $state = State::query()->where('project_id', $project->id)->firstOrFail();

        $this->assertSame('Code Review', $state->name);
        $this->assertSame('code-review', $state->slug);
        $this->assertSame(StateGroup::Started, $state->group);
    }

    public function test_project_admin_can_update_and_delete_non_default_state(): void
    {
        [$admin, $project] = $this->createProjectAdmin();
        $state = State::factory()->create(['project_id' => $project->id]);

        $this->actingAs($admin)
            ->patch(route('projects.states.update', [$project, $state]), [
                'name' => 'Doing',
                'color' => '#f59e0b',
                'sequence' => 30000,
                'group' => StateGroup::Started->value,
            ])
            ->assertRedirect(route('projects.show', $project));

        $this->assertSame('doing', $state->refresh()->slug);

        $this->actingAs($admin)
            ->delete(route('projects.states.destroy', [$project, $state]))
            ->assertRedirect(route('projects.show', $project));

        $this->assertModelMissing($state);
    }

    public function test_default_state_cannot_be_deleted(): void
    {
        [$admin, $project] = $this->createProjectAdmin();
        $state = State::factory()->create([
            'project_id' => $project->id,
            'is_default' => true,
        ]);
        $project->update(['default_state_id' => $state->id]);

        $this->actingAs($admin)
            ->delete(route('projects.states.destroy', [$project, $state]))
            ->assertSessionHasErrors('state');

        $this->assertModelExists($state);
    }

    public function test_project_admin_can_set_default_state(): void
    {
        [$admin, $project] = $this->createProjectAdmin();
        $oldDefault = State::factory()->create([
            'project_id' => $project->id,
            'is_default' => true,
        ]);
        $newDefault = State::factory()->create([
            'project_id' => $project->id,
            'is_default' => false,
        ]);
        $project->update(['default_state_id' => $oldDefault->id]);

        $this->actingAs($admin)
            ->post(route('projects.states.default', [$project, $newDefault]))
            ->assertRedirect(route('projects.show', $project));

        $this->assertSame($newDefault->id, $project->refresh()->default_state_id);
        $this->assertFalse($oldDefault->refresh()->is_default);
        $this->assertTrue($newDefault->refresh()->is_default);
    }

    public function test_project_admin_can_reorder_states(): void
    {
        [$admin, $project] = $this->createProjectAdmin();
        $first = State::factory()->create(['project_id' => $project->id, 'sequence' => 10000]);
        $second = State::factory()->create(['project_id' => $project->id, 'sequence' => 20000]);

        $this->actingAs($admin)
            ->post(route('projects.states.reorder', $project), [
                'states' => [
                    ['id' => $first->id, 'sequence' => 30000],
                    ['id' => $second->id, 'sequence' => 10000],
                ],
            ])
            ->assertRedirect(route('projects.show', $project));

        $this->assertSame(30000, $first->refresh()->sequence);
        $this->assertSame(10000, $second->refresh()->sequence);
    }

    public function test_non_admin_cannot_manage_states(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        ProjectMember::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => ProjectRole::Member,
        ]);

        $this->actingAs($user)
            ->post(route('projects.states.store', $project), [
                'name' => 'Blocked',
                'color' => '#ef4444',
                'sequence' => 10000,
                'group' => StateGroup::Started->value,
            ])
            ->assertForbidden();
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
