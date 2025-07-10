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
        Schema::create('state_regions', function (Blueprint $table) {
            $table->id();
            $table->string('SR_Code')->unique();
            $table->string('SR_Name');
            $table->string('SR_Name_MMR');
            $table->unsignedBigInteger('modifiled_by')->nullable();
            $table->timestamp('modifiled_on')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('state_regions');
    }
};
