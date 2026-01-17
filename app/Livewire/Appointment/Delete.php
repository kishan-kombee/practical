<?php

namespace App\Livewire\Appointment;

use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class Delete extends Component
{
    public $selectedAppointmentIds = [];

    public $tableName;

    public bool $showModal = false;

    public bool $isBulkDelete = false;

    public int $selectedAppointmentCount = 0;

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
        $this->selectedAppointmentIds = [];

        // Fetch the ids of the roles that match the given IDs and organization ID
        $appointmentIds = Appointment::whereIn('id', $ids)
            ->pluck('id')
            ->toArray();

        if (! empty($appointmentIds)) {
            $this->selectedAppointmentIds = $ids;

            $this->selectedAppointmentCount = count($this->selectedAppointmentIds);
            $this->isBulkDelete = $this->selectedAppointmentCount > 1;

            // Get user name for single delete
            if (! $this->isBulkDelete) {
                $this->message = __('messages.appointment.messages.delete_confirmation_text');
            } else {
                $this->message = __('messages.appointment.messages.bulk_delete_confirmation_text', ['count' => count($this->selectedAppointmentIds)]);
            }

            $this->showModal = true;
        } else {
            // If no roles were found, show an error message
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => __('messages.appointment.delete.record_not_found'),
            ]);
        }
    }

    public function confirmDelete()
    {
        if (! empty($this->selectedAppointmentIds)) {
            $userId = Auth::id();
            // Load user with role relationship to avoid repeated queries
            $user = $userId ? \App\Models\User::with('role')->find($userId) : null;

            // Use Policy to check authorization: Clinicians can only delete their own appointments
            if ($user) {
                $appointments = Appointment::whereIn('id', $this->selectedAppointmentIds)->get();
                foreach ($appointments as $appointment) {
                    if (! $user->can('delete', $appointment)) {
                        $this->dispatch('alert', type: 'error', message: __('messages.appointment.messages.can_only_delete_own'));
                        return;
                    }
                }
            }

            // Proceed with deletion of selected appointment
            Appointment::whereIn('id', $this->selectedAppointmentIds)->delete();

            session()->flash('success', __('messages.appointment.messages.delete'));

            return $this->redirect(route('appointment.index'), navigate: true);
        } else {
            $this->dispatch('alert', type: 'error', message: __('messages.user.messages.record_not_found'));
        }
    }

    public function hideModal()
    {
        $this->showModal = false;
        $this->selectedAppointmentIds = [];
        $this->selectedAppointmentCount = 0;
        $this->isBulkDelete = false;
        $this->userName = '';
    }

    public function render()
    {
        return view('livewire.appointment.delete');
    }
}
