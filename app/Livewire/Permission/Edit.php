<?php

namespace App\Livewire\Permission;

use App\Helper;
use App\Models\PermissionRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Symfony\Component\HttpFoundation\Response;

class Edit extends Component
{
    public $currentUser;

    public $roles = [];

    public $role;

    public $getAllPermissions = [];

    public $showAllPermissions = false;

    public $rootPermissionId = null;

    public function mount()
    {
        $this->currentUser = Auth::user();
        if ($this->currentUser->role_id != config('constants.roles.admin')) {
            abort(Response::HTTP_NOT_FOUND);
        }

        /* begin::Set breadcrumb */
        $segmentsData = [
            'title' => __('messages.permission.breadcrumb.title'),
            'item_1' => __('messages.permission.breadcrumb.sub_title'),
            'item_2' => __('messages.permission.breadcrumb.edit'),
        ];

        $this->dispatch('breadcrumbList', $segmentsData)->to('breadcrumb');

        $this->roles = Helper::getAllRoles();
    }

    public function updatedRole()
    {
        $this->showAllPermissions = true;
        $this->getAllPermissions = self::preparePermissionArray($this->role);
    }

    public function setUnsetPermission($permissionId, $isChecked, $rootPermissionId)
    {
        $this->rootPermissionId = $rootPermissionId;

        self::commonCodePermissionRole($isChecked, $this->role, $permissionId);

        // Clear the cache for the affected role
        Cache::forget("getCachedPermissionsByRole:$this->role");
        $this->dispatch('toast', [
            'type' => 'success',
            'message' => __('messages.common_message.change_success'),
        ]);
    }

    public function commonCodePermissionRole($checkValue, $roleId, $permissionId)
    {
        $permissionRole = PermissionRole::where('role_id', $roleId)
            ->where('permission_id', $permissionId)->first();

        if ($checkValue) {
            if (is_null($permissionRole)) {
                PermissionRole::insert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ]);
            }
        } else {
            PermissionRole::where('role_id', $roleId)
                ->where('permission_id', $permissionId)->delete();
        }
    }

    public function render()
    {
        return view('livewire.permission.edit')->title(__('messages.meta_titles.assign_permission'));
    }

    public static function preparePermissionArray($roleId)
    {
        $allPermissions = collect(Helper::getAllPermissions());
        $rolePermission = PermissionRole::where('role_id', $roleId)->orderBy('permission_id')->pluck('permission_id')->toArray();

        $rootPermissions = $allPermissions->where('guard_name', 'root')->toArray();
        $otherPermissions = $allPermissions->where('guard_name', '!=', 'root');

        foreach ($rootPermissions as $i => $rootPermission) {
            $sub_permissions = $otherPermissions->where('guard_name', $rootPermission['name'])->toArray();
            foreach ($sub_permissions as $j => $subPermission) {
                $sub_permissions[$j]['is_permission'] = in_array($subPermission['id'], $rolePermission);
            }
            $rootPermissions[$i]['sub_permissions'] = $sub_permissions;
        }

        return $rootPermissions;
    }
}
