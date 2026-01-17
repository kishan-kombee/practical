<?php

namespace App\Livewire\User;

use App\Helper;
use App\Livewire\Breadcrumb;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\Response;

class Edit extends Component
{
    use WithFileUploads;

    public $user;

    public $id;

    public $role_id;

    public $roles = [];

    public $first_name;

    public $last_name;

    public $email;

    public $mobile_number;

    public $password;

    public $status = 'Y';

    public $last_login_at;

    public $locale = 'en';

    public function mount($id)
    {
        if (! Gate::allows('edit-user')) {
            abort(Response::HTTP_FORBIDDEN);
        }

        /* begin::Set breadcrumb */
        $segmentsData = [
            'title' => __('messages.user.breadcrumb.title'),
            'item_1' => '<a href="/user" class="text-muted text-hover-primary" wire:navigate>' . __('messages.user.breadcrumb.user') . '</a>',
            'item_2' => __('messages.user.breadcrumb.edit'),
        ];
        $this->dispatch('breadcrumbList', $segmentsData)->to(Breadcrumb::class);
        /* end::Set breadcrumb */

        $this->user = User::find($id);

        if ($this->user) {
            foreach ($this->user->getAttributes() as $key => $value) {
                $this->{$key} = $value; // Dynamically assign the attributes to the class
            }
        } else {
            abort(Response::HTTP_NOT_FOUND);
        }

        $this->roles = Helper::getAllRole();
    }

    public function rules()
    {
        $rules = [
            'role_id' => 'required|exists:roles,id,deleted_at,NULL',
            'first_name' => 'required|string|max:50|regex:/^[a-zA-Z\\s]+$/',
            'last_name' => 'required|string|max:50|regex:/^[a-zA-Z\\s]+$/',
            'email' => 'nullable|email|max:320',
            'mobile_number' => 'required|digits:10|regex:/^[6-9]\\d{9}$/',
            'status' => 'required|in:Y,N',
        ];

        return $rules;
    }

    public function messages()
    {
        return [
            'role_id.required' => __('messages.user.validation.messsage.role_id.required'),
            'first_name.required' => __('messages.user.validation.messsage.first_name.required'),
            'first_name.in' => __('messages.user.validation.messsage.first_name.in'),
            'first_name.max' => __('messages.user.validation.messsage.first_name.max'),
            'last_name.required' => __('messages.user.validation.messsage.last_name.required'),
            'last_name.in' => __('messages.user.validation.messsage.last_name.in'),
            'last_name.max' => __('messages.user.validation.messsage.last_name.max'),
            'email.email' => __('messages.user.validation.messsage.email.email'),
            'email.max' => __('messages.user.validation.messsage.email.max'),
            'mobile_number.required' => __('messages.user.validation.messsage.mobile_number.required'),
            'status.required' => __('messages.user.validation.messsage.status.required'),
            'status.in' => __('messages.user.validation.messsage.status.in'),
        ];
    }

    public function store()
    {
        $this->validate();

        $data = [
            'role_id' => $this->role_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'mobile_number' => $this->mobile_number,
            'status' => $this->status,
        ];
        $this->user->update($data); // Update data into the DB

        session()->flash('success', __('messages.user.messages.update'));

        return $this->redirect('/user', navigate: true); // redirect to user listing page
    }

    public function render()
    {
        return view('livewire.user.edit')->title(__('messages.meta_title.edit_user'));
    }
}
