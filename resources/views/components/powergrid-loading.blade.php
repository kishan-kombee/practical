@props(['tableName' => null])
<style>
    @keyframes shimmer {
        0% {
            background-position: -1000px 0;
        }
        100% {
            background-position: 1000px 0;
        }
    }
    
    .skeleton-shimmer {
        background: linear-gradient(
            90deg,
            #e5e7eb 0%,
            #f3f4f6 50%,
            #e5e7eb 100%
        );
        background-size: 1000px 100%;
        animation: shimmer 2s infinite;
    }
    
    .dark .skeleton-shimmer {
        background: linear-gradient(
            90deg,
            #3f3f46 0%,
            #52525b 50%,
            #3f3f46 100%
        );
        background-size: 1000px 100%;
    }
</style>
<div wire:loading @if($tableName) data-table-name="{{ $tableName }}" @endif class="w-full">
    <div class="p-6 space-y-4">
        {{-- Table Header Skeleton - Hidden on mobile --}}
        <div class="hidden md:flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="h-9 skeleton-shimmer rounded-lg w-36"></div>
                <div class="h-9 skeleton-shimmer rounded-lg w-28"></div>
            </div>
            <div class="flex items-center gap-2">
                <div class="h-9 skeleton-shimmer rounded-lg w-24"></div>
                <div class="h-9 skeleton-shimmer rounded-lg w-24"></div>
            </div>
        </div>

        {{-- Desktop Table Rows Skeleton --}}
        <div class="hidden md:block">
            @for ($row = 0; $row < 8; $row++)
                <div class="flex items-center border-b border-gray-100 dark:border-zinc-800 last:border-b-0">
                    <div class="px-4 py-3 w-16 flex items-center">
                        <div class="h-4 w-4 skeleton-shimmer rounded"></div>
                    </div>
                    
                    <div class="px-4 py-3 w-20 flex items-center">
                        <div class="h-4 skeleton-shimmer rounded w-12"></div>
                    </div>
                    
                    <div class="px-4 py-3 w-35 flex items-center">
                        <div class="h-4 skeleton-shimmer rounded w-35"></div>
                    </div>

                    <div class="px-4 py-3 w-40 flex items-center">
                        <div class="h-4 skeleton-shimmer rounded w-40"></div>
                    </div>
                    
                    <div class="px-4 py-3 w-24 flex items-center">
                        <div class="h-6 skeleton-shimmer rounded-full w-24"></div>
                    </div>
                    
                    <div class="px-4 py-3 w-28 flex items-center">
                        <div class="h-4 skeleton-shimmer rounded w-28"></div>
                    </div>
                    
                    <div class="px-4 py-3 w-28 flex items-center">
                        <div class="h-4 skeleton-shimmer rounded w-28"></div>
                    </div>

                    <div class="px-4 py-3 w-35 flex items-center">
                        <div class="h-4 skeleton-shimmer rounded w-35"></div>
                    </div>

                    <div class="px-4 py-3 w-40 flex items-center">
                        <div class="h-4 skeleton-shimmer rounded w-40"></div>
                    </div>
                    
                    <div class="px-4 py-3 w-24 flex items-center">
                        <div class="h-6 skeleton-shimmer rounded-full w-24"></div>
                    </div>
                    
                    <div class="px-4 py-3 w-28 flex items-center">
                        <div class="h-4 skeleton-shimmer rounded w-28"></div>
                    </div>
                    
                    <div class="px-4 py-3 w-24 flex items-center">
                        <div class="h-6 skeleton-shimmer rounded-full w-24"></div>
                    </div>
                    
                    <div class="px-4 py-3 w-32 flex items-center justify-end gap-2">
                        <div class="h-8 w-8 skeleton-shimmer rounded"></div>
                        <div class="h-8 w-8 skeleton-shimmer rounded"></div>
                        <div class="h-8 w-8 skeleton-shimmer rounded"></div>
                    </div>
                </div>
            @endfor
        </div>

        {{-- Mobile Card Skeleton --}}
        <div class="block md:hidden space-y-2 px-2">
            @for ($row = 0; $row < 5; $row++)
                <div class="border border-gray-200 dark:border-gray-700 rounded shadow-sm bg-white dark:bg-gray-800 p-0 overflow-hidden transition-shadow hover:shadow-md">
                    {{-- Checkbox --}}
                    <div class="flex items-center px-2 py-2 border-b border-gray-100 dark:border-gray-700 min-h-[2.5rem]">
                        <div class="h-4 w-4 skeleton-shimmer rounded"></div>
                    </div>
                    
                    {{-- First visible field (ID) --}}
                    <div class="flex items-start gap-2 px-2 py-1.5 border-b border-gray-100 dark:border-gray-700 min-h-[2.25rem]">
                        <div class="h-3.5 skeleton-shimmer rounded w-8 flex-shrink-0 mt-0.5"></div>
                        <div class="h-3.5 skeleton-shimmer rounded w-16"></div>
                    </div>
                    
                    {{-- Second visible field (Name) --}}
                    <div class="flex items-start gap-2 px-2 py-1.5 border-b border-gray-100 dark:border-gray-700 min-h-[2.25rem]">
                        <div class="h-3.5 skeleton-shimmer rounded w-12 flex-shrink-0 mt-0.5"></div>
                        <div class="h-3.5 skeleton-shimmer rounded w-40"></div>
                    </div>
                    
                    {{-- Third visible field (Email) --}}
                    <div class="flex items-start gap-2 px-2 py-1.5 border-b border-gray-100 dark:border-gray-700 min-h-[2.25rem]">
                        <div class="h-3.5 skeleton-shimmer rounded w-12 flex-shrink-0 mt-0.5"></div>
                        <div class="h-3.5 skeleton-shimmer rounded w-48"></div>
                    </div>
                    
                    {{-- Fourth visible field (Role Badge) --}}
                    <div class="flex items-start gap-2 px-2 py-1.5 border-b border-gray-100 dark:border-gray-700 min-h-[2.25rem]">
                        <div class="h-3.5 skeleton-shimmer rounded w-10 flex-shrink-0 mt-0.5"></div>
                        <div class="h-5 skeleton-shimmer rounded-full w-20"></div>
                    </div>
                    
                    {{-- Toggle button (Read More) --}}
                    <div class="text-center py-2 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 min-h-[2.5rem] flex items-center justify-center">
                        <div class="h-7 skeleton-shimmer rounded w-28"></div>
                    </div>
                    
                    {{-- Actions --}}
                    <div class="flex items-center justify-center gap-2 px-2 py-2 border-t-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 min-h-[3rem]">
                        <div class="h-9 w-9 skeleton-shimmer rounded"></div>
                        <div class="h-9 w-9 skeleton-shimmer rounded"></div>
                        <div class="h-9 w-9 skeleton-shimmer rounded"></div>
                    </div>
                </div>
            @endfor
        </div>

        {{-- Footer Skeleton --}}
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 mt-4 border-t border-gray-200 dark:border-zinc-700 px-2">
            <div class="h-4 skeleton-shimmer rounded w-32"></div>
            <div class="flex items-center gap-2">
                <div class="h-8 skeleton-shimmer rounded w-20"></div>
                <div class="h-8 skeleton-shimmer rounded w-20"></div>
                <div class="h-8 skeleton-shimmer rounded w-20"></div>
            </div>
        </div>
    </div>
</div>
