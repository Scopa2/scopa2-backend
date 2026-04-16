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
            $table->foreignId('player_1_id')->references('id')->on('users');
            $table->foreignId('player_2_id')->nullable()->references('id')->on('users');
            $table->string('seed');
            $table->string('status')->default('waiting');
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
