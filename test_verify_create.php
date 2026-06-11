<?php
require __DIR__."/vendor/autoload.php";
$app = require_once __DIR__."/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$user = App\Models\User::first();
$request = Illuminate\Http\Request::create('/assets/create', 'GET');
$request->setUserResolver(fn() => $user);
$app->instance("request", $request);
Illuminate\Support\Facades\Auth::setUser($user);
$response = $kernel->handle($request);
$body = $response->getContent();

// Print the exact order scripts appear in <head>
preg_match_all('/<script[^>]*>.*?<\/script>|<script[^>]+src="[^"]+"/s', $body, $scripts);
$headEnd = strpos($body, '</head>');
echo "Scripts in page (in order):\n";
foreach ($scripts[0] as $s) {
    $inHead = strpos($body, $s) < $headEnd;
    $preview = trim(substr(strip_tags($s), 0, 80));
    $src = '';
    if (preg_match('/src="([^"]+)"/', $s, $m)) $src = 'src=' . basename($m[1]);
    echo "  [" . ($inHead ? 'HEAD' : 'BODY') . "] " . ($src ?: substr($preview, 0, 80)) . "\n";
}

// Show the exact initDatePickers snippet
$pos = strpos($body, 'initDatePickers');
echo "\n--- initDatePickers context ---\n";
echo substr($body, max(0, $pos - 50), 400) . "\n";
