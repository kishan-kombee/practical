<?php

namespace App\Services\Export;

use App\Models\Appointment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AppointmentExportService implements ExportServiceInterface
{
    /**
     * Build export query (mirrors table datasource logic)
     */
    public function buildQuery(array $filters, array $checkboxValues, ?string $search): Builder
    {
        $userId = Auth::id();
        // Load user with role relationship to avoid repeated queries
        $user = $userId ? \App\Models\User::with('role')->find($userId) : null;
        $isAdmin = $user && $user->isAdmin();

        $query = Appointment::query()
            ->leftJoin('users', 'users.id', '=', 'appointments.clinician_id')
            ->select([
                'appointments.id',
                'appointments.patient_name',
                'appointments.clinic_location',
                'appointments.clinician_id',
                'appointments.appointment_date',
                DB::raw('CONCAT(users.first_name, " ", users.last_name) as clinician_name'),
                DB::raw(
                    '(CASE
                        WHEN appointments.status = "' . config('constants.appointment.status.key.booked') . '" THEN  "' . config('constants.appointment.status.value.booked') . '"
                        WHEN appointments.status = "' . config('constants.appointment.status.key.completed') . '" THEN  "' . config('constants.appointment.status.value.completed') . '"
                        WHEN appointments.status = "' . config('constants.appointment.status.key.cancelled') . '" THEN  "' . config('constants.appointment.status.value.cancelled') . '"
                        ELSE " "
                    END) AS status'
                ),
            ])
            ->whereNull('appointments.deleted_at');

        // Apply authorization: Clinicians see only their appointments, Admins see all
        if (!$isAdmin && $user) {
            $query->forClinician($user->id);
        }

        $query->groupBy('appointments.id');

        // Apply patient_name filters
        if (isset($filters['input_text']['appointments']['patient_name']) && $filters['input_text']['appointments']['patient_name']) {
            $query->where('appointments.patient_name', 'like', '%' . $filters['input_text']['appointments']['patient_name'] . '%');
        }

        // Apply clinic_location filters
        if (isset($filters['input_text']['appointments']['clinic_location']) && $filters['input_text']['appointments']['clinic_location']) {
            $query->where('appointments.clinic_location', 'like', '%' . $filters['input_text']['appointments']['clinic_location'] . '%');
        }

        // Apply appointment_date filters
        $where_start = $filters['datetime']['appointments']['appointment_date']['start'] ?? null;
        $where_end = $filters['datetime']['appointments']['appointment_date']['end'] ?? null;
        if ($where_start && $where_end) {
            $query->whereBetween('appointments.appointment_date', [$where_start, $where_end]);
        }

        // Apply status filters
        if (isset($filters['select']['appointments']['status']) && $filters['select']['appointments']['status']) {
            $query->where('appointments.status', $filters['select']['appointments']['status']);
        }

        // Apply checkbox filter (export only selected ids)
        if (! empty($checkboxValues)) {
            $query->whereIn('appointments.id', $checkboxValues);
        }

        // Apply clinician_name filters
        if (isset($filters['input_text']['clinician_name']) && $filters['input_text']['clinician_name']) {
            $query->where(function ($q) use ($filters) {
                $q->where('users.first_name', 'like', '%' . $filters['input_text']['clinician_name'] . '%')
                    ->orWhere('users.last_name', 'like', '%' . $filters['input_text']['clinician_name'] . '%');
            });
        }

        // Apply global search across configured columns
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->orWhere('appointments.patient_name', 'like', "%{$search}%");
                $q->orWhere('appointments.clinic_location', 'like', "%{$search}%");
                $q->orWhere('users.first_name', 'like', "%{$search}%");
                $q->orWhere('users.last_name', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('appointments.id', 'desc');
    }

    /**
     * Map a single row to CSV format.
     */
    public function formatToCSV($row): string
    {
        $fields = [
            $row->id ?? '',
            $row->patient_name ?? '',
            $row->clinic_location ?? '',
            $row->clinician_name ?? '',
            $row->appointment_date ?? '',
            $row->status ?? '',
        ];

        return implode(',', array_map([$this, 'wrapInQuotes'], $fields));
    }

    public function getCSVHeader(): string
    {
        return '"Id","Patient Name","Clinic Location","Clinician","Appointment Date","Status"';
    }

    public function getFilenamePrefix(): string
    {
        return 'AppointmentReports_';
    }

    public function hasPermission(): bool
    {
        return Gate::allows('view-appointment');
    }

    /**
     * Wrap value in quotes for CSV compatibility
     */
    private function wrapInQuotes($value): string
    {
        $value = (string) ($value ?? '');

        return '"' . str_replace('"', '""', $value) . '"';
    }
}
