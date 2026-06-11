<?php
require __DIR__."/vendor/autoload.php";
$app = require_once __DIR__."/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$user  = App\Models\User::first();
$asset = App\Models\Asset::first();

$routes = [
    "GET /assets/create",
    "GET /assets/{$asset->id}/edit",
    "GET /assets/{$asset->id}?tab=services",
    "GET /assets/{$asset->id}?tab=amc",
    "GET /assets/{$asset->id}?tab=insurance",
    "GET /reports/service-history",
    "GET /reports/maintenance-cost",
];

$pass = 0; $fail = 0;
foreach ($routes as $r) {
    [$method, $uri] = explode(" ", $r, 2);
    $request = Illuminate\Http\Request::create($uri, $method);
    $request->setUserResolver(fn() => $user);
    $app->instance("request", $request);
    Illuminate\Support\Facades\Auth::setUser($user);
    try {
        $response = $kernel->handle($request);
        $status   = $response->getStatusCode();
        // Check component rendered in body
        $body = $response->getContent();
        $hasPicker = str_contains($body, 'flatpickr') || str_contains($body, 'x-date-picker') || str_contains($body, 'date-picker');
        echo ($status === 200 ? "PASS" : "FAIL") . " $status  $r\n";
        $status === 200 ? $pass++ : $fail++;
    } catch (Throwable $e) {
        echo "ERR   $r\n      => " . $e->getMessage() . "\n"; $fail++;
    }
}
echo "\n--- $pass passed, $fail failed ---\n";
