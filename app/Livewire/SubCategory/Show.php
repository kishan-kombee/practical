<?php

namespace App\Livewire\SubCategory;

use App\Models\SubCategory;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class Show extends Component
{
    public $id;

    public $subcategory;

    public $event = 'showsubcategoryInfoModal';

    #[On('show-subcategory-info')]
    public function show($id)
    {
        $this->subcategory = null;

        $this->subcategory = SubCategory::select(
            'sub_categories.id',
            'categories.name as categories_name',
            'sub_categories.name',
            DB::raw(
                '(CASE
                                        WHEN sub_categories.status = "' . config('constants.sub_category.status.key.inactive') . '" THEN  "' . config('constants.sub_category.status.value.inactive') . '"
                                        WHEN sub_categories.status = "' . config('constants.sub_category.status.key.active') . '" THEN  "' . config('constants.sub_category.status.value.active') . '"
                                ELSE " "
                                END) AS status'
            )
        )
            ->leftJoin('categories', 'categories.id', '=', 'sub_categories.category_id')
            ->where('sub_categories.id', $id)
            ->groupBy('sub_categories.id')
            ->first();

        if (! is_null($this->subcategory)) {
            $this->dispatch('show-modal', id: '#' . $this->event);
        } else {
            session()->flash('error', __('messages.sub_category.messages.record_not_found'));
        }
    }

    public function render()
    {
        return view('livewire.sub-category.show');
    }
}
