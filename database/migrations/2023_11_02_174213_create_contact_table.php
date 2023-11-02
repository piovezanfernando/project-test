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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->comment('Contact name');
            $table->string('phone', 20)->nullable()->comment('Contact phone');
            $table->string('email', 100)->nullable()->comment('Contact email');
            $table->softDeletes();
            $table->timestamps();
            $table->createUpdateDeleteUserId();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
