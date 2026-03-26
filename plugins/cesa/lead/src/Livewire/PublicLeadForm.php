<?php

namespace Cesa\Lead\Livewire;

use Cesa\Lead\Models\Lead;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\SimplePage;
use Filament\Schemas\Schema;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;
use Webkul\PluginManager\Package;

class PublicLeadForm extends SimplePage
{
    protected const WHATSAPP_VALIDATION_STATUS_SUCCESS = 'success';

    protected const WHATSAPP_VALIDATION_STATUS_NOT_REGISTERED = 'not_registered';

    protected const WHATSAPP_VALIDATION_STATUS_INVALID = 'invalid';

    protected const WHATSAPP_VALIDATION_STATUS_RATE_LIMITED = 'rate_limited';

    protected const WHATSAPP_VALIDATION_STATUS_FAILED = 'failed';

    use InteractsWithFormActions;
    use InteractsWithForms;

    protected static string $layout = 'lead::layouts.form';

    protected string $view = 'lead::livewire.public-lead-form';

    public ?array $data = [];

    public ?array $recentSubmission = null;

    public ?string $whatsappValidationStatus = null;

    protected bool $whatsappValidationEnabled = false;

    protected string $whatsappValidationProvider = 'fonnte';

    protected ?string $whatsappValidationEndpoint = null;

    protected ?string $whatsappValidationToken = null;

    protected string $whatsappCountryCode = '62';

    protected int $whatsappValidationTimeout = 5;

    protected int $whatsappValidationCacheTtl = 300;

    protected bool $whatsappValidationAllowManual = true;

    protected int $whatsappValidationRateLimitMaxAttempts = 10;

    protected int $whatsappValidationRateLimitDecaySeconds = 60;

    public function boot(): void
    {
        $whatsappValidation = config('lead.whatsapp_validation', []);

        $this->whatsappValidationProvider = (string) Arr::get($whatsappValidation, 'provider', 'fonnte');
        $this->whatsappValidationEndpoint = Arr::get($whatsappValidation, 'endpoint');
        $this->whatsappValidationToken = Arr::get($whatsappValidation, 'token');
        $this->whatsappCountryCode = (string) Arr::get($whatsappValidation, 'country_code', '62');
        $this->whatsappValidationTimeout = (int) Arr::get($whatsappValidation, 'timeout', 5);
        $this->whatsappValidationCacheTtl = (int) Arr::get($whatsappValidation, 'cache_ttl', 300);
        $this->whatsappValidationAllowManual = (bool) Arr::get($whatsappValidation, 'allow_manual_fallback', true);
        $this->whatsappValidationRateLimitMaxAttempts = (int) Arr::get($whatsappValidation, 'rate_limit.max_attempts', 10);
        $this->whatsappValidationRateLimitDecaySeconds = (int) Arr::get($whatsappValidation, 'rate_limit.decay', 60);

        $this->whatsappValidationEnabled = (bool) Arr::get($whatsappValidation, 'enabled', false)
            && filled($this->whatsappValidationEndpoint)
            && filled($this->whatsappValidationToken);
    }

    public function mount(): void
    {
        if (! Package::isPluginInstalled('lead')) {
            abort(404);
        }

        $this->form->fill($this->data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('lead::filament/resources/lead.fields.name'))
                    ->required()
                    ->maxLength(255)
                    ->placeholder(__('lead::filament/resources/lead.form.placeholders.name')),
                TextInput::make('phone')
                    ->label(__('lead::filament/resources/lead.fields.phone'))
                    ->tel()
                    ->required()
                    ->maxLength(15)
                    ->placeholder(__('lead::filament/resources/lead.form.placeholders.phone'))
                    ->live(onBlur: true)
                    ->helperText(fn (): ?string => $this->getWhatsAppValidationHelperText())
                    ->afterStateUpdated(function ($state, callable $set): void {
                        $set('phone', Lead::normalizePhone((string) $state));
                        $this->resetWhatsAppValidationFeedback();
                    })
                    ->suffixAction(
                        Action::make('check_whatsapp')
                            ->label(__('lead::views/public-lead-form.whatsapp_validation.action'))
                            ->icon('heroicon-m-magnifying-glass')
                            ->tooltip(__('lead::views/public-lead-form.whatsapp_validation.action'))
                            ->action(fn (): mixed => $this->checkWhatsAppValidation())
                            ->visible(fn (): bool => $this->whatsappValidationEnabled)
                    )
                    ->unique(Lead::class, 'phone')
                    ->rule(function () {
                        return function (string $attribute, $value, $fail): void {
                            if (! preg_match('/^62[0-9]{8,}$/', (string) $value)) {
                                $fail(__('lead::filament/resources/lead.validation.phone_format'));
                            }
                        };
                    }),
                Textarea::make('address')
                    ->label(__('lead::filament/resources/lead.fields.address'))
                    ->required()
                    ->columnSpanFull()
                    ->placeholder(__('lead::filament/resources/lead.form.placeholders.address')),
                TextInput::make('sales_person')
                    ->label(__('lead::filament/resources/lead.fields.sales_person'))
                    ->required()
                    ->maxLength(255)
                    ->placeholder(__('lead::filament/resources/lead.form.placeholders.sales_person')),
                Select::make('store_team_position')
                    ->label(__('lead::filament/resources/lead.fields.store_team_position'))
                    ->options([
                        'Kepala Toko' => __('lead::filament/resources/lead.options.store_team_position.kepala_toko'),
                        'Promotor'    => __('lead::filament/resources/lead.options.store_team_position.promotor'),
                        'Kasir'       => __('lead::filament/resources/lead.options.store_team_position.kasir'),
                        'Frontliner'  => __('lead::filament/resources/lead.options.store_team_position.frontliner'),
                    ])
                    ->required()
                    ->placeholder(__('lead::filament/resources/lead.form.placeholders.choose')),
                Select::make('store_branch')
                    ->label(__('lead::filament/resources/lead.fields.store_branch'))
                    ->searchable()
                    ->required()
                    ->options(Lead::storeBranchOptions())
                    ->placeholder(__('lead::filament/resources/lead.form.placeholders.store_branch')),
                Select::make('phone_transaction_range')
                    ->label(__('lead::filament/resources/lead.fields.phone_transaction_range'))
                    ->placeholder(__('lead::filament/resources/lead.form.placeholders.phone_transaction_range'))
                    ->searchable()
                    ->options([
                        'Harga di bawah 2 juta' => __('lead::filament/resources/lead.options.phone_transaction_range.below_2m'),
                        'Harga 2 - 3 juta'      => __('lead::filament/resources/lead.options.phone_transaction_range.2m_3m'),
                        'Harga 3 - 4 juta'      => __('lead::filament/resources/lead.options.phone_transaction_range.3m_4m'),
                        'Harga 4 - 7 juta'      => __('lead::filament/resources/lead.options.phone_transaction_range.4m_7m'),
                        'Harga di atas 7 juta'  => __('lead::filament/resources/lead.options.phone_transaction_range.above_7m'),
                    ])
                    ->nullable(),
            ])
            ->statePath('data');
    }

    public function checkWhatsAppValidation(): void
    {
        if (! $this->whatsappValidationEnabled) {
            return;
        }

        $this->resetWhatsAppValidationFeedback();

        $phone = data_get($this->data, 'phone');
        $phone = Lead::normalizePhone((string) $phone);

        if (blank($phone)) {
            $this->addError('data.phone', __('lead::filament/resources/lead.validation.phone_required'));

            return;
        }

        $result = $this->requestWhatsAppValidation($phone);
        $this->whatsappValidationStatus = $result['status'];

        if (
            ! $this->whatsappValidationAllowManual
            && $result['status'] !== self::WHATSAPP_VALIDATION_STATUS_SUCCESS
        ) {
            $this->addError('data.phone', $this->getWhatsAppValidationErrorMessage($result['status']));
        }
    }

    public function submit(): void
    {
        $state = $this->form->getState();

        try {
            $lead = Lead::create([
                ...$state,
                'created_by' => null,
            ]);

            $this->recentSubmission = [
                'id'    => $lead->getKey(),
                'name'  => $lead->name,
                'phone' => $lead->phone,
            ];

            Notification::make()
                ->title(__('lead::views/public-lead-form.notifications.submitted.title'))
                ->body(__('lead::views/public-lead-form.notifications.submitted.body'))
                ->success()
                ->send();
        } catch (QueryException $e) {
            if (str_contains(strtolower($e->getMessage()), 'leads_phone_unique')) {
                $this->addError('data.phone', __('lead::filament/resources/lead.validation.phone_unique'));

                return;
            }

            Log::error('Public lead submission failed (query exception).', [
                'exception' => $e,
            ]);

            $this->addError('data', __('lead::views/public-lead-form.messages.generic'));
        } catch (Throwable $e) {
            Log::error('Public lead submission failed.', [
                'exception' => $e,
            ]);

            $this->addError('data', __('lead::views/public-lead-form.messages.generic'));
        }
    }

    protected function getWhatsAppValidationHelperText(): ?string
    {
        if (! $this->whatsappValidationEnabled) {
            return null;
        }

        if ($this->whatsappValidationStatus === self::WHATSAPP_VALIDATION_STATUS_SUCCESS) {
            return __('lead::views/public-lead-form.whatsapp_validation.success');
        }

        if ($this->whatsappValidationStatus === self::WHATSAPP_VALIDATION_STATUS_NOT_REGISTERED) {
            return __('lead::views/public-lead-form.whatsapp_validation.not_registered');
        }

        if ($this->whatsappValidationStatus === self::WHATSAPP_VALIDATION_STATUS_INVALID) {
            return __('lead::views/public-lead-form.whatsapp_validation.invalid');
        }

        if ($this->whatsappValidationStatus === self::WHATSAPP_VALIDATION_STATUS_RATE_LIMITED) {
            return __('lead::views/public-lead-form.whatsapp_validation.rate_limited');
        }

        if ($this->whatsappValidationStatus === self::WHATSAPP_VALIDATION_STATUS_FAILED) {
            return __('lead::views/public-lead-form.whatsapp_validation.failed');
        }

        return __('lead::views/public-lead-form.whatsapp_validation.hint');
    }

    protected function resetWhatsAppValidationFeedback(): void
    {
        if (! $this->whatsappValidationEnabled) {
            return;
        }

        $this->whatsappValidationStatus = null;
        $this->resetErrorBag(['data.phone']);
    }

    /**
     * @return array{status: string}
     */
    protected function requestWhatsAppValidation(string $phone): array
    {
        $phone = Lead::normalizePhone($phone);

        if ($phone === '') {
            return [
                'status' => self::WHATSAPP_VALIDATION_STATUS_INVALID,
            ];
        }

        $cacheKey = $this->whatsappValidationCacheKey($phone);
        if ($this->whatsappValidationCacheTtl > 0) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        if ($this->isWhatsAppValidationRateLimited($phone)) {
            return [
                'status' => self::WHATSAPP_VALIDATION_STATUS_RATE_LIMITED,
            ];
        }

        $payload = $this->performWhatsAppValidationRequest($phone);
        $result = [
            'status' => self::WHATSAPP_VALIDATION_STATUS_FAILED,
        ];

        if ($payload === null) {
            return $result;
        }

        if (! Arr::get($payload, 'status')) {
            $result['status'] = self::WHATSAPP_VALIDATION_STATUS_FAILED;
        } else {
            $registered = Arr::get($payload, 'registered', []);
            $notRegistered = Arr::get($payload, 'not_registered', []);
            $invalid = Arr::get($payload, 'invalid', []);

            if (is_array($registered) && count($registered) > 0) {
                $result['status'] = self::WHATSAPP_VALIDATION_STATUS_SUCCESS;
            } elseif (is_array($notRegistered) && count($notRegistered) > 0) {
                $result['status'] = self::WHATSAPP_VALIDATION_STATUS_NOT_REGISTERED;
            } elseif (is_array($invalid) && count($invalid) > 0) {
                $result['status'] = self::WHATSAPP_VALIDATION_STATUS_INVALID;
            } else {
                $result['status'] = self::WHATSAPP_VALIDATION_STATUS_FAILED;
            }
        }

        if (
            $this->whatsappValidationCacheTtl > 0
            && in_array($result['status'], [self::WHATSAPP_VALIDATION_STATUS_SUCCESS, self::WHATSAPP_VALIDATION_STATUS_NOT_REGISTERED, self::WHATSAPP_VALIDATION_STATUS_INVALID], true)
        ) {
            Cache::put($cacheKey, $result, now()->addSeconds($this->whatsappValidationCacheTtl));
        }

        return $result;
    }

    protected function performWhatsAppValidationRequest(string $phone): ?array
    {
        if ($this->whatsappValidationProvider !== 'fonnte') {
            return null;
        }

        try {
            $response = Http::asForm()
                ->timeout($this->whatsappValidationTimeout)
                ->withHeaders([
                    'Authorization' => (string) $this->whatsappValidationToken,
                ])
                ->post((string) $this->whatsappValidationEndpoint, [
                    'target'      => $phone,
                    'countryCode' => $this->whatsappCountryCode,
                ]);

            if (! $response->successful()) {
                Log::warning('WhatsApp validation request failed.', [
                    'status' => $response->status(),
                ]);

                return null;
            }

            $payload = $response->json();

            return is_array($payload) ? $payload : null;
        } catch (Throwable $exception) {
            Log::warning('WhatsApp validation request exception.', [
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    protected function whatsappValidationCacheKey(string $phone): string
    {
        return sprintf('lead:whatsapp-validation:%s', $phone);
    }

    protected function isWhatsAppValidationRateLimited(string $phone): bool
    {
        if ($this->whatsappValidationRateLimitMaxAttempts <= 0 || $this->whatsappValidationRateLimitDecaySeconds <= 0) {
            return false;
        }

        $key = $this->whatsappValidationRateLimitKey($phone);

        if (! RateLimiter::tooManyAttempts($key, $this->whatsappValidationRateLimitMaxAttempts)) {
            RateLimiter::hit($key, $this->whatsappValidationRateLimitDecaySeconds);

            return false;
        }

        return true;
    }

    protected function whatsappValidationRateLimitKey(string $phone): string
    {
        $ipAddress = request()?->ip() ?: 'guest';

        return sprintf('lead:whatsapp-validation:%s:%s', $ipAddress, $phone);
    }

    protected function getWhatsAppValidationErrorMessage(string $status): string
    {
        return match ($status) {
            self::WHATSAPP_VALIDATION_STATUS_NOT_REGISTERED => __('lead::views/public-lead-form.whatsapp_validation.not_registered'),
            self::WHATSAPP_VALIDATION_STATUS_INVALID        => __('lead::views/public-lead-form.whatsapp_validation.invalid'),
            self::WHATSAPP_VALIDATION_STATUS_RATE_LIMITED   => __('lead::views/public-lead-form.whatsapp_validation.rate_limited'),
            default                                         => __('lead::views/public-lead-form.whatsapp_validation.failed'),
        };
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSubmitAction(),
        ];
    }

    protected function getSubmitAction(): Action
    {
        return Action::make('submit')
            ->label(__('lead::views/public-lead-form.actions.submit'))
            ->extraAttributes([
                'class' => '!bg-primary-700 !text-white shadow-sm hover:!bg-primary-800 hover:!text-white focus-visible:!ring-primary-300',
            ], merge: true)
            ->submit('submit');
    }

    protected function normalizePhone(string $value): string
    {
        return Lead::normalizePhone($value);
    }
}
