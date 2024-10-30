<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->json('authors');
            $table->integer('copies_count')->default(1);
            $table->integer('available_copies')->default(1);
            $table->integer('parts_count')->default(1);
            $table->string('publisher');
            $table->integer('edition_number');
            $table->string('dewey_category');
            $table->string('dewey_subcategory')->nullable();
            $table->decimal('price', 10, 2);
            $table->text('comments')->nullable();
            $table->string('central_number')->unique();
            $table->string('local_number')->unique();
            $table->date('publication_date');
            $table->date('acquisition_date');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};