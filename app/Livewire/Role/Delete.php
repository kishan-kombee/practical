<?php

namespace App\Livewire\Role;

use App\Models\Role;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use Livewire\Component;

class Delete extends Component
{
    public $selectedRoleIds = [];

    public $tableName;

    public bool $showModal = false;

    public bool $isBulkDelete = false;

    public int $selectedRoleCount = 0;

    public string $userName = '';

    public $message;

    #[On('delete-confirmation')]
    public function deleteConfirmation($ids, $tableName)
    {
        $this->handleDeleteConfirmation($ids, $tableName);
    }

    #[On('bulk-delete-confirmation')]
    public function bulkDeleteConfirmation($data)
    {
        $ids = $data['ids'] ?? [];
        $tableName = $data['tableName'] ?? '';
        $this->handleDeleteConfirmation($ids, $tableName);
    }

    #[On('delete-confirmation')]
    public function handleDeleteConfirmation($ids, $tableName)
    {
        // Initialize table name and reset selected ids
        $this->tableName = $tableName;
        $this->selectedRoleIds = [];

        // Fetch the ids of the roles that match the given IDs and organization ID
        $roleIds = Role::whereIn('id', $ids)
            ->pluck('id')
            ->toArray();

        if (! empty($roleIds)) {
            $this->selectedRoleIds = $ids;

            $this->selectedRoleCount = count($this->selectedRoleIds);
            $this->isBulkDelete = $this->selectedRoleCount > 1;

            // Get user name for single delete
            if (! $this->isBulkDelete) {
                $this->message = __('messages.role.messages.delete_confirmation_text');
            } else {
                $this->message = __('messages.role.messages.bulk_delete_confirmation_text', ['count' => count($this->selectedRoleIds)]);
            }

            $this->showModal = true;
        } else {
            // If no roles were found, show an error message
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => __('messages.role.delete.record_not_found'),
            ]);
        }
    }

    public function confirmDelete()
    {
        if (! empty($this->selectedRoleIds)) {
            // Proceed with deletion of selected role
            Role::whereIn('id', $this->selectedRoleIds)->delete();
            Cache::forget('getAllRole');
            session()->flash('success', __('messages.role.messages.delete'));

            return $this->redirect(route('role.index'), navigate: true);
        } else {
            $this->dispatch('alert', type: 'error', message: __('messages.user.messages.record_not_found'));
        }
    }

    public function hideModal()
    {
        $this->showModal = false;
        $this->selectedRoleIds = [];
        $this->selectedRoleCount = 0;
        $this->isBulkDelete = false;
        $this->userName = '';
    }

    public function render()
    {
        return view('livewire.role.delete');
    }
}
