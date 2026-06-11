<?php
require __DIR__."/vendor/autoload.php";
$app = require_once __DIR__."/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$routes = [
    "GET /reports/asset-register",
    "GET /reports/purchase-bills",
    "GET /reports/warranty-expiry",
    "GET /reports/extended-warranty-expiry",
    "GET /reports/amc-expiry",
    "GET /reports/insurance-expiry",
    "GET /reports/puc-expiry",
    "GET /reports/fitness-expiry",
    "GET /reports/road-tax-expiry",
    "GET /reports/inspection-due",
    "GET /reports/certification-expiry",
    "GET /reports/service-due",
    "GET /reports/service-history",
    "GET /reports/maintenance-cost",
    "GET /reports/vehicle-depreciation",
    "GET /reports/expiry",
    "GET /reports/service-due?expiry_filter=overdue",
    "GET /reports/service-due?expiry_filter=in30",
    "GET /reports/service-due?expiry_filter=in90",
    "GET /reports/warranty-expiry?expiry_filter=expired",
    "GET /reports/warranty-expiry?expiry_filter=in30",
    "GET /reports/amc-expiry?expiry_filter=expired",
    "GET /reports/amc-expiry?expiry_filter=in30",
    "GET /reports/insurance-expiry?expiry_filter=expired",
    "GET /reports/insurance-expiry?expiry_filter=in30",
    "GET /reports/extended-warranty-expiry?expiry_filter=expired",
    "GET /reports/certification-expiry?expiry_filter=expired",
    "GET /reports/certification-expiry?expiry_filter=in30",
    "GET /reports/puc-expiry?expiry_filter=overdue",
    "GET /reports/fitness-expiry?expiry_filter=overdue",
    // reminders page
    "GET /asset-reminders",
    "GET /asset-reminders?filter=upcoming",
    "GET /asset-reminders?filter=expired",
    "GET /asset-reminders?filter=all",
];

$pass = 0; $fail = 0;
$user = App\Models\User::first();

foreach ($routes as $r) {
    [$method, $uri] = explode(" ", $r, 2);
    [$path, $qs]    = array_pad(explode("?", $uri, 2), 2, "");
    $_GET = [];
    if ($qs) parse_str($qs, $_GET);

    $request = Illuminate\Http\Request::create($path, $method, $_GET);
    $request->setUserResolver(fn() => $user);
    $app->instance("request", $request);
    Illuminate\Support\Facades\Auth::setUser($user);

    // /reports/expiry is intentionally a redirect
    $expectedStatus = str_contains($uri, '/reports/expiry') && !str_contains($uri, 'expiry_filter') ? 302 : 200;

    $response = null;
    try {
        $response = $kernel->handle($request);
        $status   = $response->getStatusCode();
        if ($status === $expectedStatus) {
            $label = $status === 302 ? 'PASS(redirect)' : 'PASS';
            echo "$label $status  $r\n"; $pass++;
        } else {
            echo "FAIL $status  $r\n"; $fail++;
        }
    } catch (Throwable $e) {
        echo "ERR       $r\n       => " . get_class($e) . ": " . $e->getMessage() . "\n";
        $fail++;
    }
    if ($response) $kernel->terminate($request, $response);
}

echo "\n--- $pass passed, $fail failed ---\n";
