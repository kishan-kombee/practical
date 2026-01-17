<?php

namespace App\Livewire\SmsTemplate;

use App\Models\SmsTemplate;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class Show extends Component
{
    public $id;

    public $smstemplate;

    public $event = 'showsmstemplateInfoModal';

    #[On('show-smstemplate-info')]
    public function show($id)
    {
        $this->smstemplate = null;

        $this->smstemplate = SmsTemplate::select(
            'sms_templates.id',
            'sms_templates.type',
            'sms_templates.label',
            'sms_templates.message',
            'sms_templates.dlt_message_id',
            DB::raw(
                '(CASE
                                        WHEN sms_templates.status = "' . config('constants.sms_template.status.key.active') . '" THEN  "' . config('constants.sms_template.status.value.active') . '"
                                        WHEN sms_templates.status = "' . config('constants.sms_template.status.key.inactive') . '" THEN  "' . config('constants.sms_template.status.value.inactive') . '"
                                ELSE " "
                                END) AS status'
            )
        )

            ->where('sms_templates.id', $id)

            ->first();

        if (! is_null($this->smstemplate)) {
            $this->dispatch('show-modal', id: '#' . $this->event);
        } else {
            session()->flash('error', __('messages.sms_template.messages.record_not_found'));
        }
    }

    public function render()
    {
        return view('livewire.sms-template.show');
    }
}
