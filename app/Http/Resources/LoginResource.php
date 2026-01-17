<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 */
class LoginResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id ?? '',
            'role_id' => $this->role_id ?? '',
            'first_name' => $this->first_name ?? '',
            'last_name' => $this->last_name ?? '',
            'email' => $this->email ?? '',
            'mobile_number' => $this->mobile_number ?? '',
            'status' => $this->status ?? '',
            'status_text' => $this->status === 'Y' ? 'Active' : 'Inactive',
            'last_login_at' => $this->last_login_at ? (is_string($this->last_login_at) ? \Carbon\Carbon::parse($this->last_login_at)->format(config('constants.api_datetime_format')) : $this->last_login_at->format(config('constants.api_datetime_format'))) : '',
            'authorization' => $this->authorization ?? null,
            'refresh_token' => $this->refresh_token ?? null,
            'token_expires_at' => $this->token_expires_at ? (is_string($this->token_expires_at) ? \Carbon\Carbon::parse($this->token_expires_at)->format(config('constants.api_datetime_format')) : $this->token_expires_at->format(config('constants.api_datetime_format'))) : '',
        ];
    }
}
