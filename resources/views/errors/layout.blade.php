@php($es = in_array(request()->cookie('locale') ?: request()->getPreferredLanguage(['en', 'es']), ['es'], true))
<!DOCTYPE html>
<html lang="{{ ($es ? 'es' : 'en') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · Continuum</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet">
    <style>
        :root { color-scheme: light dark; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; font-family: 'Instrument Sans', system-ui, sans-serif; background: #ffffff; color: #17191c; }
        @media (prefers-color-scheme: dark) { body { background: #0f1113; color: #f2f1ee; } a.secondary { color: #9aa0a8; } }
        main { max-width: 32rem; padding: 2rem; text-align: center; }
        .code { font-size: 0.875rem; color: #ea580c; font-weight: 500; }
        h1 { margin: 0.5rem 0 0; font-size: 1.75rem; font-weight: 600; letter-spacing: -0.01em; }
        p { color: #6b7078; line-height: 1.5; }
        .actions { margin-top: 1.5rem; display: flex; gap: 1.25rem; justify-content: center; font-size: 0.9375rem; }
        a.primary { background: #17191c; color: #fff; padding: 0.5rem 0.875rem; border-radius: 6px; text-decoration: none; }
        @media (prefers-color-scheme: dark) { a.primary { background: #f2f1ee; color: #0f1113; } }
        a.secondary { color: #6b7078; text-decoration: underline; text-underline-offset: 4px; }
    </style>
</head>
<body>
    <main>
        <div class="code">{{ $code }} · Continuum</div>
        <h1>{{ $title }}</h1>
        <p>{{ $body }}</p>
        <div class="actions">
            <a class="primary" href="/">{{ $es ? 'Ir al inicio' : 'Go to the start' }}</a>
            <a class="secondary" href="/resume">{{ $es ? 'Mapa y números en vivo' : 'Live map and numbers' }}</a>
        </div>
    </main>
</body>
</html>
