@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.url')])
{!! $body['header'] !!}
@endcomponent
@endslot

{{-- Body --}}
<!-- Body here -->
{!! $body['body'] !!}


@if($body['link'])
@component('mail::button', ['url' => $body['link']])
Import Info
@endcomponent
@endif

{{-- Subcopy --}}
@slot('subcopy')
@component('mail::subcopy')
{!! $body['signature'] !!}
@endcomponent
@endslot


{{-- Footer --}}
@slot('footer')
@component('mail::footer')
{!! $body['footer'] !!}
@endcomponent
@endslot
@endcomponent