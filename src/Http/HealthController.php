<?php

namespace Brightbulb\UptimeAgent\Http;

use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class HealthController
{
    public function __invoke(Request $request): JsonResponse
    {
        $started = microtime(true);
        $checks = [];
        $failed = null;

        foreach (array_filter(config('uptime-agent.checks', [])) as $name => $enabled) {
            try {
                $this->{'check'.Str::studly($name)}();
                $checks[$name] = 'ok';
            } catch (Throwable $e) {
                $checks[$name] = 'error';
                $failed ??= ucfirst($name).' check failed: '.Str::limit($e->getMessage(), 150);
            }
        }

        // A dead database means the site is effectively down; other failures are warnings.
        $dbDown = ($checks['database'] ?? null) === 'error';
        $status = $failed ? 'error' : 'ok';
        $httpCode = $dbDown ? 503 : 200;

        $expected = (string) config('uptime-agent.token');
        $given = (string) $request->header('X-Uptime-Token');
        $authed = $expected !== '' && $given !== '' && hash_equals($expected, $given);

        if (! $authed) {
            return $this->json(['status' => $status], $httpCode);
        }

        return $this->json(array_filter([
            'status' => $status,
            'message' => $failed,
            'agent' => 'brightbulb-uptime-laravel/1.0.0',
            'platform' => 'laravel',
            'environment' => app()->environment(),
            'versions' => [
                'laravel' => Application::VERSION,
                'php' => PHP_VERSION,
            ],
            'checks' => $checks + [
                'maintenance' => app()->isDownForMaintenance(),
                'debug' => (bool) config('app.debug'),
            ],
            'time_ms' => (int) round((microtime(true) - $started) * 1000),
        ], fn ($v) => $v !== null), $httpCode);
    }

    private function checkDatabase(): void
    {
        DB::connection()->select('select 1');
    }

    private function checkCache(): void
    {
        $key = 'uptime-agent:'.Str::random(8);
        Cache::put($key, 1, 10);
        if (Cache::pull($key) !== 1) {
            throw new \RuntimeException('cache read-back mismatch');
        }
    }

    private function checkStorage(): void
    {
        $path = '.uptime-agent-'.Str::random(8);
        Storage::put($path, 'ok');
        Storage::delete($path);
    }

    private function json(array $data, int $code): JsonResponse
    {
        return response()->json($data, $code, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
