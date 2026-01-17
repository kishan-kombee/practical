<?php

namespace App\Livewire\EmailTemplate;

use App\Helper;
use App\Models\EmailTemplate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use Symfony\Component\HttpFoundation\Response;

final class Table extends PowerGridComponent
{
    public bool $deferLoading = true; // default false

    public string $loadingComponent = 'components.powergrid-loading';

    public string $tableName;

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    // Custom per page
    public int $perPage;

    // Custom per page values
    public array $perPageValues;

    public function __construct()
    {
        if (! Gate::allows('view-emailtemplates')) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $this->tableName = __('messages.email_template.listing.tableName');
        $this->perPage = config('constants.webPerPage');
        $this->perPageValues = config('constants.webPerPageValues');
    }

    public function datasource()
    {
        return EmailTemplate::query()
            ->select(
                'email_templates.id',
                'email_templates.type',
                'email_templates.label',
                'email_templates.subject',
                'email_templates.status',
                'email_templates.created_at',
            );
    }

    public function setUp(): array
    {
        return [
            PowerGrid::header(),
            PowerGrid::footer()
                ->showPerPage($this->perPage, $this->perPageValues)
                ->showRecordCount(),
        ];
    }

    /**
     * fields
     */
    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('type_label', function ($entry) {
                return config('constants.email_template.type_values.' . $entry->type) ?? $entry->type;
            })
            ->add('status_badge', fn ($entry) => Helper::getStatusBadge($entry->status))
            ->add('created_at_formatted', function ($entry) {
                return Carbon::parse($entry->created_at)->format(config('constants.default_datetime_format'));
            });
    }

    public function columns(): array
    {
        return [
            Column::add()
                ->title('Type')
                ->field('type_label', 'email_templates.type')
                ->sortable()
                ->searchable(),

            Column::add()
                ->title('Label')
                ->field('label', 'email_templates.label')
                ->searchable()
                ->sortable(),

            Column::add()
                ->title('Status')
                ->field('status_badge', 'email_templates.status')
                ->sortable()
                ->searchable(),

            Column::add()
                ->title('Created At')
                ->field('created_at_formatted', 'email_templates.created_at')
                ->sortable(),

            Column::action('Actions'),
        ];
    }

    /**
     * actions
     *
     * @return array
     */
    public function actions($entry)
    {
        $actions = [];

        if (Gate::allows('edit-emailtemplates')) {
            $actions[] = Button::add('edit')
                ->slot('<div title="Edit" class="flex items-center justify-center" data-testid="edit_button">' . view('components.flux.icon.pencil', ['variant' => 'micro', 'attributes' => new \Illuminate\View\ComponentAttributeBag(['class' => ''])])->render() . '</div>')
                ->class('w-full sm:w-auto text-gray-600 bg-gray-200 hover:bg-gray-300 py-2 px-2 rounded text-lg cursor-pointer hover:cursor-pointer text-gray-500 hover:text-gray-900')
                ->dispatch('editEmailTemplate', ['id' => $entry->id]);
        }

        return $actions;
    }

    #[On('editEmailTemplate')]
    public function editEmailTemplate($id)
    {
        return $this->redirect('email-template/' . $id . '/edit', navigate: true); // redirect to edit component
    }

    /**
     * filters
     */
    public function filters(): array
    {
        return [

            Filter::select('status_badge', 'email_templates.status')
                ->dataSource([
                    ['label' => 'Active', 'value' => config('constants.user.status.key.active')],
                    ['label' => 'Inactive', 'value' => config('constants.user.status.key.inactive')],
                ])
                ->optionLabel('label')
                ->optionValue('value'),

            Filter::datepicker('created_at_formatted', 'email_templates.created_at'),
        ];
    }
}
