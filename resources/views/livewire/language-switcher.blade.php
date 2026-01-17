<flux:dropdown position="top" align="end">
    <flux:button variant="ghost" class="h-9 px-3 text-sm group cursor-pointer" icon:trailing="chevron-down">
        {{ $this->currentLangLabel }}
    </flux:button>

    <flux:menu class="min-w-40">
        <flux:menu.radio.group>
            @foreach ($languages as $label => $code)
                <flux:menu.radio class="cursor-pointer" :checked="$currentLang === $code"
                    wire:click="switchLang('{{ $code }}')">
                    {{ $label }}
                </flux:menu.radio>
            @endforeach
        </flux:menu.radio.group>
    </flux:menu>
</flux:dropdown>
