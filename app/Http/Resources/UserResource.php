<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin User */
class UserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('roles.permissions');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->primaryRole(),
            'identity_number' => $this->identity_number,
            'phone' => $this->phone,
            'bio' => $this->bio,
            'avatar_url' => $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null,
            'is_active' => (bool) $this->is_active,
            'permissions' => $this->roles->flatMap->permissions->pluck('name')->unique()->values()->all(),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
        ];
    }
}
