<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$logs = DB::table('asset_audit_logs')->latest('id')->take(5)->get();
foreach ($logs as $l) {
    echo $l->event . ' | ' . $l->description . PHP_EOL;
    echo '  old=' . ($l->old_values ?? 'NULL') . PHP_EOL;
    echo '  new=' . ($l->new_values ?? 'NULL') . PHP_EOL;
    echo PHP_EOL;
}
