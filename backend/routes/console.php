<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('scan:send-reminders')->dailyAt('08:00')->timezone('Europe/Riga')->withoutOverlapping();
