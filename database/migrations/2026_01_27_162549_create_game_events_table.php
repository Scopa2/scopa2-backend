<?php
// database/migrations/2024_01_01_000001_create_scopa_tables.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('game_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('game_id');
            $table->integer('sequence_number'); // 1, 2, 3...
            $table->string('actor_id'); // Chi ha fatto l'azione
            $table->text('pgn_action'); // Es: "$GEN(3C+4D)" oppure "7Dx7C"
            $table->timestamps();

            $table->unique(['game_id', 'sequence_number']); // Concurrency protection
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_events');
    }
};
