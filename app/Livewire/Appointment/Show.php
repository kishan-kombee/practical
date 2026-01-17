<?php

namespace App\Livewire\Appointment;

use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class Show extends Component
{
    public $appointment;

    public $event = 'showappointmentInfoModal';

    #[On('show-appointment-info')]
    public function show($id)
    {
        $this->appointment = null;

        $appointment = Appointment::select(
            'appointments.id',
            'appointments.patient_name',
            'appointments.clinic_location',
            'appointments.clinician_id',
            'appointments.appointment_date',
            'users.first_name as clinician_first_name',
            'users.last_name as clinician_last_name',
            DB::raw('CONCAT(users.first_name, " ", users.last_name) as clinician_name'),
            DB::raw(
                '(CASE
                                        WHEN appointments.status = "' . config('constants.appointment.status.key.booked') . '" THEN  "' . config('constants.appointment.status.value.booked') . '"
                                        WHEN appointments.status = "' . config('constants.appointment.status.key.completed') . '" THEN  "' . config('constants.appointment.status.value.completed') . '"
                                        WHEN appointments.status = "' . config('constants.appointment.status.key.cancelled') . '" THEN  "' . config('constants.appointment.status.value.cancelled') . '"
                                ELSE " "
                                END) AS status'
            )
        )
            ->leftJoin('users', 'users.id', '=', 'appointments.clinician_id')
            ->where('appointments.id', $id)
            ->first();

        // Use Policy to check authorization: Clinicians can only view their own appointments
        if ($appointment) {
            $userId = Auth::id();
            $user = $userId ? \App\Models\User::with('role')->find($userId) : null;

            // Convert to Appointment model instance for policy check
            $appointmentModel = Appointment::find($appointment->id);
            if ($appointmentModel && $user && ! $user->can('view', $appointmentModel)) {
                session()->flash('error', __('messages.appointment.messages.unauthorized_access'));
                return;
            }

            $this->appointment = $appointment;
            $this->dispatch('show-modal', id: '#' . $this->event);
        } else {
            session()->flash('error', __('messages.appointment.messages.record_not_found'));
        }
    }

    public function render()
    {
        return view('livewire.appointment.show');
    }
}
