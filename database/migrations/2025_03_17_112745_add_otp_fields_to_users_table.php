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
        Schema::table('users', function (Blueprint $table) {
            $table->string('otp')->nullable()->after('phone');
            $table->timestamp('otp_expires_at')->nullable()->after('otp');
            $table->integer('otp_attempts')->default(0)->after('otp_expires_at');
            $table->timestamp('last_otp_requested_at')->nullable()->after('otp_attempts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['otp', 'otp_expires_at', 'otp_attempts', 'last_otp_requested_at']);
        });
    }
};
