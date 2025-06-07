<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $packages = \App\Models\Package::query()
        ->where('code','JosephCrowell.Passage')
        ->get();
    dd($packages->first()->latestVersion);
})->purpose('Display an inspiring quote');
