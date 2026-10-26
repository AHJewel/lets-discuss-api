<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $services = [
            'database' => $this->probe(fn () => DB::connection()->getPdo()),
            'redis' => $this->probe(fn () => Redis::connection()->ping()),
        ];

        $healthy = ! in_array('down', $services, true);

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'services' => $services,
        ], $healthy ? 200 : 503);
    }

    /**
     * @param  callable(): mixed  $check
     * @return 'up'|'down'
     */
    private function probe(callable $check): string
    {
        try {
            $check();

            return 'up';
        } catch (Throwable) {
            return 'down';
        }
    }
}
