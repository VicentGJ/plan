<?php

namespace App\Http\Requests\Projects;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageMembers', $this->route('project')) === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Project $project */
        $project = $this->route('project');

        return [
            'user_id' => [
                'required',
                Rule::exists(User::class, 'id'),
                Rule::unique('project_members', 'user_id')->where('project_id', $project->id),
            ],
            'role' => ['required', Rule::enum(ProjectRole::class)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
