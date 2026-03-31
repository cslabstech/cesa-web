<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rekrutmen_job_applications', function (Blueprint $table) {
            $table->string('gender')->nullable()->after('full_name');
            $table->date('birth_date')->nullable()->after('gender');
            $table->string('marital_status')->nullable()->after('birth_date');
            $table->text('address_ktp')->nullable()->after('marital_status');
            $table->text('address_domicile')->nullable()->after('address_ktp');
            $table->string('whatsapp_number')->nullable()->after('address_domicile');
            $table->string('active_phone')->nullable()->after('whatsapp_number');
            $table->string('emergency_contact_name')->nullable()->after('active_phone');
            $table->string('emergency_contact_relation')->nullable()->after('emergency_contact_name');
            $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_relation');
            $table->string('photo_path')->nullable()->after('emergency_contact_phone');
        });

        DB::table('rekrutmen_job_applications')
            ->update([
                'whatsapp_number' => DB::raw('phone'),
                'active_phone'    => DB::raw('phone'),
            ]);

        Schema::table('rekrutmen_job_applications', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'cover_letter',
                'portfolio_url',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('rekrutmen_job_applications', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->text('cover_letter')->nullable()->after('resume_path');
            $table->string('portfolio_url')->nullable()->after('cover_letter');
        });

        DB::table('rekrutmen_job_applications')
            ->update([
                'phone' => DB::raw('active_phone'),
            ]);

        Schema::table('rekrutmen_job_applications', function (Blueprint $table) {
            $table->dropColumn([
                'gender',
                'birth_date',
                'marital_status',
                'address_ktp',
                'address_domicile',
                'whatsapp_number',
                'active_phone',
                'emergency_contact_name',
                'emergency_contact_relation',
                'emergency_contact_phone',
                'photo_path',
            ]);
        });
    }
};
