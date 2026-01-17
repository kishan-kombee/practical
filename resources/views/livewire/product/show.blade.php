<div>
    <x-show-info-modal modalTitle="{{ __('messages.product.show.label_product') }}" :eventName="$event" :showSaveButton="false" :showCancelButton="false">
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                <flux:field class="border-b border-gray-200 dark:border-gray-700 gap-1!">
                    <flux:label>{{ __('messages.product.show.details.item_code') }}</flux:label>
                    <flux:description>{{ $product?->item_code ?? '-' }}</flux:description>
                </flux:field>
                <flux:field class="border-b border-gray-200 dark:border-gray-700 gap-1!">
                    <flux:label>{{ __('messages.product.show.details.name') }}</flux:label>
                    <flux:description>{{ $product?->name ?? '-' }}</flux:description>
                </flux:field>
                <flux:field class="border-b border-gray-200 dark:border-gray-700 gap-1!">
                    <flux:label>{{ __('messages.product.show.details.price') }}</flux:label>
                    <flux:description>{{ $product?->price ?? '-' }}</flux:description>
                </flux:field>
                <flux:field class="border-b border-gray-200 dark:border-gray-700 gap-1!">
                    <flux:label>{{ __('messages.product.show.details.description') }}</flux:label>
                    <flux:description>{{ $product?->description ?? '-' }}</flux:description>
                </flux:field>
                <flux:field class="border-b border-gray-200 dark:border-gray-700 gap-1!">
                    <flux:label>{{ __('messages.product.show.details.categories_name') }}</flux:label>
                    <flux:description>{{ !is_null($product) ? $product->categories_name : '-' }}</flux:description>
                </flux:field>
                <flux:field class="border-b border-gray-200 dark:border-gray-700 gap-1!">
                    <flux:label>{{ __('messages.product.show.details.sub_categories_name') }}</flux:label>
                    <flux:description>{{ !is_null($product) ? ($product->sub_categories_name ?? '-') : '-' }}</flux:description>
                </flux:field>
                <flux:field class="border-b border-gray-200 dark:border-gray-700 gap-1!">
                    <flux:label>{{ __('messages.product.show.details.available_status') }}</flux:label>
                    <flux:description>{{ $product?->available_status ?? '-' }}</flux:description>
                </flux:field>
                <flux:field class="border-b border-gray-200 dark:border-gray-700 gap-1!">
                    <flux:label>{{ __('messages.product.show.details.quantity') }}</flux:label>
                    <flux:description>{{ $product?->quantity ?? '-' }}</flux:description>
                </flux:field>
            </div>
        </div>
    </x-show-info-modal>
</div>