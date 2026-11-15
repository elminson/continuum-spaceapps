<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <meta name="description" content="Was this fire unusual? 26 years of NASA MODIS and VIIRS fire detections on one 1 km grid and one scale, with next-week outlooks and alerts. NASA Space Apps 2026, Team Continuum, Dominican Republic.">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="Continuum">
        <meta property="og:title" content="Continuum — Was this fire unusual?">
        <meta property="og:description" content="26 years of NASA fire detections on one scale: unusual weeks, fire seasons, next-week outlook and alerts for the Dominican Republic, Hispaniola, Cuba, Gran Chaco and the Cerrado.">
        <meta property="og:image" content="{{ url('/og.png') }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="Continuum — Was this fire unusual?">
        <meta name="twitter:description" content="26 years of NASA fire detections on one scale, with next-week outlooks and alerts. NASA Space Apps 2026.">
        <meta name="twitter:image" content="{{ url('/og.png') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @routes
        @vite(['resources/js/app.ts'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
