<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category', 50)->default('PORT_ADVISORY'); // PORT_ADVISORY, WEATHER_ALERT, REGULATORY, CARRIER_UPDATE, TRADE_LANE
            $table->text('summary');
            $table->longText('content');
            $table->string('image_url')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_breaking')->default(false);
            $table->boolean('is_published')->default(true);
            $table->dateTime('published_at');
            $table->timestamps();

            $table->index(['is_published', 'published_at']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};

