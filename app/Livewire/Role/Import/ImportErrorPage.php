<?php

namespace App\Livewire\Role\Import;

use Livewire\Component;

class ImportErrorPage extends Component
{
    public $errorLogs;

    public $event = 'importErrorShowModal';

    #[\Livewire\Attributes\On('viewImportErrors')]
    public function updateBlockStatus($errorLogs)
    {
        // error_log is already cast as array in the model, so no need to json_decode
        $this->errorLogs = is_array($errorLogs) ? $errorLogs : json_decode($errorLogs, true);
        $this->dispatch('show-modal', id: '#' . $this->event);
    }

    public function render()
    {
        return view('livewire.role.import.import-error-page');
    }
}
