<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('counties', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 50)->index();
        });

        Schema::create('cities', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('zip_code')->index();
            $table->string('city', 50)->index();
            $table->foreignId('id_county')->constrained('counties')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
        Schema::dropIfExists('counties');
    }
};
