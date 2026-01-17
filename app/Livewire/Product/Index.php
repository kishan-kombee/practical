<?php

namespace App\Livewire\Product;

use App\Livewire\Breadcrumb;
use Livewire\Component;

class Index extends Component
{
    public function mount()
    {
        /* Set breadcrumb */
        $segmentsData = [
            'title' => __('messages.product.breadcrumb.title'),
            'item_1' => __('messages.product.breadcrumb.product'),
            'item_2' => __('messages.product.breadcrumb.list'),
        ];
        $this->dispatch('breadcrumbList', $segmentsData)->to(Breadcrumb::class);
    }

    public function render()
    {
        return view('livewire.product.index')->title(__('messages.meta_title.index_product'));
    }
}
