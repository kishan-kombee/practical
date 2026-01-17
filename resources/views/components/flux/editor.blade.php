@props([
    'placeholder' => '',
    'wireModel' => '',
    'error' => '',
    'description' => '',
    'id' => null,
    'height' => '200px',
    'toolbar' => 'full', // 'basic', 'full', 'minimal'
])

@php
    $editorId = $id ?? $wireModel . '_editor';
    $toolbarConfig = [
        'basic' => [
            ['bold', 'italic', 'underline'],
            ['link'],
            [['list' => 'ordered'], ['list' => 'bullet']],
            ['clean']
        ],
        'minimal' => [
            ['bold', 'italic'],
            ['clean']
        ],
        'full' => [
            [['header' => [1, 2, 3, 4, 5, 6, false]]],
            [['size' => ['small', false, 'large', 'huge']]],
            ['bold', 'italic', 'underline', 'strike'],
            [['color' => []], ['background' => []]],
            [['script' => 'sub'], ['script' => 'super']],
            ['blockquote', 'code-block'],
            [['list' => 'ordered'], ['list' => 'bullet']],
            [['indent' => '-1'], ['indent' => '+1']],
            [['direction' => 'rtl']],
            [['align' => []]],
            ['link', 'image', 'video'],
            ['formula', 'code'],
            ['clean']
        ]
    ];
    $toolbarJson = json_encode($toolbarConfig[$toolbar]);
@endphp

<div class="relative" wire:ignore>
    <div
        id="{{ $editorId }}"
        class="quill-editor border border-gray-300 dark:border-gray-600 rounded-md focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-blue-500"
        style="height: {{ $height }}"
        data-wire-model="{{ $wireModel }}"
        data-placeholder="{{ $placeholder }}"
        data-toolbar="{{ $toolbar }}"
    ></div>

    <!-- Hidden input for Livewire -->
    <input
        type="hidden"
        wire:model="{{ $wireModel }}"
        id="{{ $wireModel }}_hidden"
    />
</div>

@if($description)
    <flux:description>{{ $description }}</flux:description>
@endif

@if($error)
    <flux:error name="{{ $error }}" data-testid="{{ $error }}_error"/>
@endif

@push('scripts')
<script>
// Initialize this specific editor when component loads
document.addEventListener('DOMContentLoaded', function() {
    if (typeof initQuillEditors === 'function') {
        initQuillEditors();
    }
});
</script>
@endpush
