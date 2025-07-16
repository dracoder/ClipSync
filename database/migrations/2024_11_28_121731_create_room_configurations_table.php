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
        Schema::create('room_configurations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room_id');
            $table->longText('configuration')->nullable();
            $table->boolean('restrict_guest')->nullable()->default(false);
            $table->string('guest_password')->nullable();
            $table->string('admin_password')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_configurations');
    }
};
