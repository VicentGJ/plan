<?php

namespace App\Http\Requests\Projects;

use App\Enums\StateGroup;
use App\Models\Project;
use App\Models\State;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateStateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageStates', $this->route('project')) === true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('name') && ! $this->filled('slug')) {
            $this->merge(['slug' => Str::slug((string) $this->input('name'))]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Project $project */
        $project = $this->route('project');
        /** @var State $state */
        $state = $this->route('state');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('states', 'name')->where('project_id', $project->id)->ignore($state)],
            'slug' => ['required', 'string', 'max:255', Rule::unique('states', 'slug')->where('project_id', $project->id)->ignore($state)],
            'color' => ['required', 'string', 'max:32'],
            'sequence' => ['required', 'integer', 'min:0'],
            'group' => ['required', Rule::enum(StateGroup::class)],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
