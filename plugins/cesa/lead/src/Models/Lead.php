<?php

namespace Cesa\Lead\Models;

use Cesa\Lead\Database\Factories\LeadFactory;
use Cesa\Lead\Enums\PhoneTransactionRange;
use Cesa\Lead\Enums\StoreTeamPosition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Webkul\Security\Models\User;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'address',
        'sales_person',
        'store_team_position',
        'store_branch',
        'phone_transaction_range',
        'public_response_id',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'store_team_position'     => StoreTeamPosition::class,
            'phone_transaction_range' => PhoneTransactionRange::class,
            'public_response_id'      => 'string',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $lead): void {
            if (blank($lead->public_response_id)) {
                $lead->public_response_id = (string) Str::ulid();
            }
        });
    }

    /**
     * Normalize an Indonesian phone number to 628xxxxxxxxxx format.
     */
    public static function normalizePhone(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $phone = preg_replace('/\D+/', '', $value);

        if ($phone === '') {
            return '';
        }

        if (str_starts_with($phone, '0062')) {
            $phone = substr($phone, 4);
            if (str_starts_with($phone, '0')) {
                $phone = '62'.substr($phone, 1);
            } else {
                $phone = '62'.$phone;
            }
        } elseif (str_starts_with($phone, '620')) {
            $phone = '62'.substr($phone, 3);
        } elseif (str_starts_with($phone, '00')) {
            $phone = '62'.substr($phone, 2);
        } elseif (str_starts_with($phone, '0')) {
            $phone = '62'.substr($phone, 1);
        } elseif (str_starts_with($phone, '62')) {
            // already correct
        } elseif (str_starts_with($phone, '8')) {
            $phone = '62'.$phone;
        } else {
            $phone = '62'.$phone;
        }

        return $phone;
    }

    /**
     * Get store branch options from config.
     *
     * @return array<string, string>
     */
    public static function storeBranchOptions(): array
    {
        $branches = config('lead.store_branches', []);

        return array_combine($branches, $branches);
    }

    /**
     * Set the phone attribute in a consistent 628xxxxxxxxxx format.
     */
    public function setPhoneAttribute($value): void
    {
        $this->attributes['phone'] = $value === null ? null : (static::normalizePhone($value) ?: null);
    }

    /**
     * Set the name attribute to uppercase (capslock).
     */
    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = mb_strtoupper($value);
    }

    /**
     * Get the user who created this lead.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the public-facing URL for the lead progress/confirmation page.
     */
    public function getPublicProgressUrl(): string
    {
        if (blank($this->public_response_id)) {
            return route('lead.public.form');
        }

        return route('lead.public.show', $this->public_response_id);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): LeadFactory
    {
        return LeadFactory::new();
    }
}
