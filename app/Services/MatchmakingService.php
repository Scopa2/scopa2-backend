<?php

namespace App\Services;

use App\Enums\GameStateEnum;
use App\Events\MatchFound;
use App\Models\Game;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class MatchmakingService
{
    private const QUEUE_KEY = 'matchmaking:queue';

    private const TIMESTAMPS_KEY = 'matchmaking:timestamps';

    /**
     * Add a user to the matchmaking queue.
     * Uses a Redis ZSET with ELO as score for efficient range queries.
     */
    public function enqueue(User $user): void
    {
        $userId = (string) $user->id;

        Redis::zadd(self::QUEUE_KEY, $user->elo, $userId);
        Redis::hset(self::TIMESTAMPS_KEY, $userId, now()->timestamp);
    }

    /**
     * Remove a user from the matchmaking queue.
     */
    public function dequeue(User $user): void
    {
        $userId = (string) $user->id;

        Redis::zrem(self::QUEUE_KEY, $userId);
        Redis::hdel(self::TIMESTAMPS_KEY, $userId);
    }

    /**
     * Check if a user is currently in the matchmaking queue.
     */
    public function isQueued(User $user): bool
    {
        return Redis::zscore(self::QUEUE_KEY, (string) $user->id) !== false;
    }

    /**
     * Get the number of users currently in the queue.
     */
    public function queueSize(): int
    {
        return (int) Redis::zcard(self::QUEUE_KEY);
    }

    /**
     * Atomically find and match compatible pairs from the queue.
     *
     * Uses a Lua script to guarantee atomicity across multiple pods —
     * two pods cannot pop the same player from the ZSET.
     *
     * @return array<int, array{game_id: string, player_1_id: string, player_2_id: string}>
     */
    public function findMatches(): array
    {
        $lua = $this->getMatchmakingLuaScript();

        $eloWindowBase = config('matchmaking.elo_window_base');
        $eloExpandRate = config('matchmaking.elo_expand_rate');
        $eloExpandInterval = config('matchmaking.elo_expand_interval');
        $eloWindowMax = config('matchmaking.elo_window_max');
        $currentTime = now()->timestamp;

        /** @var array<int, string> $results */
        $results = Redis::eval(
            $lua,
            2,
            self::QUEUE_KEY,
            self::TIMESTAMPS_KEY,
            $eloWindowBase,
            $eloExpandRate,
            $eloExpandInterval,
            $eloWindowMax,
            $currentTime,
        );

        $matches = [];

        // Lua returns flat pairs: [userId1, userId2, userId3, userId4, ...]
        for ($i = 0; $i < count($results); $i += 2) {
            $player1Id = $results[$i];
            $player2Id = $results[$i + 1];

            $game = $this->createGame($player1Id, $player2Id);

            if ($game) {
                $matches[] = [
                    'game_id' => $game->id,
                    'player_1_id' => $player1Id,
                    'player_2_id' => $player2Id,
                ];

                broadcast(new MatchFound($game->id, $player1Id, $player2Id));

                Log::info('Matchmaking: paired players', [
                    'game_id' => $game->id,
                    'player_1' => $player1Id,
                    'player_2' => $player2Id,
                ]);
            }
        }

        return $matches;
    }

    /**
     * Create a new game for the matched pair.
     */
    private function createGame(string $player1Id, string $player2Id): ?Game
    {
        return Game::create([
            'id' => (string) Str::uuid(),
            'player_1_id' => $player1Id,
            'player_2_id' => $player2Id,
            'seed' => Str::random(16),
            'status' => GameStateEnum::PLAYING,
        ]);
    }

    /**
     * Lua script for atomic matchmaking.
     *
     * Iterates the ZSET in ELO order. For each unmatched player, looks ahead
     * for the closest ELO neighbour within that player's dynamic window.
     * Matched pairs are atomically removed from both the ZSET and the
     * timestamps hash, preventing double-matching across pods.
     */
    private function getMatchmakingLuaScript(): string
    {
        return <<<'LUA'
            local queue_key = KEYS[1]
            local timestamps_key = KEYS[2]
            local elo_base = tonumber(ARGV[1])
            local elo_rate = tonumber(ARGV[2])
            local elo_interval = tonumber(ARGV[3])
            local elo_max = tonumber(ARGV[4])
            local current_time = tonumber(ARGV[5])

            local members = redis.call('ZRANGEBYSCORE', queue_key, '-inf', '+inf', 'WITHSCORES')
            local matched = {}
            local skip = {}
            local results = {}

            -- Build ordered list of {id, elo}
            local players = {}
            for i = 1, #members, 2 do
                table.insert(players, {id = members[i], elo = tonumber(members[i + 1])})
            end

            for i = 1, #players do
                if not skip[players[i].id] then
                    local p1 = players[i]
                    local p1_ts = tonumber(redis.call('HGET', timestamps_key, p1.id) or current_time)
                    local p1_wait = current_time - p1_ts
                    local p1_window = math.min(
                        elo_base + math.floor(p1_wait / elo_interval) * elo_rate,
                        elo_max
                    )

                    for j = i + 1, #players do
                        if not skip[players[j].id] then
                            local p2 = players[j]
                            local p2_ts = tonumber(redis.call('HGET', timestamps_key, p2.id) or current_time)
                            local p2_wait = current_time - p2_ts
                            local p2_window = math.min(
                                elo_base + math.floor(p2_wait / elo_interval) * elo_rate,
                                elo_max
                            )

                            local elo_diff = math.abs(p1.elo - p2.elo)
                            -- Both players must accept the other within their window
                            if elo_diff <= p1_window and elo_diff <= p2_window then
                                -- Remove both atomically
                                redis.call('ZREM', queue_key, p1.id)
                                redis.call('ZREM', queue_key, p2.id)
                                redis.call('HDEL', timestamps_key, p1.id)
                                redis.call('HDEL', timestamps_key, p2.id)

                                table.insert(results, p1.id)
                                table.insert(results, p2.id)

                                skip[p1.id] = true
                                skip[p2.id] = true
                                break
                            end
                        end
                    end
                end
            end

            return results
        LUA;
    }
}
