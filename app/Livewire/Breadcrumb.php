<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;

class Breadcrumb extends Component
{
    public $segments = [];

    #[On('breadcrumbList')]
    public function breadcrumbList($segmentsData)
    {
        $this->segments = $segmentsData;
    }

    public function render()
    {
        return view('livewire.breadcrumb', ['segments' => $this->segments]);
    }
}
