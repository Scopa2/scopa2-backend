<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ELO Window Base
    |--------------------------------------------------------------------------
    |
    | The initial ELO range within which two players can be matched.
    | A player with 1000 ELO will match against 900–1100 with a base of 100.
    |
    */

    'elo_window_base' => (int) env('MATCHMAKING_ELO_WINDOW_BASE', 100),

    /*
    |--------------------------------------------------------------------------
    | ELO Window Expand Rate
    |--------------------------------------------------------------------------
    |
    | How many additional ELO points the window expands per expansion interval.
    | Linear expansion: after 10s with interval=5 and rate=50, the window
    | becomes base + 2*50 = 200.
    |
    */

    'elo_expand_rate' => (int) env('MATCHMAKING_ELO_EXPAND_RATE', 50),

    /*
    |--------------------------------------------------------------------------
    | ELO Window Expand Interval (seconds)
    |--------------------------------------------------------------------------
    |
    | How often (in seconds) the ELO window expands by the expand rate.
    |
    */

    'elo_expand_interval' => (int) env('MATCHMAKING_ELO_EXPAND_INTERVAL', 5),

    /*
    |--------------------------------------------------------------------------
    | Maximum ELO Window
    |--------------------------------------------------------------------------
    |
    | The absolute maximum ELO difference allowed for a match, regardless
    | of wait time. Prevents absurd pairings (e.g., 400 vs 2000).
    |
    */

    'elo_window_max' => (int) env('MATCHMAKING_ELO_WINDOW_MAX', 500),

];
