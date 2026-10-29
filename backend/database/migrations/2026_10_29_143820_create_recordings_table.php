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
        Schema::create('recordings', function (Blueprint $table) {
            $table->id();
            $table->string('segment_id')->unique()->index();
            $table->string('filename');
            $table->string('file_path');
            $table->string('mime_type')->default('audio/mp4');
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->unsignedInteger('duration_ms')->default(0);
            $table->string('checksum')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->string('device_token')->nullable();
            $table->string('status')->default('UPLOADED');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recordings');
    }
};
