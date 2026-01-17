<?php

namespace App\Livewire\Appointment;

use App\Helper;
use App\Livewire\Breadcrumb;
use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Symfony\Component\HttpFoundation\Response;

class Create extends Component
{
    public $patient_name;

    public $clinic_location;

    public $clinician_id;

    public $clinicians = [];

    public $appointment_date;

    public $status = 'B';

    public $isAdmin = false;

    public function mount()
    {
        $user = Auth::user();

        // Use Policy for authorization (with Gate as fallback)
        if ($user) {
            if (! $user->can('create', Appointment::class)) {
                // Fallback to Gate check for backward compatibility
                if (! Gate::allows('add-appointment')) {
                    abort(Response::HTTP_FORBIDDEN);
                }
            }
        } else {
            // Fallback to Gate check if user is not authenticated
            if (! Gate::allows('add-appointment')) {
                abort(Response::HTTP_FORBIDDEN);
            }
        }

        /* begin::Set breadcrumb */
        $segmentsData = [
            'title' => __('messages.appointment.breadcrumb.title'),
            'item_1' => '<a href="/appointment" class="text-muted text-hover-primary" wire:navigate>' . __('messages.appointment.breadcrumb.appointment') . '</a>',
            'item_2' => __('messages.appointment.breadcrumb.create'),
        ];
        $this->dispatch('breadcrumbList', $segmentsData)->to(Breadcrumb::class);
        /* end::Set breadcrumb */

        // Load clinicians (active users)
        if ($user) {
            $user->load('role');
        }
        $this->isAdmin = $user && $user->isAdmin();

        // If current user is not admin, restrict to only themselves
        if ($this->isAdmin) {
            // Admins can see all active clinicians
            $this->clinicians = Helper::getAllActiveClinicians();
        } else {
            // Non-admins can only see themselves
            if ($user) {
                $this->clinicians = [
                    $user->id => $user->first_name . ' ' . $user->last_name
                ];
                $this->clinician_id = $user->id;
            } else {
                $this->clinicians = [];
            }
        }
    }

    public function rules()
    {
        $rules = [
            'patient_name' => 'required|string|max:50|regex:/^[a-zA-Z\\s]+$/',
            'clinic_location' => 'required|string|max:200',
            'clinician_id' => 'required|exists:users,id,deleted_at,NULL,status,Y',
            'appointment_date' => 'required|date|after_or_equal:today',
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
            'appointment_date.after_or_equal' => __('messages.appointment.validation.messsage.appointment_date.after_or_equal'),
            'status.required' => __('messages.appointment.validation.messsage.status.required'),
            'status.in' => __('messages.appointment.validation.messsage.status.in'),
        ];
    }

    public function store()
    {
        // Ensure non-admins can only create appointments for themselves
        $userId = Auth::id();
        $user = $userId ? \App\Models\User::with('role')->find($userId) : null;

        // Force clinician_id to current user's ID for non-admins
        if ($user && !$user->isAdmin()) {
            $this->clinician_id = $user->id;
        }

        $this->validate();

        $data = [
            'patient_name' => $this->patient_name,
            'clinic_location' => $this->clinic_location,
            'clinician_id' => $this->clinician_id,
            'appointment_date' => $this->appointment_date,
            'status' => $this->status,
        ];
        $appointment = Appointment::create($data);

        session()->flash('success', __('messages.appointment.messages.success'));

        return $this->redirect('/appointment', navigate: true); // redirect to appointment listing page
    }

    public function render()
    {
        return view('livewire.appointment.create')->title(__('messages.meta_title.create_appointment'));
    }
}
