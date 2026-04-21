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
            $table->string('active_whatsapp')->nullable()->after('whatsapp_number');
        });

        $retainedActiveWhatsapps = [];

        DB::table('rekrutmen_job_applications')
            ->select(['id', 'job_posting_id', 'whatsapp_number', 'deleted_at'])
            ->orderBy('job_posting_id')
            ->orderBy('id')
            ->get()
            ->each(function (object $application) use (&$retainedActiveWhatsapps): void {
                $normalizedWhatsapp = normalize_job_application_whatsapp($application->whatsapp_number ?? null);

                $activeWhatsapp = null;

                if ($application->deleted_at === null && is_string($normalizedWhatsapp) && $normalizedWhatsapp !== '') {
                    $dedupeKey = $application->job_posting_id.'|'.$normalizedWhatsapp;

                    if (! array_key_exists($dedupeKey, $retainedActiveWhatsapps)) {
                        $retainedActiveWhatsapps[$dedupeKey] = true;
                        $activeWhatsapp = $normalizedWhatsapp;
                    }
                }

                DB::table('rekrutmen_job_applications')
                    ->where('id', $application->id)
                    ->update(['active_whatsapp' => $activeWhatsapp]);
            });

        Schema::table('rekrutmen_job_applications', function (Blueprint $table) {
            $table->unique(['job_posting_id', 'active_whatsapp'], 'rekrutmen_job_applications_active_whatsapp_unique');
        });
    }

    public function down(): void
    {
        Schema::table('rekrutmen_job_applications', function (Blueprint $table) {
            $table->dropUnique('rekrutmen_job_applications_active_whatsapp_unique');
            $table->dropColumn('active_whatsapp');
        });
    }
};

if (! function_exists('normalize_job_application_whatsapp')) {
    function normalize_job_application_whatsapp(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        if ($normalized === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $normalized);

        if (! is_string($digits) || $digits === '') {
            return null;
        }

        if (str_starts_with($digits, '62')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        if (str_starts_with($digits, '8')) {
            return '62'.$digits;
        }

        return $digits;
    }
}
