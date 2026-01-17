
<div class="flex h-full w-full flex-1 flex-col gap-0 rounded-xl">
    <!-- Session Messages -->
    <x-session-message></x-session-message>
    <x-export-progress-bar></x-export-progress-bar>
    <!-- PowerGrid Table with integrated header -->
    <div class="bg-white dark:bg-gray-800 lg:rounded-xl lg:shadow-sm border-none lg:border border-neutral-200 dark:border-neutral-700 p-0 lg:p-2">
        <livewire:role.table />
    </div>
    <!-- Delete Component -->
    <livewire:role.delete />
    <!-- Show Component -->
    <x-slide-panel />
</div>
