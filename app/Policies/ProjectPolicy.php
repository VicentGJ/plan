<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        return $this->isLead($user, $project) || $project->hasActiveMembership($user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Project $project): bool
    {
        return $this->managesProject($user, $project);
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->managesProject($user, $project);
    }

    public function manageMembers(User $user, Project $project): bool
    {
        return $this->managesProject($user, $project);
    }

    public function manageStates(User $user, Project $project): bool
    {
        return $this->managesProject($user, $project);
    }

    private function managesProject(User $user, Project $project): bool
    {
        return $this->isLead($user, $project) || $project->hasActiveAdmin($user);
    }

    private function isLead(User $user, Project $project): bool
    {
        return $project->lead_id === $user->id;
    }
}
