<?php

namespace Tests\Feature\Projects;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_lead_can_manage_project(): void
    {
        $lead = User::factory()->create();
        $project = Project::factory()->create(['lead_id' => $lead->id]);

        $this->assertTrue($lead->can('view', $project));
        $this->assertTrue($lead->can('update', $project));
        $this->assertTrue($lead->can('delete', $project));
        $this->assertTrue($lead->can('manageMembers', $project));
        $this->assertTrue($lead->can('manageStates', $project));
    }

    public function test_active_admin_can_manage_project(): void
    {
        $admin = User::factory()->create();
        $project = Project::factory()->create();

        ProjectMember::factory()->create([
            'project_id' => $project->id,
            'user_id' => $admin->id,
            'role' => ProjectRole::Admin,
        ]);

        $this->assertTrue($admin->can('view', $project));
        $this->assertTrue($admin->can('update', $project));
        $this->assertTrue($admin->can('delete', $project));
        $this->assertTrue($admin->can('manageMembers', $project));
        $this->assertTrue($admin->can('manageStates', $project));
    }

    public function test_active_member_can_only_view_project(): void
    {
        $member = User::factory()->create();
        $project = Project::factory()->create();

        ProjectMember::factory()->create([
            'project_id' => $project->id,
            'user_id' => $member->id,
            'role' => ProjectRole::Member,
        ]);

        $this->assertTrue($member->can('view', $project));
        $this->assertFalse($member->can('update', $project));
        $this->assertFalse($member->can('delete', $project));
        $this->assertFalse($member->can('manageMembers', $project));
        $this->assertFalse($member->can('manageStates', $project));
    }

    public function test_active_viewer_can_only_view_project(): void
    {
        $viewer = User::factory()->create();
        $project = Project::factory()->create();

        ProjectMember::factory()->create([
            'project_id' => $project->id,
            'user_id' => $viewer->id,
            'role' => ProjectRole::Viewer,
        ]);

        $this->assertTrue($viewer->can('view', $project));
        $this->assertFalse($viewer->can('update', $project));
        $this->assertFalse($viewer->can('delete', $project));
        $this->assertFalse($viewer->can('manageMembers', $project));
        $this->assertFalse($viewer->can('manageStates', $project));
    }

    public function test_inactive_member_cannot_view_or_manage_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        ProjectMember::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => ProjectRole::Admin,
            'is_active' => false,
        ]);

        $this->assertFalse($user->can('view', $project));
        $this->assertFalse($user->can('update', $project));
        $this->assertFalse($user->can('delete', $project));
        $this->assertFalse($user->can('manageMembers', $project));
        $this->assertFalse($user->can('manageStates', $project));
    }

    public function test_non_member_cannot_view_or_manage_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $this->assertFalse($user->can('view', $project));
        $this->assertFalse($user->can('update', $project));
        $this->assertFalse($user->can('delete', $project));
        $this->assertFalse($user->can('manageMembers', $project));
        $this->assertFalse($user->can('manageStates', $project));
    }
}
