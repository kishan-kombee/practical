@props([
'rowIndex' => 0,
'childIndex' => null,
'parentId' => null,
])

@includeWhen(isset($setUp['responsive']), data_get($theme, 'root') . '.toggle-detail-responsive', [
'view' => data_get($setUp, 'detail.viewIcon') ?? null,
])

@php
$defaultCollapseIcon = data_get($theme, 'root') . '.toggle-detail';
@endphp

@includeWhen(data_get($setUp, 'detail.showCollapseIcon'),
data_get(collect($row->__powergrid_rules)->last(), 'toggleDetailView') ?? $defaultCollapseIcon,
[
'view' => data_get($setUp, 'detail.viewIcon') ?? null,
]
)

@includeWhen($radio && $radioAttribute, 'livewire-powergrid::components.radio-row', [
'attribute' => $row->{$radioAttribute},
])

@includeWhen($checkbox && $checkboxAttribute, 'livewire-powergrid::components.checkbox-row', [
'attribute' => $row->{$checkboxAttribute},
])

@php
// Count visible columns for toggle button
$hasMoreFields = false;
$visibleCount = 0;
$toggleButtonIndex = null;
$rowUniqueId = 'row-' . substr($rowId, 0, 6);
$visibleFieldIndices = [];

// First pass: collect all visible field indices
foreach ($columns as $idx => $col) {
$isAction = data_get($col, 'isAction');
$isHidden = data_get($col, 'hidden');
$isCheckbox = $checkbox && $checkboxAttribute && data_get($col, 'field') === $checkboxAttribute;
$hasTitle = !empty(strip_tags(data_get($col, 'title', '')));

// Exclude columns without titles (like profile with empty title)
if (!$isAction && !$isHidden && !$isCheckbox && $hasTitle) {
$visibleFieldIndices[] = $idx;
$visibleCount++;
}
}

// Set toggle button index after 3rd visible field
if (count($visibleFieldIndices) >= 3) {
$toggleButtonIndex = $visibleFieldIndices[2]; // Index of 3rd visible field (0-based, so index 2)
// Show toggle if there are more than 3 visible fields
if ($visibleCount > 3) {
$hasMoreFields = true;
}
}

// Debug: Ensure toggle shows when we have more than 3 fields
if ($visibleCount > 3 && $toggleButtonIndex === null) {
// Fallback: use the last visible field index if somehow toggleButtonIndex wasn't set
$toggleButtonIndex = end($visibleFieldIndices);
$hasMoreFields = true;
}
@endphp
@foreach ($columns as $columnIndex => $column)
@php
$field = data_get($column, 'field');
$content = $row->{$field} ?? '';
$templateContent = null;

if (is_array($content)) {
$template = data_get($column, 'template');
$templateContent = $content;
$content = '';
}

$contentClassField = data_get($column, 'contentClassField');
$content = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $content ?? '');
    $field = data_get($column, 'dataField', data_get($column, 'field'));

    $contentClass = data_get($column, 'contentClasses');

    if (is_array(data_get($column, 'contentClasses'))) {
    $contentClass = array_key_exists($content, data_get($column, 'contentClasses'))
    ? data_get($column, 'contentClasses')[$content]
    : '';
    }

    // Get column title for mobile responsive view (strip HTML tags)
    $columnTitle = strip_tags(data_get($column, 'title', ''));
    if (data_get($column, 'isAction')) {
    $columnTitle = trans('livewire-powergrid::datatable.labels.action');
    }

    // Determine if this column should be hidden by default on mobile
    $isAction = data_get($column, 'isAction');
    $isHidden = data_get($column, 'hidden');
    $isCheckbox = $checkbox && $checkboxAttribute && $field === $checkboxAttribute;
    $hasTitle = !empty(strip_tags(data_get($column, 'title', '')));

    // Exclude columns without titles from mobile view (like profile)
    if (!$isAction && !$isHidden && !$isCheckbox && !$hasTitle) {
    // Skip rendering this column in mobile view
    continue;
    }

    // Count visible columns before this one (excluding action, hidden, checkbox, and columns without titles)
    $visibleBeforeThis = 0;
    foreach ($columns as $idx => $col) {
    if ($idx >= $columnIndex) break;
    $colIsAction = data_get($col, 'isAction');
    $colIsHidden = data_get($col, 'hidden');
    $colIsCheckbox = $checkbox && $checkboxAttribute && data_get($col, 'field') === $checkboxAttribute;
    $colHasTitle = !empty(strip_tags(data_get($col, 'title', '')));
    if (!$colIsAction && !$colIsHidden && !$colIsCheckbox && $colHasTitle) {
    $visibleBeforeThis++;
    }
    }

    // Show first 3 fields by default, hide rest (except action and checkbox which are always shown)
    // Hidden columns should never be shown, even when expanded
    if ($isHidden) {
    $showByDefault = false;
    $mobileExpandClass = ''; // Don't add mobile-hidden-field to truly hidden columns
    } else {
    $showByDefault = $isAction || $isCheckbox || ($visibleBeforeThis < 3);
        $mobileExpandClass=$showByDefault ? '' : 'mobile-hidden-field' ;
        }
        @endphp
        <td
        @class([
        theme_style($theme, 'table.body.td' ),
        data_get($column, 'bodyClass' ),
        $mobileExpandClass,
        ])
        @style([ 'display:none'=> data_get($column, 'hidden'),
        data_get($column, 'bodyStyle'),
        ])
        wire:key="row-{{ substr($rowId, 0, 6) }}-{{ $field }}-{{ $childIndex ?? 0 }}"
        data-column="{{ data_get($column, 'isAction') ? 'actions' : $field }}"
        data-title="{{ $columnTitle }}"
        data-row-id="{{ $rowUniqueId }}"
        @if ($mobileExpandClass === 'mobile-hidden-field' && !$isHidden)
        x-data="pgRowExpand('{{ $rowUniqueId }}', false)"
        x-show="expanded"
        x-init
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @endif
        >
        @if (count(data_get($column, 'customContent')) > 0)
        @include(data_get($column, 'customContent.view'), data_get($column, 'customContent.params'))
        @else
        @if (data_get($column, 'isAction'))
        <div class="pg-actions">
            @if (method_exists($this, 'actionsFromView') && ($actionsFromView = $this->actionsFromView($row)))
            <div wire:key="actions-view-{{ data_get($row, $this->realPrimaryKey) }}">
                {!! $actionsFromView !!}
            </div>
            @endif

            <div wire:replace.self>
                @if (data_get($column, 'isAction'))
                <div
                    x-data="pgRenderActions({ rowId: @js(data_get($row, $this->realPrimaryKey)), parentId: @js($parentId) })"
                    class="{{ theme_style($theme, 'table.body.tdActionsContainer') }}"
                    x-html="toHtml">
                </div>
                @endif
            </div>
        </div>
        @endif

        @php
        $showEditOnClick = $this->shouldShowEditOnClick($column, $row);
        @endphp

        @if ($showEditOnClick === true)
        <span @class([$contentClassField, $contentClass])>
            @include(theme_style($theme, 'editable.view') ?? null, [
            'editable' => data_get($column, 'editable'),
            ])
        </span>
        @elseif(count(data_get($column, 'toggleable')) > 0)
        @php
        $showToggleable = $this->shouldShowToggleable($column, $row);
        @endphp
        @include(theme_style($theme, 'toggleable.view'), ['tableName' => $tableName])
        @else
        <span @class([$contentClassField, $contentClass])>
            @if (filled($templateContent))
            <div
                x-data="pgRenderRowTemplate({
                                parentId: @js($parentId),
                                templateContent: @js($templateContent)
                            })"
                x-html="rendered">
            </div>
            @else
            <div>{!! data_get($column, 'index') ? $rowIndex : $content !!}</div>
            @endif
        </span>
        @endif
        @endif
        </td>

        @php
        // Check if we should insert toggle button after this column
        $shouldInsertToggle = false;
        if ($hasMoreFields && $toggleButtonIndex !== null && $columnIndex === $toggleButtonIndex) {
        // Insert toggle button after the 3rd visible field
        $shouldInsertToggle = true;
        }
        @endphp

        @if ($shouldInsertToggle)
        {{-- Toggle button after 3rd visible field --}}
        <td
            class="mobile-toggle-cell"
            data-column="toggle"
            data-title=""
            data-row-id="{{ $rowUniqueId }}"
            x-data="pgRowExpand('{{ $rowUniqueId }}', false)"
            x-init>
            <button
                @click="toggleRow()"
                class="mobile-expand-toggle"
                type="button">
                <span x-show="!expanded">Show More</span>
                <span x-show="expanded">Show Less</span>
                <svg
                    class="w-4 h-4 inline-block ml-1 transition-transform"
                    x-bind:class="{ 'rotate-180': expanded }"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
        </td>
        @endif
        @endforeach