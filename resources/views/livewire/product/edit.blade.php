<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl" x-data="productValidation()" x-init="init()">
    <form wire:submit="store" class="space-y-3" @submit="handleSubmit($event)">
        <!-- Basic Information Section -->
        <div class="bg-white dark:bg-gray-800 rounded-xl lg:border border-gray-200 dark:border-gray-700 p-2 lg:p-6">
            <div class="grid grid-cols-1 md:grid-cols-2  gap-4 lg:gap-6 mb-0">
                <div class="flex-1">
                    <flux:field>
                        <flux:label for="item_code" required>{{ __('messages.product.create.label_item_code') }} <span class="text-red-500">*</span></flux:label>
                        <flux:input
                            type="text"
                            data-testid="item_code"
                            id="item_code"
                            wire:model.blur="item_code"
                            x-on:blur="validateItemCode($wire.item_code)"
                            placeholder="Enter {{ __('messages.product.create.label_item_code') }}"
                            required />
                        <flux:error name="item_code" data-testid="item_code_error" />
                        <div x-show="hasError('item_code') && isTouched('item_code')"
                            x-text="getError('item_code')"
                            class="mt-1 text-sm text-red-600"
                            data-product-error></div>
                    </flux:field>
                </div>
                <div class="flex-1">
                    <flux:field>
                        <flux:label for="name" required>{{ __('messages.product.create.label_name') }} <span class="text-red-500">*</span></flux:label>
                        <flux:input
                            type="text"
                            data-testid="name"
                            id="name"
                            wire:model.blur="name"
                            x-on:blur="validateName($wire.name)"
                            placeholder="Enter {{ __('messages.product.create.label_name') }}"
                            required />
                        <flux:error name="name" data-testid="name_error" />
                        <div x-show="hasError('name') && isTouched('name')"
                            x-text="getError('name')"
                            class="mt-1 text-sm text-red-600"
                            data-product-error></div>
                    </flux:field>
                </div>
                <div class="flex-1">
                    <flux:field>
                        <flux:label for="price" required>{{ __('messages.product.create.label_price') }} <span class="text-red-500">*</span></flux:label>
                        <flux:input
                            type="text"
                            data-testid="price"
                            id="price"
                            wire:model.blur="price"
                            x-on:blur="validatePrice($wire.price)"
                            x-on:input="if($wire.price) validatePrice($wire.price)"
                            placeholder="Enter {{ __('messages.product.create.label_price') }} (e.g., 10.99)"
                            required />
                        <flux:error name="price" data-testid="price_error" />
                        <div x-show="hasError('price') && isTouched('price')"
                            x-text="getError('price')"
                            class="mt-1 text-sm text-red-600"
                            data-product-error></div>
                    </flux:field>
                </div>
                <div class="flex-1">
                    <flux:field>
                        <flux:label for="description" required>{{ __('messages.product.create.label_description') }} <span class="text-red-500">*</span></flux:label>
                        <flux:input
                            type="text"
                            data-testid="description"
                            id="description"
                            wire:model.blur="description"
                            x-on:blur="validateDescription($wire.description)"
                            placeholder="Enter {{ __('messages.product.create.label_description') }}"
                            required />
                        <flux:error name="description" data-testid="description_error" />
                        <div x-show="hasError('description') && isTouched('description')"
                            x-text="getError('description')"
                            class="mt-1 text-sm text-red-600"
                            data-product-error></div>
                    </flux:field>
                </div>
                <div class="flex-1">
                    <x-flux.autocomplete
                        name="category_id"
                        data-testid="category_id"
                        labeltext="{{ __('messages.product.create.label_categories') }}"
                        placeholder="{{ __('messages.product.create.label_categories') }}"
                        :options="$categories"
                        displayOptions="10"
                        wire:model.live="category_id"
                        x-on:change="validateCategory($wire.category_id)"
                        :required="true" />
                    <flux:error name="category_id" data-testid="category_id_error" />
                    <div x-show="hasError('category_id') && isTouched('category_id')"
                        x-text="getError('category_id')"
                        class="mt-1 text-sm text-red-600"
                        data-product-error></div>
                </div>
                <div class="flex-1" wire:key="sub_category_wrapper_{{ $category_id }}">
                    <x-flux.autocomplete
                        name="sub_category_id"
                        data-testid="sub_category_id"
                        labeltext="{{ __('messages.product.create.label_sub_category_id') }}"
                        placeholder="{{ empty($category_id) ? __('messages.product.create.placeholder_select_category_first') : __('messages.product.create.label_sub_category_id') }}"
                        :options="$sub_categories"
                        displayOptions="10"
                        wire:model="sub_category_id"
                        x-on:change="validateSubCategory($wire.sub_category_id)"
                        :disabled="empty($category_id)"
                        :required="true" />
                    <flux:error name="sub_category_id" data-testid="sub_category_id_error" />
                    <div x-show="hasError('sub_category_id') && isTouched('sub_category_id')"
                        x-text="getError('sub_category_id')"
                        class="mt-1 text-sm text-red-600"
                        data-product-error></div>
                    @if(empty($category_id))
                    <flux:description class="text-sm text-gray-500 mt-1">{{ __('messages.product.create.select_category_first') }}</flux:description>
                    @endif
                </div>
                <div class="flex-1" x-data="{ available_status: @entangle('available_status') }">
                    <flux:field>
                        <flux:label for="available_status_switch">{{ __('messages.product.create.label_available_status') }}
                            <span class="text-red-500">*</span>
                        </flux:label>
                        <div class="flex items-center gap-3">
                            <flux:switch
                                id="available_status_switch"
                                data-testid="available_status"
                                x-bind:checked="available_status === '1'"
                                x-on:change="$wire.set('available_status', $event.target.checked ? '1' : '0')"
                                class="cursor-pointer" />
                            <label for="available_status_switch"
                                class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer"
                                x-text="available_status === '1' ? 'Available' : 'Not-available'">
                            </label>
                        </div>
                        <flux:error name="available_status" data-testid="available_status_error" />
                    </flux:field>
                </div>
                <div class="flex-1">
                    <flux:field>
                        <flux:label for="quantity">{{ __('messages.product.create.label_quantity') }} </flux:label>
                        <flux:input
                            type="text"
                            data-testid="quantity"
                            id="quantity"
                            wire:model.blur="quantity"
                            x-on:blur="validateQuantity($wire.quantity)"
                            x-on:input="if($wire.quantity) validateQuantity($wire.quantity)"
                            placeholder="Enter {{ __('messages.product.create.label_quantity') }} (e.g., 10)" />
                        <flux:error name="quantity" data-testid="quantity_error" />
                        <div x-show="hasError('quantity') && isTouched('quantity')"
                            x-text="getError('quantity')"
                            class="mt-1 text-sm text-red-600"
                            data-product-error></div>
                    </flux:field>
                </div>
            </div>
        </div>



        <!-- Action Buttons -->
        <div class="flex items-center justify-top gap-3 mt-3 lg:mt-3 border-t-2 lg:border-none border-gray-100 py-4 lg:py-0">

            <flux:button
                type="submit"
                variant="primary"
                data-testid="submit_button"
                class="cursor-pointer h-8! lg:h-9!"
                wire:loading.attr="disabled"
                wire:target="store"
                @click="validateAll()">
                {{ __('messages.update_button_text') }}
            </flux:button>

            <flux:button type="button" data-testid="cancel_button" class="cursor-pointer h-8! lg:h-9!" variant="outline" href="/product" wire:navigate>
                {{ __('messages.cancel_button_text') }}
            </flux:button>
        </div>
    </form>
</div>