<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'identifier' => $this->identifier,
            'description' => $this->description,
            'lead_id' => $this->lead_id,
            'default_state_id' => $this->default_state_id,
            'archived_at' => $this->archived_at,
            'lead' => UserResource::make($this->whenLoaded('lead')),
            'members' => ProjectMemberResource::collection($this->whenLoaded('members')),
            'states' => StateResource::collection($this->whenLoaded('states')),
        ];
    }
}
