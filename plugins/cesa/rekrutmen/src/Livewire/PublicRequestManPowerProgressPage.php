<?php

namespace Cesa\Rekrutmen\Livewire;

use Cesa\Rekrutmen\Models\RequestManPower;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\SimplePage;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class PublicRequestManPowerProgressPage extends SimplePage
{
    use InteractsWithFormActions;
    use InteractsWithForms;

    protected static string $layout = 'rekrutmen::layouts.form';

    protected string $view = 'rekrutmen::livewire.public-man-power-progress-page';

    public ?RequestManPower $requestManPower = null;

    public ?string $lookupEmail = null;

    public array $lookupResults = [];

    public bool $lookupSearched = false;

    protected int $lookupRateLimitMaxAttempts = 10;

    protected int $lookupRateLimitDecaySeconds = 60;

    public function mount(?string $response = null): void
    {
        if (blank($response)) {
            return;
        }

        $this->requestManPower = RequestManPower::query()
            ->with(['approvals', 'statusHistories'])
            ->where('status_response_id', $response)
            ->firstOrFail();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('lookupEmail')
                    ->label(__('rekrutmen::livewire/public-request-man-power-progress-page.lookup.email_label'))
                    ->email()
                    ->placeholder(__('rekrutmen::livewire/public-request-man-power-progress-page.lookup.email_placeholder'))
                    ->required()
                    ->maxLength(191),
            ])
            ->statePath('');
    }

    public function lookup(): mixed
    {
        $validated = $this->form->getState();

        if ($this->isLookupRateLimited()) {
            return null;
        }

        $this->lookupSearched = true;
        $this->lookupResults = $this->findByPublicEmail($validated['lookupEmail'])
            ->map(fn (RequestManPower $request): array => $this->buildLookupResult($request))
            ->all();

        return null;
    }

    public function getHeading(): string
    {
        return __('rekrutmen::livewire/public-request-man-power-progress-page.heading');
    }

    public function getSubheading(): string
    {
        return __('rekrutmen::livewire/public-request-man-power-progress-page.subheading');
    }

    public function hasLogo(): bool
    {
        return false;
    }

    /**
     * @return Collection<int, RequestManPower>
     */
    protected function findByPublicEmail(string $email): Collection
    {
        $normalizedEmail = $this->normalizeLookupEmail($email);

        if ($normalizedEmail === '') {
            return collect();
        }

        return RequestManPower::query()
            ->whereRaw('LOWER(email_address) = ?', [$normalizedEmail])
            ->whereNotNull('status_response_id')
            ->latest()
            ->get();
    }

    protected function buildLookupResult(RequestManPower $request): array
    {
        $status = $request->status;

        return [
            'status_response_id'    => $request->status_response_id,
            'posisi_dibutuhkan'     => $request->posisi_dibutuhkan ?? '-',
            'nama_pengaju'          => $request->nama_pengaju ?? '-',
            'status_label'          => $status?->getLabel() ?? '-',
            'status_classes'        => $this->getStatusBadgeClasses($request),
            'tanggal_pengajuan'     => $request->tanggal_pengajuan?->translatedFormat('d F Y') ?? '-',
            'lokasi_penempatan'     => $request->lokasi_penempatan ?? '-',
            'jumlah_karyawan'       => $request->jumlah_karyawan_dibutuhkan ?? '-',
            'estimasi_tanggal_join' => $request->estimasi_tanggal_join?->translatedFormat('d F Y') ?? '-',
            'url'                   => route('rekrutmen.public.request-man-power.progress', [
                'response' => $request->status_response_id,
            ]),
        ];
    }

    protected function getStatusBadgeClasses(RequestManPower $request): string
    {
        return match ($request->status?->value) {
            'approved' => 'bg-emerald-100 text-emerald-700',
            'rejected' => 'bg-red-100 text-red-700',
            'hold'     => 'bg-gray-100 text-gray-700',
            default    => 'bg-amber-100 text-amber-700',
        };
    }

    protected function normalizeLookupEmail(string $email): string
    {
        return Str::lower(trim($email));
    }

    protected function isLookupRateLimited(): bool
    {
        $key = sprintf('rekrutmen:request-man-power-progress-lookup:%s', request()?->ip() ?: 'guest');

        if (! RateLimiter::tooManyAttempts($key, $this->lookupRateLimitMaxAttempts)) {
            RateLimiter::hit($key, $this->lookupRateLimitDecaySeconds);

            return false;
        }

        $secondsRemaining = max(1, RateLimiter::availableIn($key));

        $this->addError('lookupEmail', __('rekrutmen::livewire/public-request-man-power-progress-page.lookup.rate_limit.body', [
            'seconds' => $secondsRemaining,
        ]));

        return true;
    }
}
