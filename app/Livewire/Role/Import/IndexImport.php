<?php

namespace App\Livewire\Role\Import;

use App\Livewire\Breadcrumb;
use Livewire\Component;

class IndexImport extends Component
{
    public $importData;

    public function mount()
    {
        /* Set breadcrumb */
        $segmentsData = [
            'title' => __('messages.import_history.breadcrumb.title'),
            'item_1' => __('messages.import_history.breadcrumb.role'),
            'item_2' => __('messages.import_history.breadcrumb.list'),
        ];
        $this->dispatch('breadcrumbList', $segmentsData)->to(Breadcrumb::class);

        $this->importData = [
            'folderName' => config('constants.import_csv_log.folder_name.new.role'),
            'modelName' => config('constants.import_csv_log.models.role'),
        ];
    }

    public function render()
    {
        return view('livewire.role.import.index-import')->title(__('messages.meta_title.index_role'));
    }
}
