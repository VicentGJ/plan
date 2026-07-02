<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Enums\StateGroup;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectWorkflowModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_member_and_state_models_match_the_workflow_schema(): void
    {
        $lead = User::factory()->create();
        $member = User::factory()->create();

        $project = Project::factory()->create([
            'identifier' => 'web',
            'lead_id' => $lead->id,
        ]);

        $state = State::factory()->create([
            'project_id' => $project->id,
            'name' => 'Todo',
            'slug' => '',
            'sequence' => 2000,
            'group' => StateGroup::Unstarted,
            'is_default' => true,
        ]);

        $project->update(['default_state_id' => $state->id]);

        $projectMember = ProjectMember::factory()->create([
            'project_id' => $project->id,
            'user_id' => $member->id,
            'role' => ProjectRole::Admin,
        ]);

        $this->assertSame('WEB', $project->refresh()->identifier);
        $this->assertTrue($project->lead->is($lead));
        $this->assertTrue($project->defaultState->is($state));
        $this->assertTrue($project->members->first()->is($projectMember));
        $this->assertTrue($project->states->first()->is($state));
        $this->assertSame('todo', $state->refresh()->slug);
        $this->assertSame(StateGroup::Unstarted, $state->group);
        $this->assertTrue($state->is_default);
        $this->assertSame(ProjectRole::Admin, $projectMember->role);
        $this->assertTrue($projectMember->is_active);
    }
}
