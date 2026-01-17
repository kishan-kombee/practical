<?php

namespace App\Livewire\SubCategory;

use App\Models\SubCategory;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use Livewire\Component;

class Delete extends Component
{
    public $selectedSubCategoryIds = [];

    public $tableName;

    public bool $showModal = false;

    public bool $isBulkDelete = false;

    public int $selectedSubCategoryCount = 0;

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
        $this->selectedSubCategoryIds = [];

        // Fetch the ids of the roles that match the given IDs and organization ID
        $subcategoryIds = SubCategory::whereIn('id', $ids)
            ->pluck('id')
            ->toArray();

        if (! empty($subcategoryIds)) {
            $this->selectedSubCategoryIds = $ids;

            $this->selectedSubCategoryCount = count($this->selectedSubCategoryIds);
            $this->isBulkDelete = $this->selectedSubCategoryCount > 1;

            // Get user name for single delete
            if (! $this->isBulkDelete) {
                $this->message = __('messages.sub_category.messages.delete_confirmation_text');
            } else {
                $this->message = __('messages.sub_category.messages.bulk_delete_confirmation_text', ['count' => count($this->selectedSubCategoryIds)]);
            }

            $this->showModal = true;
        } else {
            // If no roles were found, show an error message
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => __('messages.sub_category.delete.record_not_found'),
            ]);
        }
    }

    public function confirmDelete()
    {
        if (! empty($this->selectedSubCategoryIds)) {
            // Proceed with deletion of selected sub-category
            SubCategory::whereIn('id', $this->selectedSubCategoryIds)->delete();
            Cache::forget('getAllSubCategory');
            Cache::forget('getAllActiveSubCategory');
            // Clear all category-specific sub_category caches
            $subCategories = SubCategory::whereIn('id', $this->selectedSubCategoryIds)->get();
            foreach ($subCategories as $subCategory) {
                Cache::forget("getSubCategoriesByCategory:{$subCategory->category_id}");
            }
            session()->flash('success', __('messages.sub_category.messages.delete'));

            return $this->redirect(route('sub_category.index'), navigate: true);
        } else {
            $this->dispatch('alert', type: 'error', message: __('messages.user.messages.record_not_found'));
        }
    }

    public function hideModal()
    {
        $this->showModal = false;
        $this->selectedSubCategoryIds = [];
        $this->selectedSubCategoryCount = 0;
        $this->isBulkDelete = false;
        $this->userName = '';
    }

    public function render()
    {
        return view('livewire.sub-category.delete');
    }
}
