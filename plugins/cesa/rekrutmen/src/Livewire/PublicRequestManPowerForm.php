<?php

namespace Cesa\Rekrutmen\Livewire;

use Cesa\Rekrutmen\Enums\StatusKebutuhan;
use Cesa\Rekrutmen\Models\Division;
use Cesa\Rekrutmen\Models\RequestManPower;
use Cesa\Rekrutmen\Services\RecaptchaVerificationService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;
use Webkul\Support\Models\Company;

class PublicRequestManPowerForm extends SimplePage
{
    use InteractsWithFormActions;
    use InteractsWithForms;

    protected static string $layout = 'rekrutmen::layouts.form';

    protected string $view = 'rekrutmen::livewire.public-man-power-request-form';

    public ?array $data = [];

    public ?array $recentSubmission = null;

    protected bool $recaptchaEnabled = false;

    protected ?string $recaptchaSiteKey = null;

    protected ?string $recaptchaSecretKey = null;

    protected ?string $recaptchaAction = null;

    protected float $recaptchaScoreThreshold = 0.0;

    protected int $recaptchaTimeout = 5;

    protected function getValidationAttributes(): array
    {
        return [
            'data.nama_pengaju'               => __('rekrutmen::livewire/public-request-man-power-form.fields.nama_pengaju'),
            'data.posisi_pengaju'             => __('rekrutmen::livewire/public-request-man-power-form.fields.posisi_pengaju'),
            'data.email_address'              => __('rekrutmen::livewire/public-request-man-power-form.fields.email_address'),
            'data.tanggal_pengajuan'          => __('rekrutmen::livewire/public-request-man-power-form.fields.tanggal_pengajuan'),
            'data.company_id'                 => __('rekrutmen::livewire/public-request-man-power-form.fields.company_id'),
            'data.division_id'                => __('rekrutmen::livewire/public-request-man-power-form.fields.division_id'),
            'data.posisi_dibutuhkan'          => __('rekrutmen::livewire/public-request-man-power-form.fields.posisi_dibutuhkan'),
            'data.lokasi_penempatan'          => __('rekrutmen::livewire/public-request-man-power-form.fields.lokasi_penempatan'),
            'data.level_pekerjaan'            => __('rekrutmen::livewire/public-request-man-power-form.fields.level_pekerjaan'),
            'data.jumlah_karyawan_dibutuhkan' => __('rekrutmen::livewire/public-request-man-power-form.fields.jumlah_karyawan_dibutuhkan'),
            'data.estimasi_tanggal_join'      => __('rekrutmen::livewire/public-request-man-power-form.fields.estimasi_tanggal_join'),
            'data.requirements_kualifikasi'   => __('rekrutmen::livewire/public-request-man-power-form.fields.requirements_kualifikasi'),
            'data.job_description'            => __('rekrutmen::livewire/public-request-man-power-form.fields.job_description'),
            'data.keterangan'                 => __('rekrutmen::livewire/public-request-man-power-form.fields.keterangan'),
            'data.status_kebutuhan'           => __('rekrutmen::livewire/public-request-man-power-form.fields.status_kebutuhan'),
            'data.nama_karyawan_replacement'  => __('rekrutmen::livewire/public-request-man-power-form.fields.nama_karyawan_replacement'),
        ];
    }

    public function mount(): void
    {
        $recaptcha = config('rekrutmen.security.recaptcha', []);

        $this->recaptchaSiteKey = Arr::get($recaptcha, 'site_key');
        $this->recaptchaSecretKey = Arr::get($recaptcha, 'secret_key');
        $this->recaptchaAction = Arr::get($recaptcha, 'action', 'request_man_power');
        $this->recaptchaScoreThreshold = (float) Arr::get($recaptcha, 'score_threshold', 0.0);
        $this->recaptchaTimeout = (int) Arr::get($recaptcha, 'timeout', 5);

        $this->recaptchaEnabled = (bool) Arr::get($recaptcha, 'enabled', false)
            && filled($this->recaptchaSiteKey)
            && filled($this->recaptchaSecretKey);

        $this->data['tanggal_pengajuan'] ??= now()->toDateString();

        if ($this->recaptchaEnabled) {
            $this->data['recaptcha_token'] = null;
        }

        $this->form->fill($this->data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Hidden::make('tanggal_pengajuan')
                    ->default(fn () => now()->toDateString())
                    ->dehydrated(false),

                Group::make()
                    ->schema([
                        TextInput::make('nama_pengaju')
                            ->label(__('rekrutmen::livewire/public-request-man-power-form.fields.nama_pengaju'))
                            ->required()
                            ->maxLength(255)
                            ->placeholder(__('rekrutmen::livewire/public-request-man-power-form.placeholders.nama_pengaju')),
                        TextInput::make('email_address')
                            ->label(__('rekrutmen::livewire/public-request-man-power-form.fields.email_address'))
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->placeholder(__('rekrutmen::livewire/public-request-man-power-form.placeholders.email_address')),
                        TextInput::make('posisi_pengaju')
                            ->label(__('rekrutmen::livewire/public-request-man-power-form.fields.posisi_pengaju'))
                            ->required()
                            ->maxLength(255)
                            ->placeholder(__('rekrutmen::livewire/public-request-man-power-form.placeholders.posisi_pengaju')),
                        Select::make('company_id')
                            ->label(__('rekrutmen::livewire/public-request-man-power-form.fields.company_id'))
                            ->required()
                            ->options(fn (): array => Company::query()
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('division_id', null);
                            }),
                        Select::make('division_id')
                            ->label(__('rekrutmen::livewire/public-request-man-power-form.fields.division_id'))
                            ->required()
                            ->options(function (Get $get): array {
                                $companyId = $get('company_id');

                                if (! $companyId) {
                                    return [];
                                }

                                return Division::query()
                                    ->where('company_id', $companyId)
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all();
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?int $state): void {
                                if (! $state) {
                                    return;
                                }

                                $division = Division::query()->find($state);

                                if (! $division) {
                                    return;
                                }

                                $set('company_id', $division->company_id);
                            }),
                        Select::make('status_kebutuhan')
                            ->label(__('rekrutmen::livewire/public-request-man-power-form.fields.status_kebutuhan'))
                            ->required()
                            ->options(StatusKebutuhan::class)
                            ->default(StatusKebutuhan::NEW_HIRING)
                            ->live(),
                        TextInput::make('nama_karyawan_replacement')
                            ->label(__('rekrutmen::livewire/public-request-man-power-form.fields.nama_karyawan_replacement'))
                            ->maxLength(255)
                            ->nullable()
                            ->required(fn (callable $get) => $this->isReplacementStatus($get('status_kebutuhan')))
                            ->helperText(__('rekrutmen::livewire/public-request-man-power-form.helper_texts.nama_karyawan_replacement'))
                            ->placeholder(__('rekrutmen::livewire/public-request-man-power-form.placeholders.nama_karyawan_replacement'))
                            ->visible(fn (callable $get) => $this->isReplacementStatus($get('status_kebutuhan'))),
                        TextInput::make('posisi_dibutuhkan')
                            ->label(__('rekrutmen::livewire/public-request-man-power-form.fields.posisi_dibutuhkan'))
                            ->required()
                            ->maxLength(255)
                            ->placeholder(__('rekrutmen::livewire/public-request-man-power-form.placeholders.posisi_dibutuhkan')),
                        Select::make('level_pekerjaan')
                            ->label(__('rekrutmen::livewire/public-request-man-power-form.fields.level_pekerjaan'))
                            ->required()
                            ->options(RequestManPower::getTranslatedLevelPekerjaanOptions()),
                        TextInput::make('jumlah_karyawan_dibutuhkan')
                            ->label(__('rekrutmen::livewire/public-request-man-power-form.fields.jumlah_karyawan_dibutuhkan'))
                            ->required()
                            ->numeric()
                            ->default(1)
                            ->minValue(1),
                        TextInput::make('lokasi_penempatan')
                            ->label(__('rekrutmen::livewire/public-request-man-power-form.fields.lokasi_penempatan'))
                            ->required()
                            ->maxLength(255)
                            ->placeholder(__('rekrutmen::livewire/public-request-man-power-form.placeholders.lokasi_penempatan')),
                        DatePicker::make('estimasi_tanggal_join')
                            ->label(__('rekrutmen::livewire/public-request-man-power-form.fields.estimasi_tanggal_join'))
                            ->required(),
                        Textarea::make('job_description')
                            ->label(__('rekrutmen::livewire/public-request-man-power-form.fields.job_description'))
                            ->required()
                            ->rows(6)
                            ->columnSpanFull()
                            ->placeholder(__('rekrutmen::livewire/public-request-man-power-form.placeholders.job_description')),
                        Textarea::make('requirements_kualifikasi')
                            ->label(__('rekrutmen::livewire/public-request-man-power-form.fields.requirements_kualifikasi'))
                            ->required()
                            ->rows(6)
                            ->columnSpanFull()
                            ->placeholder(__('rekrutmen::livewire/public-request-man-power-form.placeholders.requirements_kualifikasi')),
                        Textarea::make('keterangan')
                            ->label(__('rekrutmen::livewire/public-request-man-power-form.fields.keterangan'))
                            ->nullable()
                            ->columnSpanFull()
                            ->placeholder(__('rekrutmen::livewire/public-request-man-power-form.placeholders.keterangan')),
                    ])
                    ->columns(1),

                Hidden::make('recaptcha_token')
                    ->default('')
                    ->dehydrated(),
            ]);
    }

    public function submit(): mixed
    {
        $this->dispatch('form-processing-started');

        try {
            $state = $this->form->getState();
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());
            $this->handleValidationError();
            $this->dispatch('form-processing-finished');

            return null;
        }

        if ($this->recaptchaEnabled) {
            $token = Arr::get($state, 'recaptcha_token');

            if (! $token) {
                $this->addError('data.recaptcha_token', __('rekrutmen::livewire/public-request-man-power-form.errors.recaptcha_required'));
                $this->handleValidationError();
                $this->dispatch('form-processing-finished');

                return null;
            }

            $service = new RecaptchaVerificationService;
            $isValid = $service->verify(
                $token,
                $this->recaptchaSecretKey,
                $this->recaptchaAction,
                request()?->getHost(),
                $this->recaptchaScoreThreshold,
                $this->recaptchaTimeout,
                request()?->ip()
            );

            if (! $isValid) {
                $this->addError('data.recaptcha_token', __('rekrutmen::livewire/public-request-man-power-form.errors.recaptcha_failed'));
                $this->handleValidationError();
                $this->dispatch('form-processing-finished');

                return null;
            }
        }

        if (
            $this->isReplacementStatus($state['status_kebutuhan'] ?? null)
            && blank($state['nama_karyawan_replacement'] ?? null)
        ) {
            $this->addError('data.nama_karyawan_replacement', __('rekrutmen::livewire/public-request-man-power-form.errors.nama_karyawan_replacement_required'));
            $this->handleValidationError();
            $this->dispatch('form-processing-finished');

            return null;
        }

        try {
            $dataToSave = Arr::except($state, ['recaptcha_token']);
            $dataToSave['tanggal_pengajuan'] = now()->toDateString();

            $rmp = RequestManPower::create($dataToSave);

            $rmp->sendSubmittedNotification();
            $rmp->sendApprovalRequestNotifications();

            $this->recentSubmission = [
                'id'                 => $rmp->getKey(),
                'status_response_id' => $rmp->status_response_id,
                'progress_url'       => $rmp->getPublicProgressUrl(),
                'posisi_dibutuhkan'  => $rmp->posisi_dibutuhkan,
                'nama_pengaju'       => $rmp->nama_pengaju,
                'status_kebutuhan'   => $rmp->status_kebutuhan->getLabel(),
                'nama_replacement'   => $rmp->nama_karyawan_replacement,
            ];

            Notification::make()
                ->title(__('rekrutmen::livewire/public-request-man-power-form.notifications.success.title'))
                ->body(__('rekrutmen::livewire/public-request-man-power-form.notifications.success.body'))
                ->success()
                ->send();
            $this->dispatch('form-processing-finished');

            return redirect()->to($rmp->getPublicProgressUrl());
        } catch (ValidationException $e) {
            $this->dispatch('form-processing-finished');
            throw $e;
        } catch (QueryException $e) {
            Log::error('Public request man power submission failed (query exception).', [
                'exception' => $e,
            ]);

            $this->addError('data', __('rekrutmen::livewire/public-request-man-power-form.errors.system'));
            $this->dispatch('form-errors-presented');
            $this->dispatch('form-processing-finished');

            return null;
        } catch (Throwable $e) {
            Log::error('Public request man power submission failed.', [
                'exception' => $e,
            ]);

            $this->addError('data', __('rekrutmen::livewire/public-request-man-power-form.errors.system'));
            $this->dispatch('form-errors-presented');
            $this->dispatch('form-processing-finished');

            return null;
        }
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
            ->label(__('rekrutmen::livewire/public-request-man-power-form.actions.submit'))
            ->extraAttributes([
                'class' => '!bg-primary-700 !text-white shadow-sm hover:!bg-primary-800 hover:!text-white focus-visible:!ring-primary-300',
            ], merge: true)
            ->submit('submit');
    }

    protected function isReplacementStatus(mixed $statusKebutuhan): bool
    {
        if ($statusKebutuhan instanceof StatusKebutuhan) {
            return $statusKebutuhan === StatusKebutuhan::REPLACEMENT;
        }

        if (! is_string($statusKebutuhan)) {
            return false;
        }

        return in_array($statusKebutuhan, [StatusKebutuhan::REPLACEMENT->value, StatusKebutuhan::REPLACEMENT->name], true);
    }

    protected function handleValidationError(): void
    {
        $this->dispatch('form-errors-presented');

        Notification::make()
            ->title(__('rekrutmen::livewire/public-request-man-power-form.notifications.validation.title'))
            ->body(__('rekrutmen::livewire/public-request-man-power-form.notifications.validation.body'))
            ->warning()
            ->send();
    }

    public function isRecaptchaEnabled(): bool
    {
        return $this->recaptchaEnabled;
    }

    public function getRecaptchaSiteKey(): ?string
    {
        return $this->recaptchaSiteKey;
    }

    public function getRecaptchaAction(): string
    {
        return $this->recaptchaAction ?? 'request_man_power';
    }
}
