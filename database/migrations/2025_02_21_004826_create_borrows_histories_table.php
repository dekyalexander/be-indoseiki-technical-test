<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('borrows_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('borrows_id');
            $table->unsignedBigInteger('books_id');
            $table->unsignedBigInteger('user_id');
            $table->string('borrower_name');
            $table->date('borrow_date');
            $table->date('return_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('borrows_histories');
    }
};
