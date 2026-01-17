<?php

namespace App\Livewire\Category;

use App\Livewire\Breadcrumb;
use Livewire\Component;

class Index extends Component
{
    public function mount()
    {
        /* Set breadcrumb */
        $segmentsData = [
            'title' => __('messages.category.breadcrumb.title'),
            'item_1' => __('messages.category.breadcrumb.category'),
            'item_2' => __('messages.category.breadcrumb.list'),
        ];
        $this->dispatch('breadcrumbList', $segmentsData)->to(Breadcrumb::class);
    }

    public function render()
    {
        return view('livewire.category.index')->title(__('messages.meta_title.index_category'));
    }
}
