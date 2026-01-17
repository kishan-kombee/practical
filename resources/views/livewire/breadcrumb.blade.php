<div class="w-full m-0 p-0">
    @if(!empty($segments))
    <div class="flex items-center space-x-1 text-sm justify-start m-0 p-0">
        <!-- Breadcrumb Items -->
        <div class="flex items-center space-x-1">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item>
                    <span class="{{ !empty($segments['title']) && empty($segments['item_1']) && empty($segments['item_2']) ? 'font-bold' : (!empty($segments['title']) ? 'font-bold' : '') }}">{!! isset($segments['title']) ? $segments['title'] : '' !!}</span>
                </flux:breadcrumbs.item>
                @if(!empty($segments['item_1']))
                <flux:breadcrumbs.item>{!! $segments['item_1'] !!}</flux:breadcrumbs.item>
                @endif
                @if(!empty($segments['item_2']))
                <flux:breadcrumbs.item>
                    <span class="font-bold">{!! $segments['item_2'] !!}</span>
                </flux:breadcrumbs.item>
                @endif
            </flux:breadcrumbs>
        </div>
    </div>
    @endif
</div>
