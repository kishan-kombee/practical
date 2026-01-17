<?php

namespace App\Livewire\SmsTemplate;

use App\Models\SmsTemplate;
use Livewire\Attributes\On;
use Livewire\Component;

class Delete extends Component
{
    public $selectedSmsTemplateIds = [];

    public $tableName;

    public bool $showModal = false;

    public bool $isBulkDelete = false;

    public int $selectedSmsTemplateCount = 0;

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
        $this->selectedSmsTemplateIds = [];

        // Fetch the ids of the roles that match the given IDs and organization ID
        $smstemplateIds = SmsTemplate::whereIn('id', $ids)
            ->pluck('id')
            ->toArray();

        if (! empty($smstemplateIds)) {
            $this->selectedSmsTemplateIds = $ids;

            $this->selectedSmsTemplateCount = count($this->selectedSmsTemplateIds);
            $this->isBulkDelete = $this->selectedSmsTemplateCount > 1;

            // Get user name for single delete
            if (! $this->isBulkDelete) {
                $this->message = __('messages.sms_template.messages.delete_confirmation_text');
            } else {
                $this->message = __('messages.sms_template.messages.bulk_delete_confirmation_text', ['count' => count($this->selectedSmsTemplateIds)]);
            }

            $this->showModal = true;
        } else {
            // If no roles were found, show an error message
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => __('messages.sms_template.delete.record_not_found'),
            ]);
        }
    }

    public function confirmDelete()
    {
        if (! empty($this->selectedSmsTemplateIds)) {
            // Proceed with deletion of selected sms-template
            SmsTemplate::whereIn('id', $this->selectedSmsTemplateIds)->delete();

            session()->flash('success', __('messages.sms_template.messages.delete'));

            return $this->redirect(route('sms-template.index'), navigate: true);
        } else {
            $this->dispatch('alert', type: 'error', message: __('messages.user.messages.record_not_found'));
        }
    }

    public function hideModal()
    {
        $this->showModal = false;
        $this->selectedSmsTemplateIds = [];
        $this->selectedSmsTemplateCount = 0;
        $this->isBulkDelete = false;
        $this->userName = '';
    }

    public function render()
    {
        return view('livewire.sms-template.delete');
    }
}
