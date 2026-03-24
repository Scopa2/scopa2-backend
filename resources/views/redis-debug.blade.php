<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redis Debug - Scopa 2</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @import "tailwindcss";
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen p-8">
    <div class="max-w-6xl mx-auto">
        <header class="mb-12">
            <h1 class="text-4xl font-bold text-indigo-400">Redis & Matchmaking Debug</h1>
            <p class="text-slate-400 mt-2">Internal state of the matchmaking service</p>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
            <div class="bg-slate-800 border border-slate-700 rounded-xl p-6 shadow-xl">
                <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-wider mb-4">Queue Size</h2>
                <div class="text-5xl font-mono text-indigo-400 font-bold">{{ $queueSize }}</div>
                <p class="text-slate-500 mt-2 text-sm">Active players in matchmaking</p>
            </div>

            <div class="bg-slate-800 border border-slate-700 rounded-xl p-6 shadow-xl">
                <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-wider mb-4">Redis Client</h2>
                <div class="text-2xl font-mono text-emerald-400 font-bold">{{ $redisClient }}</div>
                <p class="text-slate-500 mt-2 text-sm">Connected via phpredis-sentinel</p>
            </div>

            <div class="bg-slate-800 border border-slate-700 rounded-xl p-6 shadow-xl">
                <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-wider mb-4">Server Uptime</h2>
                <div class="text-2xl font-mono text-amber-400 font-bold">{{ $redisInfo['uptime_in_days'] ?? '0' }} days</div>
                <p class="text-slate-500 mt-2 text-sm">Redis instance availability</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Queue Inspector -->
            <div class="bg-slate-800 border border-slate-700 rounded-xl overflow-hidden shadow-2xl">
                <div class="px-6 py-4 bg-slate-700/50 border-b border-slate-700">
                    <h2 class="text-xl font-semibold">Matchmaking Queue (ZSET)</h2>
                </div>
                <div class="p-6">
                    @if(count($queue) > 0)
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-slate-400 text-sm border-b border-slate-700">
                                    <th class="pb-3 font-semibold">User ID</th>
                                    <th class="pb-3 font-semibold">ELO (Score)</th>
                                    <th class="pb-3 font-semibold">Wait Time</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700 font-mono text-sm">
                                @foreach($queue as $userId => $elo)
                                    <tr>
                                        <td class="py-3 text-indigo-300">{{ $userId }}</td>
                                        <td class="py-3 text-amber-300">{{ $elo }}</td>
                                        <td class="py-3 text-slate-400">
                                            {{ now()->timestamp - ($timestamps[$userId] ?? now()->timestamp) }}s
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="text-center py-12">
                            <p class="text-slate-500">The queue is currently empty.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Redis Info -->
            <div class="bg-slate-800 border border-slate-700 rounded-xl overflow-hidden shadow-2xl">
                <div class="px-6 py-4 bg-slate-700/50 border-b border-slate-700">
                    <h2 class="text-xl font-semibold">Native Redis Info</h2>
                </div>
                <div class="p-0 overflow-auto max-h-[500px]">
                    <table class="w-full text-left">
                        <tbody class="divide-y divide-slate-700 font-mono text-xs">
                            @foreach($redisInfo as $key => $value)
                                <tr class="hover:bg-slate-700/30">
                                    <td class="px-6 py-2 text-slate-400 font-semibold">{{ $key }}</td>
                                    <td class="px-6 py-2 text-emerald-300">
                                        @if(is_array($value))
                                            {{ json_encode($value) }}
                                        @else
                                            {{ $value }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
