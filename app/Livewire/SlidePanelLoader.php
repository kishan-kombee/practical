<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;

class SlidePanelLoader extends Component
{
    public $componentName = null;

    public $params = [];

    #[On('loadSlideComponent')]
    public function loadSlideComponent($component, $params)
    {
        $this->componentName = $component ?? null;
        $this->params = $params ?? [];

        // 🔑 force a fresh mount every time
        $this->params['_lw_key'] =
        $component . '-' . ($params['id'] ?? uniqid()) . '-' . now()->timestamp;
    }

    public function render()
    {
        return view('livewire.slide-panel-loader');
    }
}
