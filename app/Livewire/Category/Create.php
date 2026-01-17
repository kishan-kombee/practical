<?php

namespace App\Livewire\Category;

use App\Livewire\Breadcrumb;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithFileUploads;

use Symfony\Component\HttpFoundation\Response;

class Create extends Component
{
    use WithFileUploads;

    public $id;

    public $name;

    public $status = '1';

    public function mount()
    {
        if (! Gate::allows('add-category')) {
            abort(Response::HTTP_FORBIDDEN);
        }

        /* begin::Set breadcrumb */
        $segmentsData = [
            'title' => __('messages.category.breadcrumb.title'),
            'item_1' => '<a href="/category" class="text-muted text-hover-primary" wire:navigate>' . __('messages.category.breadcrumb.category') . '</a>',
            'item_2' => __('messages.category.breadcrumb.create'),
        ];
        $this->dispatch('breadcrumbList', $segmentsData)->to(Breadcrumb::class);
        /* end::Set breadcrumb */
    }

    public function rules()
    {
        $rules = [
            'name' => 'required|max:191',
            'status' => 'required|in:0,1',
        ];

        return $rules;
    }

    public function messages()
    {
        return [
            'name.required' => __('messages.category.validation.messsage.name.required'),
            'name.max' => __('messages.category.validation.messsage.name.max'),
            'status.required' => __('messages.category.validation.messsage.status.required'),
            'status.in' => __('messages.category.validation.messsage.status.in'),
        ];
    }

    public function store()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'status' => $this->status,
        ];
        $category = Category::create($data);

        Cache::forget('getAllCategory');
        Cache::forget('getAllActiveCategory');

        session()->flash('success', __('messages.category.messages.success'));

        return $this->redirect('/category', navigate: true); // redirect to category listing page
    }

    public function render()
    {
        return view('livewire.category.create')->title(__('messages.meta_title.create_category'));
    }
}
