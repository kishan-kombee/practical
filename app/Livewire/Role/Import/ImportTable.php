<?php

namespace App\Livewire\Role\Import;

use App\Helper;
use App\Models\ImportLog;
use App\Traits\RefreshDataTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use Symfony\Component\HttpFoundation\Response;

final class ImportTable extends PowerGridComponent
{
    use RefreshDataTable;

    public bool $deferLoading = true; // default false

    public string $tableName;

    public string $loadingComponent = 'components.powergrid-loading';

    public string $sortField = 'id';

    public string $sortDirection = 'desc';

    // Custom per page
    public int $perPage;

    // Custom per page values
    public array $perPageValues;

    public $currentRole;

    public string $search = '';

    public function __construct()
    {
        if (Gate::allows('import-role')) {
            $this->currentRole = Auth::user();
            $this->perPage = config('constants.webPerPage');
            $this->perPageValues = config('constants.webPerPageValues');
            $this->tableName = __('messages.role.listing.tableName') . '-import';
        } else {
            abort(Response::HTTP_NOT_FOUND);
        }
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

    public function datasource(): Builder
    {
        if (isset($_GET['file_name'])) {
            $this->search = $_GET['file_name'];
        }

        return ImportLog::query()->where('model_name', config('constants.import_type.role'));
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('file_name')
            ->add('file_path')
            ->add('status', function (ImportLog $model) {
                return Helper::getImportStatusText($model->status);
            })
            ->add('no_of_rows', function (ImportLog $model) {
                return $model->no_of_rows > 0 ? $model->no_of_rows : 'N/A';
            })
            ->add('error_log')
            ->add('created_at_formatted', fn (ImportLog $model) => Carbon::parse($model->created_at)->format(config('constants.date_formats.default')));
    }

    public function columns(): array
    {
        return [
            Column::make(__('messages.import.file_lable'), 'file_name')
                ->sortable()
                ->searchable(),

            Column::make(__('messages.import.date_lable'), 'created_at_formatted', 'created_at'),

            Column::make(__('messages.import.no_of_rows_lable'), 'no_of_rows'),

            Column::make(__('messages.import.status_lable'), 'status'),

            Column::action(__('messages.import.actions_lable')),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::inputText('file_name')->component('column-search-textbox', ['placeholder' => 'File']),
            Filter::select('status', 'status')
                ->dataSource(ImportLog::status())
                ->optionValue('status')
                ->optionLabel('label'),
            Filter::datetimepicker('created_at'),
            Filter::inputText('no_of_rows')->component('column-search-textbox', ['placeholder' => 'No. of rows']),
        ];
    }

    public function actions(ImportLog $row): array
    {
        $actions = [];

        // Only show View Log button for failed status (N)
        if ($row->status == config('constants.import_csv_log.status.key.fail')) {
            $actions[] = Button::add('view_log')
                ->slot('<div title="View error log" class="flex items-center justify-center" data-testid="view_log_button">' . view('components.flux.icon.eye', ['variant' => 'micro', 'attributes' => new \Illuminate\View\ComponentAttributeBag(['class' => 'text-red-600 hover:text-red-800'])])->render() . '</div>')
                ->class('border border-red-200 text-red-600 hover:bg-red-50 hover:border-red-300 py-2 px-2 rounded text-sm cursor-pointer hover:cursor-pointer')
                ->dispatchTo('role.import.import-error-page', 'viewImportErrors', ['errorLogs' => $row->error_log]);
        }

        return $actions;
    }
}
