@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
<table cellpadding="0" cellspacing="0" border="0" role="presentation" style="margin: 0 auto;">
<tr>
<td align="center" style="padding-bottom: 8px;">
<!--[if mso]>
<v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" style="height:48px;width:48px;" arcsize="22%" fillcolor="#4f46e5" stroke="f">
<v:textbox style="mso-fit-shape-to-text:false;" inset="0,0,0,0">
<center style="color:#ffffff;font-family:'Segoe UI',sans-serif;font-size:28px;font-weight:700;">S</center>
</v:textbox>
</v:roundrect>
<![endif]-->
<!--[if !mso]><!-->
<div style="width: 48px; height: 48px; background: linear-gradient(135deg, #6366f1, #4f46e5); background-color: #4f46e5; border-radius: 11px; display: flex; align-items: center; justify-content: center; font-family: 'Figtree', 'Segoe UI', sans-serif; font-size: 28px; font-weight: 700; color: #ffffff;">S</div>
<!--<![endif]-->
</td>
</tr>
<tr>
<td align="center" style="font-family: 'Figtree', 'Segoe UI', sans-serif; font-size: 19px; font-weight: 700; color: #4f46e5;">
{!! $slot !!}
</td>
</tr>
</table>
</a>
</td>
</tr>
