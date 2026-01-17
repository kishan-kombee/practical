@props([
    'title',
    'description',
    'showBack' => false, // optional back button toggle
])

<div class="flex w-full flex-col text-center relative">
    <flux:heading size="xl">{{ $title }}</flux:heading>

    <flux:subheading class="flex items-center justify-center gap-2">
        @if ($showBack)
            <button type="button" wire:click="back" data-testid="back_button"
                class="flex items-center cursor-pointer justify-center w-6 h-6 rounded-lg border border-zinc-300 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
        @endif
        <span>{{ $description }}</span>
    </flux:subheading>
</div>
