<div wire:key="flash-{{ uniqid() }}">
    @if (session()->has('success'))
        <div
            x-data="{ show: true }"
            x-init="setTimeout(() => show = false, 5000)"
            x-show="show"
            class="mb-4 flex items-center justify-between rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800 shadow"
            role="alert"
        >
            <span class="text-sm font-medium">{{ session('success') }}</span>
            <button type="button"
                class="ml-3 inline-flex h-6 w-6 items-center justify-center rounded-md text-green-700 hover:bg-green-100 focus:outline-none"
                x-on:click="show = false">
                <x-flux::icon name="x-mark" class="h-4 w-4" />
            </button>
        </div>
    @elseif (session()->has('warning'))
        <div
            x-data="{ show: true }"
            x-init="setTimeout(() => show = false, 5000)"
            x-show="show"
            class="mb-4 flex items-center justify-between rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-yellow-800 shadow"
            role="alert"
        >
            <span class="text-sm font-medium">{{ session('warning') }}</span>
            <button type="button"
                class="ml-3 inline-flex h-6 w-6 items-center justify-center rounded-md text-yellow-700 hover:bg-yellow-100 focus:outline-none"
                x-on:click="show = false">
                <x-flux::icon name="x-mark" class="h-4 w-4" />
            </button>
        </div>
    @elseif (session()->has('error'))
        <div
            x-data="{ show: true }"
            x-init="setTimeout(() => show = false, 5000)"
            x-show="show"
            class="mb-4 flex items-center justify-between rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800 shadow"
            role="alert"
        >
            <span class="text-sm font-medium">{{ session('error') }}</span>
            <button type="button"
                class="ml-3 inline-flex h-6 w-6 items-center justify-center rounded-md text-red-700 hover:bg-red-100 focus:outline-none"
                x-on:click="show = false">
                <x-flux::icon name="x-mark" class="h-4 w-4" />
            </button>
        </div>
    @endif
</div>
