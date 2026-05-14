<?php

namespace Cesa\Padelnis\Models;

use Cesa\Padelnis\Database\Factories\ReservationFactory;
use Cesa\Padelnis\Services\ReservationReferenceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class Reservation extends Model
{
    use HasFactory, SoftDeletes;

    protected const int REFERENCE_GENERATION_MAX_ATTEMPTS = 5;

    protected $table = 'padelnis_reservations';

    protected $fillable = [
        'id_reff',
        'customer_name',
        'reservation_date',
        'court',
        'reservation_time',
        'transfer_amount',
    ];

    protected static function booted(): void
    {
        static::saving(function (Reservation $reservation): void {
            $reservation->syncActiveSlotKey();
            $reservation->assertActiveSlotIsAvailable();
        });

        static::creating(function (Reservation $reservation): void {
            if (blank($reservation->id_reff)) {
                $reservation->id_reff = app(ReservationReferenceService::class)->generate();
            }
        });

        static::deleted(function (Reservation $reservation): void {
            if ($reservation->isForceDeleting()) {
                return;
            }

            static::query()
                ->withoutGlobalScopes()
                ->whereKey($reservation->getKey())
                ->update(['active_slot_key' => null]);

            $reservation->active_slot_key = null;
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reservation_date' => 'date',
            'transfer_amount'  => 'decimal:2',
            'created_at'       => 'datetime',
            'updated_at'       => 'datetime',
            'deleted_at'       => 'datetime',
        ];
    }

    protected function performInsert(Builder $query): bool
    {
        for ($attempt = 1; $attempt <= self::REFERENCE_GENERATION_MAX_ATTEMPTS; $attempt++) {
            try {
                return $this->getConnection()->transaction(fn (): bool => parent::performInsert($query));
            } catch (QueryException $exception) {
                if (static::isDuplicateActiveSlotException($exception)) {
                    throw static::duplicateActiveSlotValidationException();
                }

                if (! $this->isDuplicateReferenceException($exception) || $attempt === self::REFERENCE_GENERATION_MAX_ATTEMPTS) {
                    throw $exception;
                }

                $this->id_reff = null;
                $this->exists = false;
                $this->wasRecentlyCreated = false;
            }
        }

        return false;
    }

    protected function performUpdate(Builder $query): bool
    {
        try {
            return parent::performUpdate($query);
        } catch (QueryException $exception) {
            if (static::isDuplicateActiveSlotException($exception)) {
                throw static::duplicateActiveSlotValidationException();
            }

            throw $exception;
        }
    }

    protected function isDuplicateReferenceException(QueryException $exception): bool
    {
        if (! static::isUniqueConstraintException($exception)) {
            return false;
        }

        $constraintMessage = static::constraintMessage($exception);

        return str_contains($constraintMessage, 'id_reff')
            || str_contains($constraintMessage, 'padelnis_reservations_id_reff_unique');
    }

    /**
     * @return array<string, string>
     */
    public static function courtOptions(): array
    {
        $courts = array_values(config('padelnis.courts', []));

        return array_combine($courts, $courts) ?: [];
    }

    /**
     * @return array<string, string>
     */
    public static function slotOptions(): array
    {
        $slots = array_values(config('padelnis.slots', []));

        return array_combine($slots, $slots) ?: [];
    }

    public function setCustomerNameAttribute(mixed $value): void
    {
        $this->attributes['customer_name'] = $this->normalizeName($value);
    }

    public function setCourtAttribute(mixed $value): void
    {
        $this->attributes['court'] = $this->normalizeToConfiguredOption($value, static::courtOptions());
    }

    public function setTransferAmountAttribute(mixed $value): void
    {
        $this->attributes['transfer_amount'] = static::normalizeTransferAmount($value);
    }

    public function setReservationTimeAttribute(mixed $value): void
    {
        $this->attributes['reservation_time'] = static::normalizeReservationTime($value);
    }

    public function getReservationTimeAttribute(mixed $value): string
    {
        return static::normalizeReservationTime($value);
    }

    public static function normalizeReservationTime(mixed $value): string
    {
        $normalized = self::squishValue((string) $value);

        foreach (static::slotOptions() as $slot) {
            if (mb_strtolower($slot, 'UTF-8') === mb_strtolower($normalized, 'UTF-8')) {
                return $slot;
            }
        }

        if (! preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?(?:\s*-\s*(\d{1,2}):(\d{2})(?::\d{2})?)?$/', $normalized, $matches)) {
            return $normalized;
        }

        $startTime = sprintf('%02d:%s', (int) $matches[1], $matches[2]);
        $endTime = isset($matches[3], $matches[4])
            ? sprintf('%02d:%s', (int) $matches[3], $matches[4])
            : null;

        foreach (static::slotOptions() as $slot) {
            if ($endTime !== null && $slot === "{$startTime} - {$endTime}") {
                return $slot;
            }

            if ($endTime === null && str_starts_with($slot, "{$startTime} - ")) {
                return $slot;
            }
        }

        return $endTime === null ? $startTime : "{$startTime} - {$endTime}";
    }

    public static function formatTransferAmountForForm(mixed $value): ?string
    {
        $normalized = static::normalizeTransferAmount($value);

        if ($normalized === null || $normalized === '') {
            return null;
        }

        if (! is_numeric($normalized)) {
            return (string) $value;
        }

        $amount = (float) $normalized;
        $decimalPlaces = floor($amount) === $amount ? 0 : 2;

        return number_format($amount, $decimalPlaces, ',', '.');
    }

    public static function normalizeTransferAmount(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return number_format((float) $value, 2, '.', '');
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $normalized = static::normalizeLocalizedNumber($value);

        return is_numeric($normalized)
            ? number_format((float) $normalized, 2, '.', '')
            : $normalized;
    }

    public static function makeActiveSlotKey(mixed $court, mixed $reservationDate, mixed $reservationTime): ?string
    {
        $normalizedCourt = static::normalizeToConfiguredOption($court, static::courtOptions());
        $normalizedDate = static::normalizeReservationDate($reservationDate);
        $normalizedTime = static::normalizeReservationTime($reservationTime);

        if ($normalizedCourt === '' || $normalizedDate === null || $normalizedTime === '') {
            return null;
        }

        return "{$normalizedCourt}|{$normalizedDate}|{$normalizedTime}";
    }

    public static function activeSlotExists(mixed $court, mixed $reservationDate, mixed $reservationTime, mixed $ignoredKey = null): bool
    {
        $activeSlotKey = static::makeActiveSlotKey($court, $reservationDate, $reservationTime);

        if ($activeSlotKey === null) {
            return false;
        }

        $query = static::query()
            ->withoutGlobalScopes()
            ->where('active_slot_key', $activeSlotKey);

        if ($ignoredKey !== null) {
            $query->whereKeyNot($ignoredKey);
        }

        return $query->exists();
    }

    public static function isDuplicateActiveSlotException(QueryException $exception): bool
    {
        if (! static::isUniqueConstraintException($exception)) {
            return false;
        }

        $constraintMessage = static::constraintMessage($exception);

        return str_contains($constraintMessage, 'active_slot_key')
            || str_contains($constraintMessage, 'padelnis_reservations_active_slot_key_unique');
    }

    protected static function isUniqueConstraintException(QueryException $exception): bool
    {
        return in_array((string) $exception->getCode(), ['23000', '23505'], true);
    }

    protected static function constraintMessage(QueryException $exception): string
    {
        return strtolower((string) ($exception->errorInfo[2] ?? $exception->getPrevious()?->getMessage() ?? $exception->getMessage()));
    }

    protected static function duplicateActiveSlotValidationException(): ValidationException
    {
        return ValidationException::withMessages([
            'reservation_time' => __('padelnis::filament/resources/reservation.validation.active_slot_unique'),
        ]);
    }

    protected function syncActiveSlotKey(): void
    {
        $this->active_slot_key = $this->deleted_at
            ? null
            : static::makeActiveSlotKey($this->court, $this->reservation_date, $this->reservation_time);
    }

    protected function assertActiveSlotIsAvailable(): void
    {
        if (! is_string($this->active_slot_key) || $this->active_slot_key === '') {
            return;
        }

        $query = static::query()
            ->withoutGlobalScopes()
            ->where('active_slot_key', $this->active_slot_key);

        if ($this->exists && $this->getKey() !== null) {
            $query->whereKeyNot($this->getKey());
        }

        if ($query->exists()) {
            throw static::duplicateActiveSlotValidationException();
        }
    }

    protected static function normalizeReservationDate(mixed $value): ?string
    {
        if ($value instanceof Carbon) {
            return $value->format('Y-m-d');
        }

        $normalized = self::squishValue((string) $value);

        if ($normalized === '') {
            return null;
        }

        try {
            return Carbon::parse($normalized)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    protected static function normalizeLocalizedNumber(string $value): string
    {
        $value = preg_replace('/[^\d,.\-]/', '', $value) ?? '';

        if ($value === '') {
            return '';
        }

        $isNegative = str_starts_with($value, '-');
        $value = str_replace('-', '', $value);

        $lastCommaPosition = strrpos($value, ',');
        $lastDotPosition = strrpos($value, '.');
        $decimalSeparator = null;

        if ($lastCommaPosition !== false && $lastDotPosition !== false) {
            $decimalSeparator = $lastCommaPosition > $lastDotPosition ? ',' : '.';
        } elseif ($lastCommaPosition !== false) {
            $fractionLength = strlen($value) - $lastCommaPosition - 1;
            $decimalSeparator = $fractionLength > 0 && $fractionLength <= 2 ? ',' : null;
        } elseif ($lastDotPosition !== false) {
            $fractionLength = strlen($value) - $lastDotPosition - 1;
            $decimalSeparator = $fractionLength > 0 && $fractionLength <= 2 ? '.' : null;
        }

        if ($decimalSeparator === null) {
            $normalized = preg_replace('/\D/', '', $value) ?? '';

            return $isNegative && $normalized !== '' ? "-{$normalized}" : $normalized;
        }

        $separatorPosition = strrpos($value, $decimalSeparator);
        $integer = preg_replace('/\D/', '', substr($value, 0, $separatorPosition)) ?: '0';
        $fraction = preg_replace('/\D/', '', substr($value, $separatorPosition + 1)) ?? '';
        $normalized = "{$integer}.{$fraction}";

        return $isNegative ? "-{$normalized}" : $normalized;
    }

    protected function normalizeName(mixed $value): string
    {
        $normalized = self::squishValue((string) $value);

        return mb_convert_case($normalized, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * @param  array<string, string>  $options
     */
    protected static function normalizeToConfiguredOption(mixed $value, array $options): string
    {
        $normalized = self::squishValue((string) $value);

        foreach ($options as $option) {
            if (mb_strtolower($option, 'UTF-8') === mb_strtolower($normalized, 'UTF-8')) {
                return $option;
            }
        }

        return $normalized;
    }

    protected static function squishValue(string $value): string
    {
        $squished = preg_replace('/\s+/', ' ', trim($value));

        return is_string($squished) ? $squished : trim($value);
    }

    protected static function newFactory(): ReservationFactory
    {
        return ReservationFactory::new();
    }
}
