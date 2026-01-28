<?php
// database/migrations/2024_01_01_000001_create_scopa_tables.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('player_1_id')->nullable();
            $table->string('player_2_id')->nullable(); // 'bot' se PVE
            $table->string('seed'); // Per rigenerare mazzo e shop
            $table->string('status')->default('waiting'); // waiting, playing, finished
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
