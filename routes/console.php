<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 期限切れの見積もりを毎時0分にクローズ
Schedule::command('estimates:close-expired')->hourly();

// 入札期限バッチ処理を毎時5分に実行
Schedule::command('bid:process-deadline')->hourlyAt(5);
