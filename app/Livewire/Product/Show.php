<?php

namespace App\Livewire\Product;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class Show extends Component
{
    public $id;

    public $product;

    public $event = 'showproductInfoModal';

    #[On('show-product-info')]
    public function show($id)
    {
        $this->product = null;

        $this->product = Product::select(
            'products.id',
            'products.item_code',
            'products.name',
            'products.price',
            'products.description',
            'categories.name as categories_name',
            'sub_categories.name as sub_categories_name',
            DB::raw(
                '(CASE
                                        WHEN products.available_status = "' . config('constants.product.available_status.key.not-available') . '" THEN  "' . config('constants.product.available_status.value.not-available') . '"
                                        WHEN products.available_status = "' . config('constants.product.available_status.key.available') . '" THEN  "' . config('constants.product.available_status.value.available') . '"
                                ELSE " "
                                END) AS available_status'
            ),
            'products.quantity'
        )
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('sub_categories', 'sub_categories.id', '=', 'products.sub_category_id')
            ->where('products.id', $id)
            ->groupBy('products.id')
            ->first();

        if (! is_null($this->product)) {
            $this->dispatch('show-modal', id: '#' . $this->event);
        } else {
            session()->flash('error', __('messages.product.messages.record_not_found'));
        }
    }

    public function render()
    {
        return view('livewire.product.show');
    }
}
