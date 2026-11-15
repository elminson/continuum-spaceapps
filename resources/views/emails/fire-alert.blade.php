<!doctype html>
<html lang="{{ $es ? 'es' : 'en' }}">
<head><meta charset="utf-8"><title>{{ $es ? 'Alerta de fuego' : 'Fire alert' }}</title></head>
<body style="margin:0;padding:32px 16px;background:#f6f4f1;font-family:Instrument Sans,system-ui,-apple-system,Segoe UI,sans-serif;color:#17191c">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center">
<table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;background:#ffffff;border-radius:6px;border:1px solid #e7e5e1">
<tr><td style="padding:28px 28px 8px">
    <div style="font-size:13px;color:#6b7078">Continuum · {{ $es ? 'Alerta de fuego' : 'Fire alert' }}@if($test) · {{ $es ? 'prueba' : 'test' }}@endif</div>
    <h1 style="margin:8px 0 0;font-size:22px;font-weight:600;letter-spacing:-0.01em">
        <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:{{ $severity === 'extreme' ? '#c2410c' : ($severity === 'high' ? '#ea580c' : '#fb923c') }};margin-right:8px"></span>{{ $name }}
    </h1>
</td></tr>
<tr><td style="padding:8px 28px 20px;font-size:16px;line-height:1.55">{{ $text }}</td></tr>
<tr><td style="padding:0 28px 24px">
    <a href="{{ $mapUrl }}" style="display:inline-block;background:#17191c;color:#ffffff;text-decoration:none;font-size:14px;padding:10px 14px;border-radius:6px">{{ $es ? 'Ver el mapa de esa semana' : 'See the map of that week' }}</a>
    <a href="{{ $methodUrl }}" style="display:inline-block;margin-left:12px;color:#17191c;font-size:14px">{{ $es ? 'Cómo se decide una alerta' : 'How an alert is decided' }}</a>
</td></tr>
<tr><td style="padding:16px 28px 24px;border-top:1px solid #e7e5e1;font-size:12px;line-height:1.5;color:#6b7078">
    {{ $es ? 'Datos: NASA FIRMS (MODIS y VIIRS), armonizados por Continuum. Texto escrito con una plantilla fija y los números calculados; ningún modelo de IA interviene en este correo.' : 'Data: NASA FIRMS (MODIS and VIIRS), harmonized by Continuum. Written with a fixed template from the computed numbers; no AI model is involved in this email.' }}
    <br><a href="{{ $stopUrl }}" style="color:#6b7078">{{ $es ? 'Gestionar o detener estas alertas' : 'Manage or stop these alerts' }}</a>
</td></tr>
</table>
</td></tr></table>
</body>
</html>
