<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['pending', 'ready', 'completed', 'cancelled'])->default('pending');
            $table->integer('queue_position')->default(0);
            $table->boolean('notification_sent')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            // Un utilisateur ne peut avoir qu'une réservation active par livre
            $table->unique(['user_id', 'book_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};