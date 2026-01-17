<?php

namespace App\Livewire\Product;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use Livewire\Component;

class Delete extends Component
{
    public $selectedProductIds = [];

    public $tableName;

    public bool $showModal = false;

    public bool $isBulkDelete = false;

    public int $selectedProductCount = 0;

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
        $this->selectedProductIds = [];

        // Fetch the ids of the roles that match the given IDs and organization ID
        $productIds = Product::whereIn('id', $ids)
            ->pluck('id')
            ->toArray();

        if (! empty($productIds)) {
            $this->selectedProductIds = $ids;

            $this->selectedProductCount = count($this->selectedProductIds);
            $this->isBulkDelete = $this->selectedProductCount > 1;

            // Get user name for single delete
            if (! $this->isBulkDelete) {
                $this->message = __('messages.product.messages.delete_confirmation_text');
            } else {
                $this->message = __('messages.product.messages.bulk_delete_confirmation_text', ['count' => count($this->selectedProductIds)]);
            }

            $this->showModal = true;
        } else {
            // If no roles were found, show an error message
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => __('messages.product.delete.record_not_found'),
            ]);
        }
    }

    public function confirmDelete()
    {
        if (! empty($this->selectedProductIds)) {
            // Proceed with deletion of selected product
            Product::whereIn('id', $this->selectedProductIds)->delete();
            Cache::forget('getAllProduct');
            Cache::forget('getAllActiveSubCategory');
            session()->flash('success', __('messages.product.messages.delete'));

            return $this->redirect(route('product.index'), navigate: true);
        } else {
            $this->dispatch('alert', type: 'error', message: __('messages.user.messages.record_not_found'));
        }
    }

    public function hideModal()
    {
        $this->showModal = false;
        $this->selectedProductIds = [];
        $this->selectedProductCount = 0;
        $this->isBulkDelete = false;
        $this->userName = '';
    }

    public function render()
    {
        return view('livewire.product.delete');
    }
}
