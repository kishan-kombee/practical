<?php

namespace App\Http\Resources;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
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
            'last_login_at' => $this->last_login_at ? (is_string($this->last_login_at) ? Carbon::parse($this->last_login_at)->format(config('constants.api_datetime_format')) : $this->last_login_at->format(config('constants.api_datetime_format'))) : '',
        ];
    }
}
