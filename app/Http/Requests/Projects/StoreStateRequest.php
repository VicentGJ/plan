<?php

namespace App\Http\Requests\Projects;

use App\Enums\StateGroup;
use App\Models\Project;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreStateRequest extends FormRequest
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

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('states', 'name')->where('project_id', $project->id)],
            'slug' => ['required', 'string', 'max:255', Rule::unique('states', 'slug')->where('project_id', $project->id)],
            'color' => ['required', 'string', 'max:32'],
            'sequence' => ['required', 'integer', 'min:0'],
            'group' => ['required', Rule::enum(StateGroup::class)],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
