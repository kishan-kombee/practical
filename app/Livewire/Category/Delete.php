<?php

namespace App\Livewire\Category;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use Livewire\Component;

class Delete extends Component
{
    public $selectedCategoryIds = [];

    public $tableName;

    public bool $showModal = false;

    public bool $isBulkDelete = false;

    public int $selectedCategoryCount = 0;

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
        $this->selectedCategoryIds = [];

        // Fetch the ids of the roles that match the given IDs and organization ID
        $categoryIds = Category::whereIn('id', $ids)
            ->pluck('id')
            ->toArray();

        if (! empty($categoryIds)) {
            $this->selectedCategoryIds = $ids;

            $this->selectedCategoryCount = count($this->selectedCategoryIds);
            $this->isBulkDelete = $this->selectedCategoryCount > 1;

            // Get user name for single delete
            if (! $this->isBulkDelete) {
                $this->message = __('messages.category.messages.delete_confirmation_text');
            } else {
                $this->message = __('messages.category.messages.bulk_delete_confirmation_text', ['count' => count($this->selectedCategoryIds)]);
            }

            $this->showModal = true;
        } else {
            // If no roles were found, show an error message
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => __('messages.category.delete.record_not_found'),
            ]);
        }
    }

    public function confirmDelete()
    {
        if (! empty($this->selectedCategoryIds)) {
            // Proceed with deletion of selected category
            Category::whereIn('id', $this->selectedCategoryIds)->delete();
            Cache::forget('getAllCategory');
            Cache::forget('getAllActiveCategory');
            session()->flash('success', __('messages.category.messages.delete'));

            return $this->redirect(route('category.index'), navigate: true);
        } else {
            $this->dispatch('alert', type: 'error', message: __('messages.user.messages.record_not_found'));
        }
    }

    public function hideModal()
    {
        $this->showModal = false;
        $this->selectedCategoryIds = [];
        $this->selectedCategoryCount = 0;
        $this->isBulkDelete = false;
        $this->userName = '';
    }

    public function render()
    {
        return view('livewire.category.delete');
    }
}
