<?php

namespace App\Livewire\Role;

use App\Livewire\Breadcrumb;
use Livewire\Component;

class Index extends Component
{
    public function mount()
    {
        /* Set breadcrumb */
        $segmentsData = [
            'title' => __('messages.role.breadcrumb.title'),
            'item_1' => __('messages.role.breadcrumb.role'),
            'item_2' => __('messages.role.breadcrumb.list'),
        ];
        $this->dispatch('breadcrumbList', $segmentsData)->to(Breadcrumb::class);
    }

    public function render()
    {
        return view('livewire.role.index')->title(__('messages.meta_title.index_role'));
    }
}
