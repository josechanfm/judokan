@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'MJA')
<img src="/images/site_logo.png" class="logo" alt="MJA logo">
@else
{{ $slot }}
@endif
</a>
</td>
</tr>
