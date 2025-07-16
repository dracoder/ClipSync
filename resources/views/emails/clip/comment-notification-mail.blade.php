@component('mail::message')
{{ __('hello') }}, {{ $clipOwner }}!

{!! __('comment_notification_mail_text', [
    'commenter' => $commenter,
    'clipTitle' => $clipTitle,
]) !!}

@if (!empty($commentText))
<div style="
    background-color: #f8f9fa;
    border-left: 4px solid #fbbf24;
    border-radius: 4px;
    margin: 16px 0;
    padding: 16px;
    font-style: italic;
    color: #4b5563;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
    <p style="
        margin: 0;
        font-size: 16px;
        line-height: 1.5;">
        "{{ $commentText }}"
    </p>
    <div style="
        margin-top: 8px;
        font-size: 14px;
        color: #6b7280;
        text-align: right;">
        — {{ $commenter }}
    </div>
</div>
@endif

<x-mail::button :url="$commentUrl">
{{ __('click_to_view_comment') }}
</x-mail::button>

{{ __('thanks')}},<br>
{{ config('app.name') }}
@endcomponent