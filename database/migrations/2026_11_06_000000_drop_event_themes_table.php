<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('event_themes');
    }

    public function down(): void
    {
        Schema::create('event_themes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_default')->default(false);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('priority')->default(0);
            $table->json('colors')->nullable();
            $table->string('logo_override')->nullable();
            $table->string('banner_image')->nullable();
            $table->json('decorative_images')->nullable();
            $table->string('particle_effect')->nullable();
            $table->timestamps();
        });
    }
};
