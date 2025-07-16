@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-flex; align-items:center;">
    <img src="{{ asset('img/clipsync.svg') }}" class="logo" alt="{{ config('app.name') }}">
<span class="clip-text" style="margin-left: -20px;">{{ config('app.name') }}</span>
</a>
</td>
</tr>
