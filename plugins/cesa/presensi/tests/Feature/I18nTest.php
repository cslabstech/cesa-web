<?php

namespace Cesa\Presensi\Tests\Feature;

use Cesa\Presensi\Filament\Clusters\Configurations;
use Cesa\Presensi\Filament\Resources\AttendanceResource;
use Cesa\Presensi\Filament\Resources\LeaveResource;
use Cesa\Presensi\Filament\Resources\OfficeResource;
use Cesa\Presensi\Filament\Resources\OvertimeResource;
use Cesa\Presensi\Filament\Resources\ScheduleResource;
use Cesa\Presensi\Filament\Resources\ShiftResource;
use Cesa\Presensi\Tests\PresensiTestCase;

class I18nTest extends PresensiTestCase
{
    public function test_presensi_translation_keys_are_consistent_between_en_and_id(): void
    {
        $english = $this->collectTranslationKeys('en');
        $indonesian = $this->collectTranslationKeys('id');

        $this->assertSame(array_keys($english), array_keys($indonesian));

        foreach (array_keys($english) as $file) {
            $this->assertSame($english[$file], $indonesian[$file], "Translation keys mismatch for {$file}");
        }
    }

    public function test_admin_labels_are_localized_for_en_and_id(): void
    {
        $expectations = [
            'en' => [
                'config'     => 'Settings',
                'attendance' => 'Attendances',
                'leave'      => 'Leaves',
                'office'     => 'Offices',
                'overtime'   => 'Overtimes',
                'schedule'   => 'Schedules',
                'shift'      => 'Shifts',
            ],
            'id' => [
                'config'     => 'Pengaturan',
                'attendance' => 'Presensi',
                'leave'      => 'Cuti dan Izin',
                'office'     => 'Kantor',
                'overtime'   => 'Lembur',
                'schedule'   => 'Jadwal',
                'shift'      => 'Shift',
            ],
        ];

        foreach ($expectations as $locale => $labels) {
            app()->setLocale($locale);

            $this->assertSame($labels['config'], Configurations::getNavigationLabel());
            $this->assertSame($labels['attendance'], AttendanceResource::getNavigationLabel());
            $this->assertSame($labels['leave'], LeaveResource::getNavigationLabel());
            $this->assertSame($labels['office'], OfficeResource::getNavigationLabel());
            $this->assertSame($labels['overtime'], OvertimeResource::getNavigationLabel());
            $this->assertSame($labels['schedule'], ScheduleResource::getNavigationLabel());
            $this->assertSame($labels['shift'], ShiftResource::getNavigationLabel());
        }
    }

    /**
     * @param  array<string, mixed>  $items
     * @return array<int, string>
     */
    private function flattenKeys(array $items, string $prefix = ''): array
    {
        $keys = [];

        foreach ($items as $key => $value) {
            $path = $prefix === '' ? $key : "{$prefix}.{$key}";

            if (is_array($value)) {
                $keys = array_merge($keys, $this->flattenKeys($value, $path));
            } else {
                $keys[] = $path;
            }
        }

        return $keys;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function collectTranslationKeys(string $locale): array
    {
        $basePath = base_path("plugins/cesa/presensi/resources/lang/{$locale}");
        $files = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file instanceof \SplFileInfo || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($basePath.DIRECTORY_SEPARATOR, '', $file->getPathname());
            $keys = $this->flattenKeys(require $file->getPathname());

            sort($keys);

            $files[$relativePath] = $keys;
        }

        ksort($files);

        return $files;
    }
}
