<?php

namespace App\Livewire\Category;

use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class Show extends Component
{
    public $id;

    public $category;

    public $event = 'showcategoryInfoModal';

    #[On('show-category-info')]
    public function show($id)
    {
        $this->category = null;

        $this->category = Category::select(
            'categories.id',
            'categories.name',
            DB::raw(
                '(CASE
                                        WHEN categories.status = "' . config('constants.category.status.key.inactive') . '" THEN  "' . config('constants.category.status.value.inactive') . '"
                                        WHEN categories.status = "' . config('constants.category.status.key.active') . '" THEN  "' . config('constants.category.status.value.active') . '"
                                ELSE " "
                                END) AS status'
            )
        )

            ->where('categories.id', $id)

            ->first();

        if (! is_null($this->category)) {
            $this->dispatch('show-modal', id: '#' . $this->event);
        } else {
            session()->flash('error', __('messages.category.messages.record_not_found'));
        }
    }

    public function render()
    {
        return view('livewire.category.show');
    }
}
