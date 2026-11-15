<!doctype html>
<html lang="{{ $es ? 'es' : 'en' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width">
<meta name="color-scheme" content="light">
<title>{{ $es ? 'Alerta de fuego' : 'Fire alert' }} · {{ $name }}</title>
</head>
<body style="margin:0;padding:0;background:#f6f4f1;font-family:'Instrument Sans',system-ui,-apple-system,'Segoe UI',Helvetica,Arial,sans-serif;color:#17191c;-webkit-font-smoothing:antialiased">
<div style="display:none;max-height:0;overflow:hidden;opacity:0">{{ $preheader }}</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f4f1"><tr><td align="center" style="padding:32px 16px">
<table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%">

    <tr><td style="padding:0 4px 12px;font-size:13px;color:#6b7078">
        <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#ea580c;vertical-align:middle;margin-right:8px"></span><span style="vertical-align:middle;font-weight:600;color:#17191c">Continuum</span>
        <span style="vertical-align:middle"> · {{ $es ? 'Alerta de fuego' : 'Fire alert' }}@if($test) · {{ $es ? 'prueba' : 'test' }}@endif</span>
    </td></tr>

    <tr><td style="background:#ffffff;border:1px solid #e7e5e1;border-radius:6px;padding:28px 28px 24px">
        <div style="font-size:13px;color:#6b7078">{{ $name }} · {{ $weekLabel }}</div>
        <div style="margin-top:10px;font-size:52px;line-height:1;font-weight:600;letter-spacing:-0.02em;color:{{ $severity === 'extreme' ? '#c2410c' : '#ea580c' }};font-variant-numeric:tabular-nums">
            <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:{{ $severity === 'extreme' ? '#c2410c' : ($severity === 'high' ? '#ea580c' : '#fb923c') }};vertical-align:18px;margin-right:6px"></span>×{{ $times }}
        </div>
        <div style="margin-top:6px;font-size:15px;color:#17191c">{{ $es ? 'lo normal para esa semana' : 'normal for that week' }}</div>

        <table role="presentation" cellpadding="0" cellspacing="0" style="margin-top:22px;width:100%;font-variant-numeric:tabular-nums">
            <tr>
                <td style="padding:0 16px 0 0;vertical-align:top">
                    <div style="font-size:22px;font-weight:600">{{ $observed }}</div>
                    <div style="font-size:12px;color:#6b7078">{{ $es ? 'celdas-día de 1 km con fuego' : 'burning 1 km cell-days' }}</div>
                </td>
                <td style="padding:0 16px;vertical-align:top;border-left:1px solid #e7e5e1">
                    <div style="font-size:22px;font-weight:600">{{ $typical }}</div>
                    <div style="font-size:12px;color:#6b7078">{{ $es ? 'típico, misma semana de otros años' : 'typical, same week in other years' }}</div>
                </td>
                <td style="padding:0 0 0 16px;vertical-align:top;border-left:1px solid #e7e5e1">
                    <div style="font-size:22px;font-weight:600">{{ $severityLabel }}</div>
                    <div style="font-size:12px;color:#6b7078">{{ $es ? 'nivel (score :s)' : 'level (score :s)' }}</div>
                </td>
            </tr>
        </table>

        <p style="margin:22px 0 0;font-size:16px;line-height:1.55">{{ $text }}</p>

        <table role="presentation" cellpadding="0" cellspacing="0" style="margin-top:24px"><tr>
            <td style="background:#17191c;border-radius:6px"><a href="{{ $mapUrl }}" style="display:inline-block;padding:11px 16px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:500">{{ $es ? 'Ver el mapa de esa semana' : 'See the map of that week' }}</a></td>
            <td style="padding-left:14px"><a href="{{ $methodUrl }}" style="color:#17191c;font-size:14px">{{ $es ? 'Cómo se decide una alerta' : 'How an alert is decided' }}</a></td>
        </tr></table>
    </td></tr>

    <tr><td style="padding:16px 4px 0;font-size:12px;line-height:1.5;color:#6b7078">
        {{ $es ? 'Datos: NASA FIRMS (MODIS y VIIRS), puestos en una cuadrícula de 1 km y una sola escala por Continuum. La semana se compara con la misma semana de todos los demás años desde 2000 (mediana y MAD). Texto escrito con una plantilla fija a partir de los números calculados; ningún modelo de IA interviene en este correo.' : 'Data: NASA FIRMS (MODIS and VIIRS), put on one 1 km grid and one scale by Continuum. The week is compared with the same week in every other year since 2000 (median and MAD). Written with a fixed template from the computed numbers; no AI model is involved in this email.' }}
        <br><br><a href="{{ $stopUrl }}" style="color:#6b7078">{{ $es ? 'Gestionar o detener estas alertas' : 'Manage or stop these alerts' }}</a> · <a href="{{ $siteUrl }}" style="color:#6b7078">continuum-spaceapps.isotherm.app</a>
    </td></tr>
</table>
</td></tr></table>
</body>
</html>
