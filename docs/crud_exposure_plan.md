# CRUD Exposure Plan

Plan for exposing CRUD around the first backend slice: `projects`, `project_members`, and `states`.

Goal: use boring Laravel defaults, avoid boilerplate, and keep Inertia-friendly web routes. No repositories, custom service layer, API-only layer, or extra pages until the code actually needs them.

## Scope

Build backend CRUD for:

- Projects
- Project members
- Project states

Out of scope for this iteration:

- Issues, sprints, labels, comments
- Board view
- Full UI implementation
- Separate create/edit Inertia pages for every resource
- Repositories or generic CRUD abstractions

## Route Shape

Use `routes/web.php` behind `auth` and `verified` middleware.

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('projects', ProjectController::class)
        ->except(['create', 'edit']);

    Route::resource('projects.members', ProjectMemberController::class)
        ->except(['create', 'edit', 'show'])
        ->scoped();

    Route::resource('projects.states', StateController::class)
        ->except(['create', 'edit', 'show'])
        ->scoped();

    Route::post('projects/{project}/states/reorder', [StateController::class, 'reorder'])
        ->name('projects.states.reorder');

    Route::post('projects/{project}/states/{state}/default', [StateController::class, 'default'])
        ->name('projects.states.default')
        ->scopeBindings();
});
```

Why no `create` / `edit` routes: Inertia can handle form state inside list/detail pages. The server needs render endpoints and mutation endpoints, not a page per form.

## Controllers

### `ProjectController`

Methods:

```text
index
store
show
update
destroy
```

Responsibilities:

- List projects visible to the authenticated user.
- Create a project.
- On project creation:
  - make current user the project lead
  - add current user as admin member
  - create default states
  - set the default state
- Show project detail/settings data.
- Update project metadata.
- Delete/archive project.

Start with inline transaction logic in `store`. Extract an action later only if it gets noisy.

### `ProjectMemberController`

Methods:

```text
store
update
destroy
```

Responsibilities:

- Add user to project.
- Update role.
- Update active status.
- Remove member.

Rules:

- Only project admins can manage members.
- A user can only be added once per project.
- `is_active = false` suspends access without deleting history.
- Issue reassignment on member deletion waits until issues exist.

### `StateController`

Methods:

```text
store
update
destroy
reorder
default
```

Responsibilities:

- Add workflow state.
- Update workflow state.
- Delete workflow state.
- Reorder states by sparse `sequence` values.
- Set project default state.

Rules:

- Only project admins can manage states.
- Cannot delete the default state.
- Cannot delete a state with issues, once issues exist.
- State routes must stay project-scoped.

## Authorization

Authorization should land with the first CRUD implementation, not later. The model already has the required access data: project lead, project members, role, and active status.

Start with one policy:

```text
ProjectPolicy
```

Suggested methods:

```php
viewAny(User $user): bool
view(User $user, Project $project): bool
create(User $user): bool
update(User $user, Project $project): bool
delete(User $user, Project $project): bool
manageMembers(User $user, Project $project): bool
manageStates(User $user, Project $project): bool
```

Role behavior:

| User kind | View project | Manage project | Manage states | Manage members |
| --- | --- | --- | --- | --- |
| Project lead | yes | yes | yes | yes |
| Active admin | yes | yes | yes | yes |
| Active member | yes | no | no | no |
| Active viewer | yes | no | no | no |
| Inactive member | no | no | no | no |
| Non-member | no | no | no | no |

Current scope treats `Member` and `Viewer` the same: read-only. Later, `Member` gets issue/comment mutation rights.

Use the policy from controllers and form requests:

```php
$this->authorize('view', $project);
$this->authorize('update', $project);
$this->authorize('manageMembers', $project);
$this->authorize('manageStates', $project);
```

Why one policy: members and states are project-scoped. Their permissions are really project permissions.

Do not add `ProjectMemberPolicy`, `StatePolicy`, or named gates yet unless the rules become resource-specific.

A small `Project` helper is acceptable if it keeps the policy readable:

```php
public function activeMembershipFor(User $user): ?ProjectMember
```

No service class for authorization.

## Validation

Use Form Requests to keep controllers short.

Suggested files:

```text
app/Http/Requests/Projects/StoreProjectRequest.php
app/Http/Requests/Projects/UpdateProjectRequest.php
app/Http/Requests/Projects/StoreProjectMemberRequest.php
app/Http/Requests/Projects/UpdateProjectMemberRequest.php
app/Http/Requests/Projects/StoreStateRequest.php
app/Http/Requests/Projects/UpdateStateRequest.php
app/Http/Requests/Projects/ReorderStatesRequest.php
```

Project rules:

```php
'name' => ['required', 'string', 'max:255', Rule::unique(Project::class)],
'identifier' => ['required', 'string', 'max:12', Rule::unique(Project::class)],
'description' => ['nullable', 'string'],
```

Project update rules should ignore the current project for unique checks.

Member rules:

```php
'user_id' => ['required', 'exists:users,id'],
'role' => ['required', Rule::enum(ProjectRole::class)],
'is_active' => ['sometimes', 'boolean'],
```

State rules:

```php
'name' => ['required', 'string', 'max:255'],
'slug' => ['nullable', 'string', 'max:255'],
'color' => ['required', 'string', 'max:32'],
'sequence' => ['required', 'integer', 'min:0'],
'group' => ['required', Rule::enum(StateGroup::class)],
'is_default' => ['sometimes', 'boolean'],
```

Reorder rules:

```php
'states' => ['required', 'array'],
'states.*.id' => ['required', 'integer'],
'states.*.sequence' => ['required', 'integer', 'min:0'],
```

## Resources / Serialization

Use API Resources for Inertia props once data needs shaping.

Suggested files:

```text
app/Http/Resources/ProjectResource.php
app/Http/Resources/ProjectMemberResource.php
app/Http/Resources/StateResource.php
```

Guidelines:

- Use resources for page props.
- Use `whenLoaded()` for relationships.
- Keep authorization, validation, and writes out of resources.
- Do not create resources for data that is not rendered yet.

## Inertia Pages Later

Keep the UI small:

```text
resources/js/pages/projects/Index.vue
resources/js/pages/projects/Show.vue
```

`Show.vue` can contain:

- project settings form
- members table/form
- states table/form

Avoid separate pages for every create/edit form unless navigation demands it.

## Tests

Add backend feature tests before wiring UI.

Suggested files:

```text
tests/Feature/Projects/ProjectPolicyTest.php
tests/Feature/Projects/ProjectCrudTest.php
tests/Feature/Projects/ProjectMemberCrudTest.php
tests/Feature/Projects/StateCrudTest.php
```

### Policy tests

- Project lead can view/update/delete/manage members/manage states.
- Active admin can view/update/delete/manage members/manage states.
- Active member can view only.
- Active viewer can view only.
- Inactive member cannot view or mutate.
- Non-member cannot view or mutate.

### Project tests

- Authenticated user can create a project.
- Project creation creates default states.
- Project creation makes creator admin member.
- Identifier normalizes uppercase.
- Guest cannot create project.
- Non-member cannot view project.
- Project admin can update/delete project.

### Member tests

- Project admin can add a member.
- Duplicate member fails.
- Project admin can change role.
- Project admin can deactivate member.
- Inactive member loses project access.
- Non-admin cannot manage members.

### State tests

- Project admin can create/update/delete state.
- Cannot delete default state.
- Can set default state.
- Can reorder states.
- Non-admin cannot manage states.

## Suggested Build Order

### 1. Project authorization

Files:

```text
ProjectPolicy
ProjectPolicyTest
```

This locks in role/ownership rules before CRUD methods start depending on them.

### 2. Project CRUD

Files:

```text
ProjectController
StoreProjectRequest
UpdateProjectRequest
ProjectResource
ProjectCrudTest
```

This unlocks project creation and default states.

### 3. State CRUD

Files:

```text
StateController
StoreStateRequest
UpdateStateRequest
ReorderStatesRequest
StateResource
StateCrudTest
```

This unlocks workflow management.

### 4. Member CRUD

Files:

```text
ProjectMemberController
StoreProjectMemberRequest
UpdateProjectMemberRequest
ProjectMemberResource
ProjectMemberCrudTest
```

This unlocks team permissions.

## Deliberate Simplifications

- No repository layer.
- No generic CRUD abstraction.
- No service/action class until controller logic gets hard to read.
- No separate policies for states or project members yet.
- No issue-related member deletion behavior until issues exist.
- No separate create/edit Inertia pages yet.
