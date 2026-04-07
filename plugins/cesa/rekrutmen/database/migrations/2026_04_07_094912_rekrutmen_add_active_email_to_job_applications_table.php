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
            $table->string('active_email')->nullable()->after('email');
        });

        $retainedActiveEmails = [];

        DB::table('rekrutmen_job_applications')
            ->select(['id', 'job_posting_id', 'email', 'deleted_at'])
            ->orderBy('job_posting_id')
            ->orderBy('id')
            ->get()
            ->each(function (object $application) use (&$retainedActiveEmails): void {
                $normalizedEmail = is_string($application->email)
                    ? strtolower(trim($application->email))
                    : null;

                $activeEmail = null;

                if ($application->deleted_at === null && is_string($normalizedEmail) && $normalizedEmail !== '') {
                    $dedupeKey = $application->job_posting_id.'|'.$normalizedEmail;

                    if (! array_key_exists($dedupeKey, $retainedActiveEmails)) {
                        $retainedActiveEmails[$dedupeKey] = true;
                        $activeEmail = $normalizedEmail;
                    }
                }

                DB::table('rekrutmen_job_applications')
                    ->where('id', $application->id)
                    ->update(['active_email' => $activeEmail]);
            });

        Schema::table('rekrutmen_job_applications', function (Blueprint $table) {
            $table->unique(['job_posting_id', 'active_email'], 'rekrutmen_job_applications_active_email_unique');
        });
    }

    public function down(): void
    {
        Schema::table('rekrutmen_job_applications', function (Blueprint $table) {
            $table->dropUnique('rekrutmen_job_applications_active_email_unique');
            $table->dropColumn('active_email');
        });
    }
};
