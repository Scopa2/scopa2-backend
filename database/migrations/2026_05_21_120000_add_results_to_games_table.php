<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('games', function (Blueprint $t) {
            $t->foreignId('winner_id')->nullable()->after('player_2_id')->references('id')->on('users');
            $t->unsignedInteger('final_score_p1')->nullable()->after('winner_id');
            $t->unsignedInteger('final_score_p2')->nullable()->after('final_score_p1');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $t) {
            $t->dropColumn(['winner_id', 'final_score_p1', 'final_score_p2']);
        });
    }
};
