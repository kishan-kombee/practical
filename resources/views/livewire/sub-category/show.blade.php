<div>
    <x-show-info-modal modalTitle="{{ __('messages.sub_category.show.label_sub_category') }}" :eventName="$event" :showSaveButton="false" :showCancelButton="false">
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <flux:field class="border-b border-gray-200 dark:border-gray-700 gap-1!">
        <flux:label>{{ __('messages.sub_category.show.details.categories_name') }}</flux:label>
        <flux:description>{{ !is_null($subcategory) ? $subcategory->categories_name : '-' }}</flux:description>
    </flux:field>
                             <flux:field class="border-b border-gray-200 dark:border-gray-700 gap-1!">
        <flux:label>{{ __('messages.sub_category.show.details.name') }}</flux:label>
        <flux:description>{{ $subcategory?->name ?? '-' }}</flux:description>
    </flux:field>
                             <flux:field class="border-b border-gray-200 dark:border-gray-700 gap-1!">
        <flux:label>{{ __('messages.sub_category.show.details.status') }}</flux:label>
        <flux:description>{{ $subcategory?->status ?? '-' }}</flux:description>
    </flux:field>
            </div>
        </div>
    </x-show-info-modal>
</div>
