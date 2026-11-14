<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('fire:ingest')->hourlyAt(10)->withoutOverlapping()->runInBackground();
