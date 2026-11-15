<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('fire:ingest')->hourlyAt(10)->withoutOverlapping()->runInBackground();

// Alerts: check every subscribed box once a day, after the morning ingest (08:00 in Santo Domingo).
Schedule::command('fire:notify')->dailyAt('12:20')->withoutOverlapping()->runInBackground();
// Deliver queued emails without a resident worker.
Schedule::command('queue:work database --stop-when-empty --max-time=50 --tries=3')->everyMinute()->withoutOverlapping()->runInBackground();

// Weather for the forecast: NASA POWER publishes daily values with a lag of a few days; refresh the last two weeks every morning.
Schedule::command('fire:weather')->dailyAt('11:40')->withoutOverlapping()->runInBackground();
