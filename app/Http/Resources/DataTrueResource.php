<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DataTrueResource extends JsonResource
{
    public $message;

    /**
     *  Create a new resource instance.
     *
     * DataTrueResource constructor.
     */
    public function __construct($resource, $message)
    {
        parent::__construct($resource);
        $this->message = $message;
    }

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'success' => true,
            'message' => $this->message,
            'data' => true,
        ];
    }
}
