<?php

namespace App\Http\Requests\Projects;

use App\Models\Project;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderStatesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageStates', $this->route('project')) === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Project $project */
        $project = $this->route('project');

        return [
            'states' => ['required', 'array'],
            'states.*.id' => ['required', 'integer', 'distinct', Rule::exists('states', 'id')->where('project_id', $project->id)],
            'states.*.sequence' => ['required', 'integer', 'min:0'],
        ];
    }
}
