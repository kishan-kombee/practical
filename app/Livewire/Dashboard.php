<?php

namespace App\Livewire;

use App\Models\Appointment;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public $totalProducts;

    public $totalAppointments;

    public $upcomingAppointments;

    public $userName;

    public $userRole;

    public function mount()
    {
        /* begin::Set breadcrumb */
        $segmentsData = [
            'title' => __('messages.dashboard.breadcrumb.title'),
            'item_1' => __('messages.dashboard.breadcrumb.sub_title'),
            'item_2' => __('messages.dashboard.breadcrumb.list'),
        ];
        $this->dispatch('breadcrumbList', $segmentsData)->to(Breadcrumb::class);
        /* end::Set breadcrumb */

        $this->loadDashboardData();
    }

    /**
     * Load all dashboard statistics efficiently
     * Uses single queries for each statistic to avoid duplicate queries
     * Admin users see all counts, other users see only their own appointment counts
     */
    private function loadDashboardData(): void
    {
        $user = Auth::user();

        // Get user information with role relationship loaded
        if ($user) {
            // Load role relationship to check if user is admin
            $user->load('role');
            $this->userName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            $this->userRole = $user->role ? $user->role->name : '-';
            $isAdmin = $user->isAdmin();
        } else {
            $this->userName = '-';
            $this->userRole = '-';
            $isAdmin = false;
        }

        // Get total products count (excluding soft deleted) - single efficient query
        // Products count is same for all users
        $this->totalProducts = Product::count();

        // Get appointment counts based on user role
        if ($isAdmin) {
            // Admin sees all appointments
            $this->totalAppointments = Appointment::count();

            // Get upcoming appointments (next 7 days) - all appointments
            $today = Carbon::today();
            $nextWeek = Carbon::today()->addDays(7);

            $this->upcomingAppointments = Appointment::whereBetween('appointment_date', [$today, $nextWeek])
                ->count();
        } else {
            // Non-admin users see only their own appointments
            if ($user) {
                $this->totalAppointments = Appointment::where('clinician_id', $user->id)
                    ->count();

                // Get upcoming appointments (next 7 days) - only their own
                $today = Carbon::today();
                $nextWeek = Carbon::today()->addDays(7);

                $this->upcomingAppointments = Appointment::where('clinician_id', $user->id)
                    ->whereBetween('appointment_date', [$today, $nextWeek])
                    ->count();
            } else {
                $this->totalAppointments = 0;
                $this->upcomingAppointments = 0;
            }
        }
    }

    public function render()
    {
        return view('livewire.dashboard')->title(__('messages.meta_titles.dashboard'));
    }
}
