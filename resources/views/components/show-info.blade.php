@props(['key' => '', 'value' => ''])

<div class="grid grid-cols-2 gap-x-6 py-2 border-b border-gray-200 last:border-b-0 my-5">
    <!-- Key / Label -->
    <div class="text-sm font-semibold text-gray-700 dark:text-gray-200">
        {{ $key }}
    </div>

    <!-- Value -->
    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
        {{ $value }}
    </div>
</div>
