<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('service_type', 40)->default('consultation')->after('duration_minutes');
            $table->string('visit_type', 20)->default('in_person')->after('service_type');
            $table->index(['service_type', 'appointment_date']);
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['service_type', 'appointment_date']);
            $table->dropColumn(['service_type', 'visit_type']);
        });
    }
};
