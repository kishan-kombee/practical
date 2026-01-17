<?php

namespace App\Livewire\Product;

use App\Helper;
use App\Livewire\Breadcrumb;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithFileUploads;

use Symfony\Component\HttpFoundation\Response;

class Create extends Component
{
    use WithFileUploads;

    public $id;

    public $item_code;

    public $name;

    public $price;

    public $description;

    public $category_id;

    public $categories = [];

    public $sub_categories = [];

    public $sub_category_id;

    public $available_status;

    public $quantity;

    public function mount()
    {
        if (! Gate::allows('add-product')) {
            abort(Response::HTTP_FORBIDDEN);
        }

        /* begin::Set breadcrumb */
        $segmentsData = [
            'title' => __('messages.product.breadcrumb.title'),
            'item_1' => '<a href="/product" class="text-muted text-hover-primary" wire:navigate>' . __('messages.product.breadcrumb.product') . '</a>',
            'item_2' => __('messages.product.breadcrumb.create'),
        ];
        $this->dispatch('breadcrumbList', $segmentsData)->to(Breadcrumb::class);
        /* end::Set breadcrumb */

        $this->categories = Helper::getAllActiveCategory();
        // Don't load sub_categories initially - they will be loaded dynamically when category is selected
        $this->sub_categories = [];
    }

    public function updatedCategoryId($value)
    {
        if ($value) {
            $this->sub_categories = Helper::getSubCategoriesByCategory($value);
            $this->sub_category_id = null; // Reset sub_category when category changes
        } else {
            $this->sub_categories = [];
            $this->sub_category_id = null;
        }
    }

    public function rules()
    {
        $rules = [
            'item_code' => 'required|max:191',
            'name' => 'required|max:191',
            'price' => 'required',
            'description' => 'required',
            'category_id' => 'required|exists:categories,id,deleted_at,NULL',
            'sub_category_id' => 'required|exists:sub_categories,id,deleted_at,NULL',
            'available_status' => 'required|in:0,1',
        ];

        return $rules;
    }

    public function messages()
    {
        return [
            'item_code.required' => __('messages.product.validation.messsage.item_code.required'),
            'item_code.max' => __('messages.product.validation.messsage.item_code.max'),
            'name.required' => __('messages.product.validation.messsage.name.required'),
            'name.max' => __('messages.product.validation.messsage.name.max'),
            'price.required' => __('messages.product.validation.messsage.price.required'),
            'description.required' => __('messages.product.validation.messsage.description.required'),
            'category_id.required' => __('messages.product.validation.messsage.category_id.required'),
            'sub_category_id.required' => __('messages.product.validation.messsage.sub_category_id.required'),
            'available_status.required' => __('messages.product.validation.messsage.available_status.required'),
            'available_status.in' => __('messages.product.validation.messsage.available_status.in'),
        ];
    }

    public function store()
    {
        $this->validate();

        $data = [
            'item_code' => $this->item_code,
            'name' => $this->name,
            'price' => $this->price,
            'description' => $this->description,
            'category_id' => $this->category_id,
            'sub_category_id' => $this->sub_category_id,
            'available_status' => $this->available_status,
            'quantity' => $this->quantity,
        ];
        $product = Product::create($data);

        Cache::forget('getAllProduct');
        Cache::forget('getAllActiveSubCategory');

        session()->flash('success', __('messages.product.messages.success'));

        return $this->redirect('/product', navigate: true); // redirect to product listing page
    }

    public function render()
    {
        return view('livewire.product.create')->title(__('messages.meta_title.create_product'));
    }
}
