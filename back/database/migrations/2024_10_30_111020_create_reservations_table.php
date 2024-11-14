<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            $table->enum('status', [
                'pending',    // Initial state when user requests a book
                'ready',      // Book is ready for pickup
                'accepted',   // Librarian accepted the reservation
                'delivered',  // Book has been given to user
                'returned',   // Book has been returned to library
                'cancelled'   // Reservation was cancelled
            ])->default('pending');

            // Tracking positions and dates
            $table->integer('queue_position')->nullable();
            $table->timestamp('expires_at')->nullable();    // For 'ready' status expiration
            $table->timestamp('accepted_at')->nullable();   // When librarian accepts
            $table->timestamp('delivered_at')->nullable();  // When book is given to user
            $table->timestamp('returned_at')->nullable();   // When book is returned
            $table->timestamp('due_date')->nullable();      // When book must be returned
            $table->timestamps();

            // Add indexes for common queries
            $table->index(['status', 'user_id']);
            $table->index(['status', 'book_id']);
            $table->index('due_date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('reservations');
    }
};
