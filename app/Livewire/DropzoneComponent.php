<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DropzoneComponent extends Component
{
    public $importData;

    public $userID;

    /**
     * mount
     *
     * @param mixed $importData
     */
    public function mount($importData)
    {
        $user = Auth::user();
        $this->importData = $importData;
        $this->userID = $user->id;
    }

    public function downloadSampleCsv()
    {
        if ($this->importData['modelName'] == config('constants.import_csv_log.models.role')) {
            $filePath = public_path('samples/import_sample_role.csv');
        } elseif ($this->importData['modelName'] == config('constants.import_csv_log.models.user')) {
            $filePath = public_path('samples/import_sample_user.csv');
        } elseif ($this->importData['modelName'] == config('constants.import_csv_log.models.category')) {
            $filePath = public_path('samples/import_sample_category.csv');
        } elseif ($this->importData['modelName'] == config('constants.import_csv_log.models.sub_category')) {
            $filePath = public_path('samples/import_sample_sub_category.csv');
        } elseif ($this->importData['modelName'] == config('constants.import_csv_log.models.product')) {
            $filePath = public_path('samples/import_sample_product.csv');
        } elseif ($this->importData['modelName'] == config('constants.import_csv_log.models.appointment')) {
            $filePath = public_path('samples/import_sample_appointment.csv');
        } elseif ($this->importData['modelName'] == config('constants.import_csv_log.models.smstemplate')) {
            $filePath = public_path('samples/import_sample_smstemplate.csv');
        } else {
            $filePath = ''; // Default file path
        }

        if ($filePath != '') {
            return response()->download($filePath);
        } else {
            session()->flash('error', __('messages.something_went_wrong'));
        }
    }

    public function render()
    {
        return view('livewire.dropzone-component');
    }
}
