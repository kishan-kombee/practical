<?php

namespace App\Livewire\SmsTemplate;

use App\Livewire\Breadcrumb;
use App\Models\SmsTemplate;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\Response;

class Create extends Component
{
    use WithFileUploads;

    public $id;

    public $type;

    public $label;

    public $message;

    public $dlt_message_id;

    public $status = 'Y';

    public function mount()
    {
        if (! Gate::allows('add-sms-template')) {
            abort(Response::HTTP_FORBIDDEN);
        }

        /* begin::Set breadcrumb */
        $segmentsData = [
            'title' => __('messages.sms_template.breadcrumb.title'),
            'item_1' => '<a href="/sms-template" class="text-muted text-hover-primary" wire:navigate>' . __('messages.sms_template.breadcrumb.smstemplate') . '</a>',
            'item_2' => __('messages.sms_template.breadcrumb.create'),
        ];
        $this->dispatch('breadcrumbList', $segmentsData)->to(Breadcrumb::class);
        /* end::Set breadcrumb */
    }

    public function rules()
    {
        $rules = [
            'type' => 'required|string|max:100',
            'label' => 'required|string|max:100',
            'message' => 'required|string',
            'dlt_message_id' => 'required|string|max:200|regex:/^[A-Z0-9]{8,16}$/',
            'status' => 'required|in:Y,N',
        ];

        return $rules;
    }

    public function messages()
    {
        return [
            'type.required' => __('messages.sms_template.validation.messsage.type.required'),
            'type.in' => __('messages.sms_template.validation.messsage.type.in'),
            'type.max' => __('messages.sms_template.validation.messsage.type.max'),
            'label.required' => __('messages.sms_template.validation.messsage.label.required'),
            'label.in' => __('messages.sms_template.validation.messsage.label.in'),
            'label.max' => __('messages.sms_template.validation.messsage.label.max'),
            'message.required' => __('messages.sms_template.validation.messsage.message.required'),
            'message.in' => __('messages.sms_template.validation.messsage.message.in'),
            'dlt_message_id.required' => __('messages.sms_template.validation.messsage.dlt_message_id.required'),
            'dlt_message_id.in' => __('messages.sms_template.validation.messsage.dlt_message_id.in'),
            'dlt_message_id.max' => __('messages.sms_template.validation.messsage.dlt_message_id.max'),
            'status.required' => __('messages.sms_template.validation.messsage.status.required'),
            'status.in' => __('messages.sms_template.validation.messsage.status.in'),
        ];
    }

    public function store()
    {
        $this->validate();

        $data = [
            'type' => $this->type,
            'label' => $this->label,
            'message' => $this->message,
            'dlt_message_id' => $this->dlt_message_id,
            'status' => $this->status,
        ];
        $smstemplate = SmsTemplate::create($data);

        session()->flash('success', __('messages.sms_template.messages.success'));

        return $this->redirect('/sms-template', navigate: true); // redirect to sms-template listing page
    }

    public function render()
    {
        return view('livewire.sms-template.create')->title(__('messages.meta_title.create_sms_template'));
    }
}
