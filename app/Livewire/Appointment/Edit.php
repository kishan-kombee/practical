<?php

namespace App\Livewire\Appointment;

use App\Helper;
use App\Livewire\Breadcrumb;
use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Symfony\Component\HttpFoundation\Response;

class Edit extends Component
{

    public $appointment;

    public $id;

    public $patient_name;

    public $clinic_location;

    public $clinician_id;

    public $clinicians = [];

    public $appointment_date;

    public $status = 'B';

    public $isAdmin = false;

    public function mount($id)
    {
        $user = Auth::user();
        
        // Use Policy for authorization (with Gate as fallback)
        if ($user) {
            if (! $user->can('update', Appointment::class)) {
                // Fallback to Gate check for backward compatibility
                if (! Gate::allows('edit-appointment')) {
                    abort(Response::HTTP_FORBIDDEN);
                }
            }
        } else {
            // Fallback to Gate check if user is not authenticated
            if (! Gate::allows('edit-appointment')) {
                abort(Response::HTTP_FORBIDDEN);
            }
        }

        /* begin::Set breadcrumb */
        $segmentsData = [
            'title' => __('messages.appointment.breadcrumb.title'),
            'item_1' => '<a href="/appointment" class="text-muted text-hover-primary" wire:navigate>' . __('messages.appointment.breadcrumb.appointment') . '</a>',
            'item_2' => __('messages.appointment.breadcrumb.edit'),
        ];
        $this->dispatch('breadcrumbList', $segmentsData)->to(Breadcrumb::class);
        /* end::Set breadcrumb */

        $this->appointment = Appointment::find($id);

        if ($this->appointment) {
            // Use Policy to check authorization: Clinicians can only edit their own appointments
            if ($user) {
                $user->load('role');
            }
            $this->isAdmin = $user && $user->isAdmin();

            // Use Policy authorization
            if ($user && ! $user->can('update', $this->appointment)) {
                abort(Response::HTTP_FORBIDDEN, __('messages.appointment.messages.can_only_edit_own'));
            }

            foreach ($this->appointment->getAttributes() as $key => $value) {
                $this->{$key} = $value; // Dynamically assign the attributes to the class
            }

            // Load clinicians (active users)
            $this->clinicians = Helper::getAllActiveClinicians();
        } else {
            abort(Response::HTTP_NOT_FOUND);
        }
    }

    public function rules()
    {
        $rules = [
            'patient_name' => 'required|string|max:50|regex:/^[a-zA-Z\\s]+$/',
            'clinic_location' => 'required|string|max:200',
            'clinician_id' => 'required|exists:users,id,deleted_at,NULL,status,Y',
            'appointment_date' => 'required|date',
            'status' => 'required|in:B,D,N',
        ];

        return $rules;
    }

    public function messages()
    {
        return [
            'patient_name.required' => __('messages.appointment.validation.messsage.patient_name.required'),
            'patient_name.in' => __('messages.appointment.validation.messsage.patient_name.in'),
            'patient_name.max' => __('messages.appointment.validation.messsage.patient_name.max'),
            'clinic_location.required' => __('messages.appointment.validation.messsage.clinic_location.required'),
            'clinic_location.in' => __('messages.appointment.validation.messsage.clinic_location.in'),
            'clinic_location.max' => __('messages.appointment.validation.messsage.clinic_location.max'),
            'clinician_id.required' => __('messages.appointment.validation.messsage.clinician_id.required'),
            'clinician_id.exists' => __('messages.appointment.validation.messsage.clinician_id.exists'),
            'appointment_date.required' => __('messages.appointment.validation.messsage.appointment_date.required'),
            'status.required' => __('messages.appointment.validation.messsage.status.required'),
            'status.in' => __('messages.appointment.validation.messsage.status.in'),
        ];
    }

    public function store()
    {
        $this->validate();

        // Use Policy to ensure non-admins can only update their own appointments and can't change clinician
        $userId = Auth::id();
        $user = $userId ? \App\Models\User::with('role')->find($userId) : null;
        
        // Use Policy authorization
        if ($user && ! $user->can('update', $this->appointment)) {
            abort(Response::HTTP_FORBIDDEN, __('messages.appointment.messages.can_only_update_own'));
        }

        // Additional check: Non-admins cannot change the clinician_id
        if ($user && !$user->isAdmin()) {
            if ($this->clinician_id != $this->appointment->clinician_id) {
                session()->flash('error', __('messages.appointment.messages.cannot_change_clinician'));
                return;
            }
        }

        $data = [
            'patient_name' => $this->patient_name,
            'clinic_location' => $this->clinic_location,
            'clinician_id' => $this->clinician_id,
            'appointment_date' => $this->appointment_date,
            'status' => $this->status,
        ];
        $this->appointment->update($data); // Update data into the DB

        session()->flash('success', __('messages.appointment.messages.update'));

        return $this->redirect('/appointment', navigate: true); // redirect to appointment listing page
    }

    public function render()
    {
        return view('livewire.appointment.edit')->title(__('messages.meta_title.edit_appointment'));
    }
}
