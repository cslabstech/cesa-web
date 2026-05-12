<?php

namespace Cesa\FormTransfer\Models;

use Cesa\FormTransfer\Database\Factories\TransferRequestRealizationFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Security\Models\User;

class TransferRequestRealization extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'form_transfer_request_realizations';

    protected $fillable = [
        'transfer_request_id',
        'user_id',
        'amount',
        'realized_at',
        'proof_path',
        'notes',
    ];

    protected ?string $originalProofPath = null;

    protected static function booted(): void
    {
        static::saving(function (TransferRequestRealization $realization): void {
            $realization->originalProofPath = $realization->exists
                ? $realization->getRawOriginal('proof_path')
                : null;
        });

        static::saved(function (TransferRequestRealization $realization): void {
            $realization->syncProofStorageName();
            /*
             * Penghapusan fisik otomatis dimatikan untuk mencegah kasus tidak terduga
             * di mana value menjadi null dari form dan menghapus file secara permanen
             * (demi keamanan dan retensi data).
             */
            // $realization->deleteRemovedProofFile();
            $realization->transferRequest?->refreshRealizationSummary();
        });

        static::deleted(function (TransferRequestRealization $realization): void {
            $realization->transferRequest?->refreshRealizationSummary();
        });

        static::forceDeleted(function (TransferRequestRealization $realization): void {
            /*
             * Penghapusan fisik otomatis dimatikan untuk mencegah kasus tidak terduga
             * di mana value menjadi null dari form dan menghapus file secara permanen
             * (demi keamanan dan retensi data).
             */
            // $realization->deleteProofFile($realization->getRawOriginal('proof_path') ?? $realization->proof_path);
        });
    }

    protected function casts(): array
    {
        return [
            'amount'      => 'decimal:2',
            'realized_at' => 'date',
            'notes'       => 'string',
        ];
    }

    public function transferRequest(): BelongsTo
    {
        return $this->belongsTo(TransferRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    protected function syncProofStorageName(): void
    {
        if (blank($this->proof_path) || ! $this->transferRequest?->uid) {
            return;
        }

        $renamedPath = $this->renameProofPath((string) $this->proof_path);

        if ($renamedPath === $this->proof_path) {
            return;
        }

        $this->forceFill(['proof_path' => $renamedPath]);
        $this->saveQuietly();
    }

    protected function renameProofPath(string $path): string
    {
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $relativePath = ltrim($path, '/');

        if ($relativePath === '') {
            return $path;
        }

        $uid = (string) $this->transferRequest?->uid;
        $baseName = pathinfo($relativePath, PATHINFO_BASENAME);

        if (str_starts_with($baseName, "{$uid}-")) {
            return $relativePath;
        }

        $disk = $this->resolveAttachmentDisk($relativePath);

        if (! $disk) {
            return $path;
        }

        try {
            $extension = Str::lower(pathinfo($relativePath, PATHINFO_EXTENSION));
            $directory = trim(pathinfo($relativePath, PATHINFO_DIRNAME), './');
            $directory = $directory !== '' ? $directory : 'form-transfer/realizations';
            $pathPrefix = trim($directory, '/').'/';

            do {
                $fileName = "{$uid}-R{$this->getKey()}-".Str::lower(Str::random(6));

                if ($extension !== '') {
                    $fileName .= '.'.$extension;
                }

                $targetPath = $pathPrefix.$fileName;
            } while (Storage::disk($disk)->exists($targetPath));

            Storage::disk($disk)->move($relativePath, $targetPath);

            return $targetPath;
        } catch (\Throwable $e) {
            logger()->warning('Failed to rename transfer realization proof.', [
                'transfer_request_realization_id' => $this->getKey(),
                'original_path'                   => $path,
                'disk'                            => $disk,
                'error'                           => $e->getMessage(),
            ]);

            return $path;
        }
    }

    protected function deleteRemovedProofFile(): void
    {
        if (! $this->originalProofPath || $this->originalProofPath === $this->proof_path) {
            $this->originalProofPath = null;

            return;
        }

        $this->deleteProofFile($this->originalProofPath);
        $this->originalProofPath = null;
    }

    protected function deleteProofFile(mixed $path): void
    {
        if (! is_string($path) || $path === '' || filter_var($path, FILTER_VALIDATE_URL)) {
            return;
        }

        $relativePath = ltrim($path, '/');
        $disk = $this->resolveAttachmentDisk($relativePath);

        if (! $disk) {
            return;
        }

        try {
            Storage::disk($disk)->delete($relativePath);
        } catch (\Throwable $e) {
            logger()->warning('Failed to delete transfer realization proof.', [
                'transfer_request_realization_id' => $this->getKey(),
                'path'                            => $relativePath,
                'disk'                            => $disk,
                'error'                           => $e->getMessage(),
            ]);
        }
    }

    protected function resolveAttachmentDisk(string $relativePath): ?string
    {
        $candidateDisks = array_values(array_unique(array_filter([
            config('filament.default_filesystem_disk'),
            config('filesystems.default'),
            'local',
            'public',
            'ftp',
        ], fn (mixed $disk): bool => is_string($disk) && $disk !== '')));

        foreach ($candidateDisks as $disk) {
            if (! config()->has("filesystems.disks.{$disk}")) {
                continue;
            }

            try {
                if (Storage::disk($disk)->exists($relativePath)) {
                    return $disk;
                }
            } catch (\Throwable $e) {
                logger()->debug('Failed to check realization proof existence on disk.', [
                    'disk'  => $disk,
                    'path'  => $relativePath,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return null;
    }

    protected static function newFactory(): Factory
    {
        return TransferRequestRealizationFactory::new();
    }
}
