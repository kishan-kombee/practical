<?php

namespace App\Livewire\SubCategory;

use App\Livewire\Breadcrumb;
use Livewire\Component;

class Index extends Component
{
    public function mount()
    {
        /* Set breadcrumb */
        $segmentsData = [
            'title' => __('messages.sub_category.breadcrumb.title'),
            'item_1' => __('messages.sub_category.breadcrumb.sub_category'),
            'item_2' => __('messages.sub_category.breadcrumb.list'),
        ];
        $this->dispatch('breadcrumbList', $segmentsData)->to(Breadcrumb::class);
    }

    public function render()
    {
        return view('livewire.sub-category.index')->title(__('messages.meta_title.index_sub_category'));
    }
}
