<?php

namespace App\Livewire\EmailTemplate;

use Livewire\Component;

class Index extends Component
{
    public function mount()
    {
        /* begin::Set breadcrumb */
        $segmentsData = [
            'title' => __('messages.email_template.breadcrumb.title'),
            'item_1' => __('messages.email_template.breadcrumb.sub_title'),
            'item_2' => __('messages.user.breadcrumb.list'),
        ];
        $this->dispatch('breadcrumbList', $segmentsData)->to('breadcrumb');
        /* end::Set breadcrumb */
    }

    public function render()
    {
        return view('livewire.email-template.index');
    }
}
