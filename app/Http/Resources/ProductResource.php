<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id ?? '',
            'item_code' => $this->item_code ?? '',
            'name' => $this->name ?? '',
            'price' => $this->price ?? '',
            'description' => $this->description ?? '',
            'category_id' => $this->category_id ?? '',
            'sub_category_id' => $this->sub_category_id ?? '',
            'available_status' => $this->available_status ?? '',
            'available_status_text' => config('constants.available_status_values.' . $this->available_status) ?? '',
            'quantity' => $this->quantity ?? '',
        ];
    }
}
