<?php

namespace App\Livewire\SubCategory;

use App\Helper;
use App\Livewire\Breadcrumb;
use App\Models\SubCategory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithFileUploads;

use Symfony\Component\HttpFoundation\Response;

class Edit extends Component
{
    use WithFileUploads;

    public $subcategory;

    public $id;

    public $category_id;

    public $categories = [];

    public $name;

    public $status;

    public function mount($id)
    {
        if (! Gate::allows('edit-sub_category')) {
            abort(Response::HTTP_FORBIDDEN);
        }

        /* begin::Set breadcrumb */
        $segmentsData = [
            'title' => __('messages.sub_category.breadcrumb.title'),
            'item_1' => '<a href="/sub_category" class="text-muted text-hover-primary" wire:navigate>' . __('messages.sub_category.breadcrumb.sub_category') . '</a>',
            'item_2' => __('messages.sub_category.breadcrumb.edit'),
        ];
        $this->dispatch('breadcrumbList', $segmentsData)->to(Breadcrumb::class);
        /* end::Set breadcrumb */

        $this->subcategory = SubCategory::find($id);

        if ($this->subcategory) {
            foreach ($this->subcategory->getAttributes() as $key => $value) {
                $this->{$key} = $value; // Dynamically assign the attributes to the class
            }
        } else {
            abort(Response::HTTP_NOT_FOUND);
        }

        $this->categories = Helper::getAllActiveCategory();
    }

    public function rules()
    {
        $rules = [
            'category_id' => 'required|exists:categories,id,deleted_at,NULL',
            'name' => 'required|max:191',
            'status' => 'required|in:0,1',
        ];

        return $rules;
    }

    public function messages()
    {
        return [
            'category_id.required' => __('messages.sub_category.validation.messsage.category_id.required'),
            'name.required' => __('messages.sub_category.validation.messsage.name.required'),
            'name.max' => __('messages.sub_category.validation.messsage.name.max'),
            'status.required' => __('messages.sub_category.validation.messsage.status.required'),
            'status.in' => __('messages.sub_category.validation.messsage.status.in'),
        ];
    }

    public function store()
    {
        $this->validate();

        $data = [
            'category_id' => $this->category_id,
            'name' => $this->name,
            'status' => $this->status,
        ];
        $this->subcategory->update($data); // Update data into the DB

        Cache::forget('getAllSubCategory');
        Cache::forget('getAllActiveSubCategory');
        // Clear category-specific sub_category cache
        Cache::forget("getSubCategoriesByCategory:{$this->category_id}");

        session()->flash('success', __('messages.sub_category.messages.update'));

        return $this->redirect('/sub_category', navigate: true); // redirect to sub_category listing page
    }

    public function render()
    {
        return view('livewire.sub-category.edit')->title(__('messages.meta_title.edit_sub_category'));
    }
}
