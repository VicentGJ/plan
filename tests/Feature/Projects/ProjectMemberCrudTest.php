<?php

namespace Tests\Feature\Projects;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectMemberCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_admin_can_add_member(): void
    {
        [$admin, $project] = $this->createProjectAdmin();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('projects.members.store', $project), [
                'user_id' => $user->id,
                'role' => ProjectRole::Member->value,
            ])
            ->assertRedirect(route('projects.show', $project));

        $member = ProjectMember::whereBelongsTo($project)->whereBelongsTo($user)->firstOrFail();

        $this->assertSame(ProjectRole::Member, $member->role);
        $this->assertTrue($member->is_active);
    }

    public function test_duplicate_member_fails_validation(): void
    {
        [$admin, $project] = $this->createProjectAdmin();
        $user = User::factory()->create();

        ProjectMember::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($admin)
            ->post(route('projects.members.store', $project), [
                'user_id' => $user->id,
                'role' => ProjectRole::Member->value,
            ])
            ->assertSessionHasErrors('user_id');
    }

    public function test_project_admin_can_update_member_role_and_active_status(): void
    {
        [$admin, $project] = $this->createProjectAdmin();
        $member = ProjectMember::factory()->create([
            'project_id' => $project->id,
            'role' => ProjectRole::Viewer,
        ]);

        $this->actingAs($admin)
            ->patch(route('projects.members.update', [$project, $member]), [
                'role' => ProjectRole::Admin->value,
                'is_active' => false,
            ])
            ->assertRedirect(route('projects.show', $project));

        $member->refresh();

        $this->assertSame(ProjectRole::Admin, $member->role);
        $this->assertFalse($member->is_active);
    }

    public function test_inactive_member_loses_project_access(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        ProjectMember::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->get(route('projects.show', $project))
            ->assertForbidden();
    }

    public function test_non_admin_cannot_manage_members(): void
    {
        $memberUser = User::factory()->create();
        $targetUser = User::factory()->create();
        $project = Project::factory()->create();

        ProjectMember::factory()->create([
            'project_id' => $project->id,
            'user_id' => $memberUser->id,
            'role' => ProjectRole::Member,
        ]);

        $this->actingAs($memberUser)
            ->post(route('projects.members.store', $project), [
                'user_id' => $targetUser->id,
                'role' => ProjectRole::Viewer->value,
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
