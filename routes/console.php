<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('packages:build-index-from-packagist')->daily();
