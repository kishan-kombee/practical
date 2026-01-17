<?php

namespace App\Livewire\Role;

use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Show extends Component
{
    public $id;

    public $role;

    public function mount($id)
    {
        $this->role = null;

        $this->role = Role::select(
            'roles.id',
            'roles.name',
            DB::raw(
                '(CASE
                                        WHEN roles.status = "' . config('constants.role.status.key.active') . '" THEN  "' . config('constants.role.status.value.active') . '"
                                        WHEN roles.status = "' . config('constants.role.status.key.inactive') . '" THEN  "' . config('constants.role.status.value.inactive') . '"
                                ELSE " "
                                END) AS status'
            )
        )

            ->where('roles.id', $id)

            ->first();

        if (is_null($this->role)) {
            session()->flash('error', __('messages.role.messages.record_not_found'));
        }
    }

    public function render()
    {
        return view('livewire.role.show');
    }
}
