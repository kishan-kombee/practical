<?php

namespace App\Livewire\EmailFormat;

use App\Models\EmailFormat;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Symfony\Component\HttpFoundation\Response;

class Edit extends Component
{
    public $emailFormats;

    public function mount()
    {
        if (! Gate::allows('edit-emailformats')) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $this->emailFormats = EmailFormat::select('id', 'label', 'body')->get()->toArray();

        /* begin::Set breadcrumb */
        $segmentsData = [
            'title' => __('messages.email_format.breadcrumb.title'),
            'item_1' => __('messages.email_format.breadcrumb.sub_title'),
            'item_2' => __('messages.email_format.breadcrumb.edit'),
        ];
        $this->dispatch('breadcrumbList', $segmentsData)->to('breadcrumb');
        /* end::Set breadcrumb */
    }

    public function rules(): array
    {
        return [
            'emailFormats.*.body' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'emailFormats.*.body.required' => __('messages.email_format.messages.body_required'),
            'emailFormats.*.body.string' => __('messages.email_format.messages.body_string'),
        ];
    }

    public function store(): void
    {
        $this->validate();

        foreach ($this->emailFormats as $emailFormatData) {
            $emailFormat = EmailFormat::find($emailFormatData['id']);
            if ($emailFormat) {
                $emailFormat->update([
                    'body' => $emailFormatData['body'],
                ]);
            }
        }
        session()->flash('success', __('messages.email_format.messages.update_success'));
    }

    public function render()
    {
        return view('livewire.email-format.edit');
    }
}
