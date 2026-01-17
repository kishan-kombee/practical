<?php

namespace App\Livewire\SmsTemplate;

use App\Livewire\Breadcrumb;
use Livewire\Component;

class Index extends Component
{
    public function mount()
    {
        /* Set breadcrumb */
        $segmentsData = [
            'title' => __('messages.sms_template.breadcrumb.title'),
            'item_1' => __('messages.sms_template.breadcrumb.sms_template'),
            'item_2' => __('messages.sms_template.breadcrumb.list'),
        ];
        $this->dispatch('breadcrumbList', $segmentsData)->to(Breadcrumb::class);
    }

    public function render()
    {
        return view('livewire.sms-template.index')->title(__('messages.meta_title.index_sms_template'));
    }
}
