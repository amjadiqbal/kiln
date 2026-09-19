<?php

namespace AmjadIqbal\Kiln\Http\Controllers;

use AmjadIqbal\Kiln\OpcacheManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Runs inside an actual FPM worker (or whatever SAPI serves this request),
 * unlike `php artisan kiln:*` which runs in a separate CLI process with its
 * own OPcache segment. This is the reliable way to invalidate the shared
 * memory an FPM pool's workers actually serve from — hit it from a deploy
 * script instead of, or in addition to, the artisan commands.
 */
class OpcacheController extends Controller
{
    public function clear(OpcacheManager $opcache): JsonResponse
    {
        if (! $opcache->isActive()) {
            return response()->json(['ok' => false, 'message' => 'OPcache is not active in this process.'], 500);
        }

        $ok = $opcache->clear();

        return response()->json(['ok' => $ok]);
    }

    public function warm(OpcacheManager $opcache): JsonResponse
    {
        if (! $opcache->isActive()) {
            return response()->json(['ok' => false, 'message' => 'OPcache is not active in this process.'], 500);
        }

        $paths = config('kiln.warm_paths', []);

        if (config('kiln.warm_vendor', false)) {
            $paths[] = 'vendor';
        }

        $directories = array_map(fn (string $path) => base_path($path), $paths);

        $result = $opcache->warm($directories);

        return response()->json(['ok' => true] + $result);
    }

    public function status(OpcacheManager $opcache): JsonResponse
    {
        if (! $opcache->isActive()) {
            return response()->json(['ok' => false, 'message' => 'OPcache is not active in this process.'], 500);
        }

        return response()->json(['ok' => true, 'status' => $opcache->status()]);
    }
}
