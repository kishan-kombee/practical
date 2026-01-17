<?php

namespace App\Livewire\Appointment;

use App\Livewire\Breadcrumb;
use Livewire\Component;

class Index extends Component
{
    public function mount()
    {
        /* Set breadcrumb */
        $segmentsData = [
            'title' => __('messages.appointment.breadcrumb.title'),
            'item_1' => __('messages.appointment.breadcrumb.appointment'),
            'item_2' => __('messages.appointment.breadcrumb.list'),
        ];
        $this->dispatch('breadcrumbList', $segmentsData)->to(Breadcrumb::class);
    }

    public function render()
    {
        return view('livewire.appointment.index')->title(__('messages.meta_title.index_appointment'));
    }
}
