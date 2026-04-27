<?php

namespace Cesa\FormTransfer\Livewire;

use Cesa\FormTransfer\Models\TransferRequest;
use Cesa\FormTransfer\Services\TransferApprovalNotificationService;
use Cesa\FormTransfer\Services\TransferRequestService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\SimplePage;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\RateLimiter;
use Webkul\PluginManager\Package;

class PublicTransferProgressPage extends SimplePage
{
    use InteractsWithFormActions;
    use InteractsWithForms;

    protected static string $layout = 'form-transfer::layouts.form';

    protected string $view = 'form-transfer::livewire.public-transfer-progress-page';

    public array $approvals = [];

    public array $summary = [];

    public string $statusLabel = '';

    public ?string $lookupEmail = null;

    public ?string $lookupReference = null;

    public array $lookupResults = [];

    public bool $lookupSearched = false;

    protected TransferRequestService $transferRequestService;

    protected TransferApprovalNotificationService $notificationService;

    protected int $lookupRateLimitMaxAttempts = 10;

    protected int $lookupRateLimitDecaySeconds = 60;

    public function boot(
        TransferRequestService $transferRequestService,
        TransferApprovalNotificationService $notificationService,
    ): void {
        $this->transferRequestService = $transferRequestService;
        $this->notificationService = $notificationService;
    }

    public function mount(?string $response = null): void
    {
        if (! Package::isPluginInstalled('form-transfer')) {
            abort(404);
        }

        if (blank($response)) {
            return;
        }

        $request = $this->transferRequestService->findByStatusResponseId($response);

        if (! $request) {
            abort(404);
        }

        $this->showProgressFor($request);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('lookupEmail')
                    ->label(__('form-transfer::public.progress.lookup.email_label'))
                    ->email()
                    ->placeholder(__('form-transfer::public.progress.lookup.email_placeholder'))
                    ->required()
                    ->maxLength(191),
                TextInput::make('lookupReference')
                    ->label(__('form-transfer::public.progress.lookup.reference_label'))
                    ->placeholder(__('form-transfer::public.progress.lookup.reference_placeholder'))
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

        if (filled($validated['lookupReference'] ?? null)) {
            return $this->lookupSpecificRequest($validated['lookupReference'], $validated['lookupEmail']);
        }

        $this->lookupSearched = true;
        $this->lookupResults = $this->transferRequestService
            ->findByPublicEmail($validated['lookupEmail'])
            ->map(fn (TransferRequest $request): array => $this->buildLookupResult($request))
            ->all();

        return null;
    }

    protected function lookupSpecificRequest(string $reference, string $email): mixed
    {
        $request = $this->transferRequestService->findByPublicLookup(
            $reference,
            $email,
        );

        if (! $request || blank($request->status_response_id)) {
            $this->addError('lookupReference', __('form-transfer::public.progress.lookup.not_found'));

            return null;
        }

        return redirect()->route('form-transfer.public.progress', [
            'response' => $request->status_response_id,
        ]);
    }

    public function getHeading(): string
    {
        return __('form-transfer::public.progress.heading');
    }

    public function getSubheading(): string
    {
        return __('form-transfer::public.progress.description');
    }

    public function hasLogo(): bool
    {
        return false;
    }

    protected function showProgressFor(TransferRequest $request): void
    {
        $this->summary = $this->notificationService->getRequestSummary($request);
        $this->approvals = $request->approvals ?? [];
        $this->statusLabel = $this->summary['status'] ?? '';
    }

    protected function buildLookupResult(TransferRequest $request): array
    {
        $summary = $this->notificationService->getRequestSummary($request);

        return [
            'uid'          => $summary['uid'] ?? '-',
            'title'        => $summary['title'] ?? 'Form Transfer',
            'requester'    => $summary['requester_name'] ?? '-',
            'status'       => $summary['status'] ?? '-',
            'status_color' => $summary['status_color'] ?? 'gray',
            'amount'       => $summary['transfer_amount'] ?? '0',
            'submitted_at' => $request->created_at?->format('d M Y H:i') ?? '-',
            'url'          => route('form-transfer.public.progress', [
                'response' => $request->status_response_id,
            ]),
        ];
    }

    protected function isLookupRateLimited(): bool
    {
        $key = sprintf('form-transfer:progress-lookup:%s', request()?->ip() ?: 'guest');

        if (! RateLimiter::tooManyAttempts($key, $this->lookupRateLimitMaxAttempts)) {
            RateLimiter::hit($key, $this->lookupRateLimitDecaySeconds);

            return false;
        }

        $secondsRemaining = max(1, RateLimiter::availableIn($key));

        Notification::make()
            ->title(__('form-transfer::public.progress.lookup.rate_limit.title'))
            ->body(__('form-transfer::public.progress.lookup.rate_limit.body', [
                'seconds' => $secondsRemaining,
            ]))
            ->warning()
            ->send();

        $this->addError('lookupReference', __('form-transfer::public.progress.lookup.rate_limit.body', [
            'seconds' => $secondsRemaining,
        ]));

        return true;
    }
}
