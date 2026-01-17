<?php

namespace App\Livewire\EmailTemplate;

use App\Helper;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Symfony\Component\HttpFoundation\Response;

class Edit extends Component
{
    public $emailTemplate;

    public $id;

    public $type;

    public $label;

    public $subject;

    public $body;

    public $status = 'Y';

    public $statusLabels = ['Inactive', 'Active'];

    public $legendsArray = [];

    public function mount($id)
    {
        if (! Gate::allows('edit-emailtemplates')) {
            abort(Response::HTTP_FORBIDDEN);
        }

        /* begin::Set breadcrumb */
        $segmentsData = [
            'title' => __('messages.email_template.breadcrumb.title'),
            'item_1' => __('messages.email_template.breadcrumb.sub_title'),
            'item_2' => __('messages.user.breadcrumb.create'),
        ];
        $this->dispatch('breadcrumbList', $segmentsData)->to('breadcrumb');
        /* end::Set breadcrumb */

        $this->id = $id;
        $this->emailTemplate = EmailTemplate::find($id);
        if (! is_null($this->emailTemplate)) {
            foreach ($this->emailTemplate->getAttributes() as $key => $value) {
                $this->{$key} = $value;
            }
            // Load legends from Helper
            $this->legendsArray = Helper::getAllLegends();
        } else {
            abort(Response::HTTP_NOT_FOUND);
        }
    }

    public function store()
    {
        $validateData = $this->validate([
            'subject' => 'required',
            'body' => 'required',
            'status' => 'required|in:Y,N',
        ]);
        EmailTemplate::where('id', $this->id)->update($validateData);
        session()->flash('success', __('messages.common_message.email_template_update'));

        return $this->redirect('/email-templates', navigate: true);
    }

    public function render()
    {
        $types = EmailTemplate::types();

        return view('livewire.email-template.edit', compact('types'));
    }
}
