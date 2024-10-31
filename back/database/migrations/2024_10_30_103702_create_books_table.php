<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->json('authors');
            $table->string('dewey_category');
            $table->string('dewey_subcategory')->nullable();
            $table->integer('copies_count')->default(1);
            $table->integer('available_copies')->default(1);
            $table->integer('parts_count')->default(1);
            $table->string('publisher')->nullable();
            $table->integer('edition_number')->default(1);
            $table->decimal('price', 8, 2)->nullable();
            $table->text('comments')->nullable();
            $table->string('central_number')->nullable();
            $table->string('local_number')->nullable();
            $table->date('publication_date')->nullable();
            $table->date('acquisition_date')->nullable();
            $table->timestamps();
            $table->softDeletes(); // Add this line for soft deletes
        });
    }

    public function down()
    {
        Schema::dropIfExists('books');
    }
};