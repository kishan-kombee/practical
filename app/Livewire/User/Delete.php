<?php

namespace App\Livewire\User;

use App\Models\User;
use Livewire\Attributes\On;
use Livewire\Component;

class Delete extends Component
{
    public $selectedUserIds = [];

    public $tableName;

    public bool $showModal = false;

    public bool $isBulkDelete = false;

    public int $selectedUserCount = 0;

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
        $this->selectedUserIds = [];

        // Fetch the ids of the roles that match the given IDs and organization ID
        $userIds = User::whereIn('id', $ids)
            ->pluck('id')
            ->toArray();

        if (! empty($userIds)) {
            $this->selectedUserIds = $ids;

            $this->selectedUserCount = count($this->selectedUserIds);
            $this->isBulkDelete = $this->selectedUserCount > 1;

            // Get user name for single delete
            if (! $this->isBulkDelete) {
                $this->message = __('messages.user.messages.delete_confirmation_text');
            } else {
                $this->message = __('messages.user.messages.bulk_delete_confirmation_text', ['count' => count($this->selectedUserIds)]);
            }

            $this->showModal = true;
        } else {
            // If no roles were found, show an error message
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => __('messages.user.delete.record_not_found'),
            ]);
        }
    }

    public function confirmDelete()
    {
        if (! empty($this->selectedUserIds)) {
            // Proceed with deletion of selected user
            User::whereIn('id', $this->selectedUserIds)->delete();

            session()->flash('success', __('messages.user.messages.delete'));

            return $this->redirect(route('user.index'), navigate: true);
        } else {
            $this->dispatch('alert', type: 'error', message: __('messages.user.messages.record_not_found'));
        }
    }

    public function hideModal()
    {
        $this->showModal = false;
        $this->selectedUserIds = [];
        $this->selectedUserCount = 0;
        $this->isBulkDelete = false;
        $this->userName = '';
    }

    public function render()
    {
        return view('livewire.user.delete');
    }
}
