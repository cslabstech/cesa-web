<?php

namespace Cesa\Rekrutmen\Console\Commands;

use Cesa\Rekrutmen\Models\JobApplication;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SyncCandidateCvCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'rekrutmen:sync-cv {--dry-run : Hanya cek pencocokan tanpa mengubah database}';

    /**
     * @var string
     */
    protected $description = 'Cocokkan berkas CV di folder storage/app/public/rekrutmen/cv dengan kandidat pelamar';

    public function handle(): int
    {
        $this->info('Memulai pencocokan berkas CV pelamar...');

        $cvDirectory = storage_path('app/public/rekrutmen/cv');
        if (! File::isDirectory($cvDirectory)) {
            $this->error("Direktori CV tidak ditemukan di {$cvDirectory}");

            return self::FAILURE;
        }

        $files = File::files($cvDirectory);
        $this->info('Ditemukan '.count($files).' berkas CV di '.$cvDirectory);

        $filesById = [];
        $allFiles = [];
        foreach ($files as $file) {
            $filename = $file->getFilename();
            $allFiles[] = $filename;

            // Pola file: CV-{id}-...
            if (preg_match('/^CV-(\d+)-/i', $filename, $m)) {
                $id = (int) $m[1];
                $filesById[$id] = $filename;
            }
        }

        $this->info('Berkas dengan pola ID pelamar (CV-{id}-...): '.count($filesById).' berkas.');

        $applications = JobApplication::query()->get(['id', 'full_name', 'email', 'whatsapp_number', 'resume_path', 'job_posting_id']);
        $this->info('Total pelamar di database: '.$applications->count());

        $matchedCount = 0;
        $alreadySetCount = 0;
        $updatedCount = 0;
        $notFoundCount = 0;

        $dryRun = (bool) $this->option('dry-run');

        foreach ($applications as $app) {
            $matchedFile = null;

            // 1. Cocokkan langsung berdasarkan ID: CV-{$app->id}-...
            if (isset($filesById[$app->id])) {
                $matchedFile = $filesById[$app->id];
            }

            // 2. Jika tidak ada, cek apakah resume_path yang ada sekarang cocok dengan salah satu file di storage
            if (! $matchedFile && ! empty($app->resume_path)) {
                $base = basename($app->resume_path);
                if (in_array($base, $allFiles, true)) {
                    $matchedFile = $base;
                }
            }

            // 3. Jika belum cocok, coba cocokkan berdasarkan nama pelamar (slug nama)
            if (! $matchedFile && ! empty($app->full_name)) {
                $nameSlug = Str::slug($app->full_name);
                if ($nameSlug !== '') {
                    foreach ($allFiles as $fn) {
                        $fnLower = strtolower($fn);
                        if (str_contains($fnLower, $nameSlug)) {
                            $matchedFile = $fn;
                            break;
                        }
                    }
                }
            }

            if ($matchedFile) {
                $relativeStoragePath = 'rekrutmen/cv/'.$matchedFile;
                $matchedCount++;

                if ($app->resume_path === $relativeStoragePath) {
                    $alreadySetCount++;
                } else {
                    $updatedCount++;
                    $this->line("  [MATCH] #{$app->id} {$app->full_name} -> {$relativeStoragePath}");

                    if (! $dryRun) {
                        DB::table('rekrutmen_job_applications')
                            ->where('id', $app->id)
                            ->update([
                                'resume_path' => $relativeStoragePath,
                                'updated_at'  => now(),
                            ]);
                    }
                }
            } else {
                $notFoundCount++;
            }
        }

        $this->newLine();
        $this->info('=== HASIL PENCOCOKAN CV ===');
        $this->info(" - Berhasil dicocokkan : {$matchedCount} pelamar");
        $this->info(" - Sudah sesuai        : {$alreadySetCount} pelamar");
        $this->info(" - Diperbarui          : {$updatedCount} pelamar");
        $this->info(" - Belum ada berkas CV : {$notFoundCount} pelamar");

        if ($dryRun) {
            $this->warn('Mode DRY-RUN aktif: Database belum diubah.');
        } else {
            $this->info('Sinkronisasi berkas CV ke database selesai dan berhasil disimpan!');
        }

        return self::SUCCESS;
    }
}
