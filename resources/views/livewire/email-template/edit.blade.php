<div class="flex h-full w-full flex-1 flex-col gap-0 rounded-xl">
    <form wire:submit.prevent="store" class="space-y-0">
        <!-- Card: Header with Title and Status Toggle -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-neutral-200 dark:border-neutral-700">
            <div class="flex items-center justify-between py-4 px-0 ml-6 mr-6 border-b border-neutral-200 dark:border-neutral-700">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">{{ $label }}</h3>
                <div x-data="{ status: @entangle('status') }" class="flex items-center gap-3">
                    <label for="status_switch" class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer"
                        x-text="status === 'Y' ? 'Active' : 'Inactive'">
                    </label>
                    <flux:switch id="status_switch" data-testid="status" class="cursor-pointer"
                        x-bind:checked="status === 'Y'"
                        x-on:change="$wire.set('status', $event.target.checked ? 'Y' : 'N')" />
                </div>
            </div>

            <div class="p-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Side: Form Fields -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Subject Input -->
                    <flux:field>
                        <flux:label for="subject" required>{{ __('messages.email_template.create.label_subject') }}
                        </flux:label>
                        <flux:input id="subject" wire:model="subject" autofocus />
                        <flux:error name="subject" />
                    </flux:field>

                    <!-- Body Editor -->
                    <flux:field>
                        <flux:label for="body" required>{{ __('messages.email_template.create.label_body') }}
                        </flux:label>
                        <x-flux.editor wireModel="body" id="body" height="500px" />
                        <flux:error name="body" />
                    </flux:field>
                </div>

                <!-- Right Side: Legends Accordion -->
                <div class="lg:col-span-1">
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Available Placeholders</h4>
                        <div class="space-y-2">
                            @php $index = 1; @endphp
                            @foreach ($legendsArray as $category => $legends)
                                @php
                                    $isFirst = $index === 1;
                                    $openState = $isFirst ? 'true' : 'false';
                                @endphp
                                <div x-data="{ open: {{ $openState }} }"
                                    class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                                    <button type="button" @click="open = !open"
                                        class="w-full px-4 py-3 flex items-center justify-between bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                        <span
                                            class="font-medium text-gray-900 dark:text-white">{{ $category }}</span>
                                        <flux:icon.chevron-down class="w-4 h-4 transition-transform"
                                            x-bind:class="open ? 'rotate-180' : ''" />
                                    </button>
                                    <div x-show="open" x-transition class="p-4 bg-white dark:bg-gray-800">
                                        @if (is_array($legends))
                                            @foreach ($legends as $legend)
                                                <div x-data="{ copied: false }"
                                                    @click="navigator.clipboard.writeText('{{ $legend }}').then(() => { copied = true; setTimeout(() => copied = false, 2000); })"
                                                    class="text-sm text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 cursor-pointer py-2 border-b border-gray-100 dark:border-gray-700 last:border-0 transition group"
                                                    title="Click to copy">
                                                    <span x-text="copied ? '✓ Copied!' : '{{ $legend }}'"
                                                        :class="copied ? 'text-green-600 dark:text-green-400 font-semibold' : ''"
                                                        class="transition">
                                                    </span>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="text-sm text-gray-700 dark:text-gray-300">
                                                No placeholders available
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                @php $index++; @endphp
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

        </div>
         <!-- Action Buttons -->
         <div class="flex items-center justify-top gap-3 mt-3 lg:mt-3 border-t-2 lg:border-none border-gray-100 py-4 lg:py-0">
                <flux:button type="submit" variant="primary" data-testid="submit_button" class="cursor-pointer h-8! lg:h-9!"
                    wire:loading.attr="disabled" wire:target="store">
                    {{ __('messages.submit_button_text') }}
                </flux:button>
                <flux:button type="button" href="{{ route('email-template.index') }}" wire:navigate class="cursor-pointer">
                    {{ __('messages.cancel_button_text') }}
                </flux:button>
            </div>
    </form>
</div>