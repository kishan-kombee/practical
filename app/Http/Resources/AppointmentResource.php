<?php

namespace App\Http\Resources;

use App\Models\Appointment;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Appointment
 */
class AppointmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id ?? '',
            'patient_name' => $this->patient_name ?? '',
            'clinic_location' => $this->clinic_location ?? '',
            'clinician_id' => $this->clinician_id ?? '',
            'appointment_date' => $this->appointment_date ? (is_string($this->appointment_date) ? $this->appointment_date : $this->appointment_date->format('Y-m-d')) : '',
            'status' => $this->status ?? '',
            'status_text' => $this->status === 'B' ? 'Booked' : ($this->status === 'D' ? 'Completed' : ($this->status === 'N' ? 'Cancelled' : '')),
            'created_at' => $this->created_at ? (is_string($this->created_at) ? $this->created_at : $this->created_at->format(config('constants.api_datetime_format', 'Y-m-d H:i:s'))) : '',
            'updated_at' => $this->updated_at ? (is_string($this->updated_at) ? $this->updated_at : $this->updated_at->format(config('constants.api_datetime_format', 'Y-m-d H:i:s'))) : '',
        ];
    }
}
