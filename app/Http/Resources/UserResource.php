<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->id,
            'name'  => $this->name,
            'email' => $this->email,

            // whenLoaded — only included if explicitly eager-loaded
            /*'roles' => $this->whenLoaded(
                'roles',
                fn() => $this->roles->pluck('name'),
            )*/
            'role' => $this->whenLoaded('roles', function() {
                return $this->roles->first()->name;
            }),
            'permissions' => $this->whenLoaded(
                'permissions',
                fn() => $this->getAllPermissions()->pluck('name'),
            ),
            'email_verified' => ! is_null($this->email_verified_at)
        ];
    }
}
