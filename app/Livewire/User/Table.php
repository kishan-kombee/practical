<?php

namespace App\Livewire\User;

use App\Models\User;
use App\Services\Export\ExportServiceFactory;
use App\Traits\RefreshDataTable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use Throwable;

final class Table extends PowerGridComponent
{
    use RefreshDataTable;

    public bool $deferLoading = true; // default false

    public string $tableName;

    public string $loadingComponent = 'components.powergrid-loading';

    public string $sortField = 'users.id';

    public string $sortDirection = 'desc';

    // Custom per page
    public int $perPage;

    // Custom per page values
    public array $perPageValues;

    public $currentUser;

    public bool $isExporting = false;

    public int $total = 0;

    public function __construct()
    {
        if (! Gate::allows('view-user')) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $this->tableName = __('messages.user.listing.tableName');
        $this->perPage = config('constants.webPerPage');
        $this->perPageValues = config('constants.webPerPageValues');
    }

    public function exportData()
    {
        try {
            // Prevent duplicate exports
            if ($this->isExporting) {
                return false;
            }

            // Use ExportService to build query and get accurate count
            $exportService = ExportServiceFactory::create('user');

            $query = $exportService->buildQuery(
                $this->filters ?? [],
                $this->checkboxValues ?? [],
                $this->search ?? ''
            );
            $totalRecords = DB::table(DB::raw('(' . $query->toSql() . ') as subquery'))
                ->mergeBindings($query->getQuery())
                ->count();

            // Check if there are any records to export
            if ($totalRecords == 0) {
                $this->dispatch('alert', type: 'error', message: __('messages.export.no_records_found'));

                return false;
            }

            // Set exporting state
            $this->isExporting = true;

            // Prepare export parameters for SSE streaming
            // IMPORTANT: Create deep copies of filters to prevent them from being affected by UI changes
            $exportId = uniqid('export_', true); // Unique ID for this export session
            $exportParams = [
                'exportType' => 'user', // Export type identifier
                'exportId' => $exportId, // Unique export session ID
                'filters' => json_decode(json_encode($this->filters ?? []), true), // Deep copy to isolate from UI changes
                'checkboxValues' => array_values($this->checkboxValues ?? []), // Create new array copy
                'search' => (string) ($this->search ?? ''), // Create string copy
                'total' => $totalRecords,
                'startPage' => '/user',
                'chunkSize' => 100, // Process 100 records per chunk
            ];

            // Dispatch event to start SSE streaming export
            // Pass data directly as array (Livewire v3 handles this better)
            $this->dispatch('startExportStreamSSE', $exportParams);
        } catch (Throwable $e) {
            // Log and dispatch error alert if exception occurs
            logger()->error('App\Livewire\UserTable: exportData: Throwable', ['Message' => $e->getMessage(), 'TraceAsString' => $e->getTraceAsString()]);
            session()->flash('error', __('messages.user.messages.common_error_message'));

            // Reset exporting state on error
            $this->isExporting = false;

            return false;
        }
    }

    #[On('export-completed')]
    public function onExportCompleted(): void
    {
        $this->isExporting = false;
    }

    #[On('export-error')]
    public function onExportError(): void
    {
        $this->isExporting = false;
    }

    public function header(): array
    {
        $headerArray = [];

        if (Gate::allows('add-user')) {
            $headerArray[] = Button::add('add-user')
                ->slot('    <a href="/user/create" title="Add New User" data-testid="add_new" class="flex items-center justify-center cursor-pointer" wire:navigate>
        <svg class="h-5 w-5 text-pg-white-500 dark:text-pg-white-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
    </a>')
                ->class(
                    'flex rounded-md ring-1 transition focus:ring-2
                        dark:text-white text-white
                        bg-black hover:bg-gray-800
                        border-0 py-2 px-2
                        focus:outline-none
                        sm:text-sm sm:leading-6
                        w-8 h-8 lg:w-9 lg:h-9 inline-flex items-center justify-center ml-1
                        focus:ring-black focus:ring-offset-1'
                );
        }

        if (Gate::allows('export-user')) {
            $headerArray[] = Button::add('export-data')
                ->slot('<a wire:click.prevent="exportData" href="#" title="Export User" data-testid="export_button" data-export-type="user"  class="flex items-center justify-center"  wire:target="exportData" onclick="handleExportClick(event)" x-bind:class="$wire.isExporting ? \'opacity-50 cursor-not-allowed\' : \'cursor-pointer\'" x-bind:disabled="$wire.isExporting" >
                        <svg class="h-5 w-5 text-white dark:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </a>
                ')
                ->class('
                    flex rounded-md ring-1 transition focus:ring-2
                    text-white bg-green-600 hover:bg-green-700
                    border-0 py-2 px-2
                    focus:outline-none
                    sm:text-sm sm:leading-6
                    w-8 h-8 lg:w-9 lg:h-9 inline-flex items-center justify-center ml-1
                    focus:ring-green-600 focus:ring-offset-1
                ');
        }

        if (Gate::allows('bulkDelete-user')) {
            $headerArray[] = Button::add('bulk-delete')
                ->slot('<div x-show="$wire.checkboxValues && $wire.checkboxValues.length > 0" x-transition>
                <div class="flex rounded-md ring-1 transition focus:ring-2
                        dark:text-white text-white
                        bg-red-600 hover:bg-red-600
                        border-0 py-2 px-2
                        focus:outline-none
                        sm:text-sm sm:leading-6
                        w-8 h-8 lg:w-9 lg:h-9 items-center justify-center ml-1
                        focus:ring-red focus:ring-offset-1 cursor-pointer"
                    data-testid="bulk_delete_button"
                    wire:click="bulkDelete"
                    title="Bulk Delete Users">
                    <svg class="h-5 w-5 text-pg-white-500 dark:text-pg-white-300"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                        </path>
                    </svg>
                </div>
            </div>
            ');
        }

        return $headerArray;
    }

    public function setUp(): array
    {
        $this->showCheckBox();

        return [

            PowerGrid::header(),

            PowerGrid::footer()
                ->showPerPage($this->perPage, $this->perPageValues)
                ->showRecordCount(), // For large datasets, use showRecordCount('min') for better performance, This avoids calculating the total record count and improves speed.
        ];
    }

    public function datasource(): Builder
    {
        // Main query
        return User::query()
            ->leftJoin('roles', 'roles.id', '=', 'users.role_id')
            ->select([
                'users.id',
                'roles.name as role_name',
                'users.first_name',
                'users.last_name',
                'users.mobile_number',
                DB::raw(
                    '(CASE
                                                WHEN users.status = "' . config('constants.user.status.key.active') . '" THEN  "' . config('constants.user.status.value.active') . '"
                                                WHEN users.status = "' . config('constants.user.status.key.inactive') . '" THEN  "' . config('constants.user.status.value.inactive') . '"
                                        ELSE " "
                                        END) AS status'
                ),
            ])->groupBy('users.id');
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')

            ->add('created_at_formatted', fn($row) => Carbon::parse($row->created_at)->format(config('constants.default_datetime_format')));
    }

    public function columns(): array
    {
        return [

            Column::make(__('messages.user.listing.id'), 'id')
                ->sortable()
                ->searchable(),

            Column::make(__('messages.user.listing.roles'), 'role_name')
                ->sortable()
                ->searchable(),

            Column::make(__('messages.user.listing.first_name'), 'first_name')
                ->sortable()
                ->searchable(),

            Column::make(__('messages.user.listing.last_name'), 'last_name')
                ->sortable()
                ->searchable(),

            Column::make(__('messages.user.listing.mobile_number'), 'mobile_number')
                ->sortable()
                ->searchable(),

            Column::make(__('messages.user.listing.status'), 'status')
                ->sortable()
                ->searchable(),
            Column::make(__('messages.created_date'), 'created_at_formatted', 'created_at'),
            Column::action(__('messages.user.listing.actions')),
        ];
    }

    public function filters(): array
    {
        return [

            Filter::select('role_name', 'roles.name')
                ->dataSource(\App\Models\Role::all())
                ->optionLabel('name')
                ->optionValue('name'),
            Filter::inputText('first_name', 'users.first_name')
                ->operators(['contains'])
                ->placeholder(__('messages.user.filter.first_name_placeholder')),
            Filter::inputText('last_name', 'users.last_name')
                ->operators(['contains'])
                ->placeholder(__('messages.user.filter.last_name_placeholder')),

            Filter::select('status', 'users.status')
                ->dataSource(User::status())
                ->optionLabel('label')
                ->optionValue('key'),
            Filter::datetimepicker('created_at'),
        ];
    }

    #[On('edit')]
    public function edit($id)
    {
        return $this->redirect('user/' . $id . '/edit', navigate: true); // redirect to edit component
    }

    public function actions(User $row): array
    {
        $actions = [];

        if (Gate::allows('show-user')) {
            $actions[] = Button::add('view')
                ->slot('<div title="' . __('messages.tooltip.view') . '" class="flex items-center justify-center" data-testid="view_button">' . view('components.flux.icon.eye', ['variant' => 'micro', 'attributes' => new \Illuminate\View\ComponentAttributeBag(['class' => ''])])->render() . '</div>')
                ->class('w-full sm:w-auto text-gray-600 bg-gray-200 hover:bg-gray-300 py-2 px-2 rounded text-lg cursor-pointer hover:cursor-pointer text-gray-500 hover:text-gray-900')
                ->dispatchTo('user.show', 'show-user-info', ['id' => $row->id]);
        }

        if (Gate::allows('edit-user')) {
            $actions[] = Button::add('edit')
                ->slot('<div title="Edit" class="flex items-center justify-center" data-testid="edit_button">' . view('components.flux.icon.pencil', ['variant' => 'micro', 'attributes' => new \Illuminate\View\ComponentAttributeBag(['class' => ''])])->render() . '</div>')
                ->class('w-full sm:w-auto text-gray-600 bg-gray-200 hover:bg-gray-300 py-2 px-2 rounded text-lg cursor-pointer hover:cursor-pointer text-gray-500 hover:text-gray-900')
                ->dispatch('edit', ['id' => $row->id]);
        }

        if (Gate::allows('delete-user')) {
            $actions[] = Button::add('delete-user')
                ->slot('<div title="' . __('messages.tooltip.click_delete') . '" class="flex items-center justify-center" data-testid="delete_button">' . view('components.flux.icon.trash', ['variant' => 'micro', 'attributes' => new \Illuminate\View\ComponentAttributeBag(['class' => ''])])->render() . '</div>')
                ->class('w-full h-8 sm:h-auto sm:w-auto bg-red-100 sm:bg-red-0 text-red-600 hover:bg-red-200 py-2 px-2 rounded text-lg cursor-pointer hover:cursor-pointer hover:text-red-800')
                ->dispatchTo('user.delete', 'delete-confirmation', ['ids' => [$row->id], 'tableName' => $this->tableName]);
        }

        return $actions;
    }

    public function actionRules($row): array
    {
        return [];
    }

    public function handlePageChange()
    {
        $this->checkboxAll = false;
        $this->checkboxValues = [];
    }

    #[On('deSelectCheckBoxEvent')]
    public function deSelectCheckBox(): bool
    {
        $this->checkboxAll = false;
        $this->checkboxValues = [];

        return true;
    }

    public function bulkDelete(): void
    {
        try {
            // Clear any existing error message
            if (! empty($this->checkboxValues)) {
                // Dispatch to the delete component
                $this->dispatch('bulk-delete-confirmation', [
                    'ids' => $this->checkboxValues,
                    'tableName' => $this->tableName,
                ]);
            } else {
                // Show flash message using Livewire event
                session()->flash('error', __('messages.bulk_delete.no_users_selected'));
            }
        } catch (Throwable $e) {
            // Defer logging to run after response
            defer(function () use ($e) {
                logger()->error('App\Livewire\User\Table: bulkDelete: Throwable', [
                    'Message' => $e->getMessage(),
                    'TraceAsString' => $e->getTraceAsString(),
                ]);
            });
            session()->flash('error', __('messages.bulk_delete.failed'));
        }
    }
}
