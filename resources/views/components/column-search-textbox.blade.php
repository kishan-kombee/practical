@props(['placeholder' => ''])
<div class="powergrid-column-search">
    <flux:input type="text" placeholder="{{ $placeholder }}" {{ $attributes->get('inputAttributes') }}
        class="form-control" />
    <!-- <flux:icon icon="magnifying-glass" class="input-search-icon" /> -->
</div>
