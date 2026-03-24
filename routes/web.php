<?php

use App\Services\MatchmakingService;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/redis-debug', function (MatchmakingService $matchmaking) {
    return view('redis-debug', [
        'redisInfo' => Redis::info(),
        'redisClient' => config('database.redis.client'),
        'queueSize' => $matchmaking->queueSize(),
        'queue' => Redis::zrange('matchmaking:queue', 0, -1, 'WITHSCORES'),
        'timestamps' => Redis::hgetall('matchmaking:timestamps'),
    ]);
});

Route::get('/cache-test', function () {
    $oldCacheValue = Cache::get('test');
    $oldCacheValue = $oldCacheValue ?? 0;
    Cache::put('test', $oldCacheValue + 1);

    return Cache::get('test');
});
