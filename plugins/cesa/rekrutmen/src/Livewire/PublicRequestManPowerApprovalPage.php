<?php

namespace Cesa\Rekrutmen\Livewire;

use Cesa\Rekrutmen\Models\RequestManPowerApproval;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\SimplePage;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;

class PublicRequestManPowerApprovalPage extends SimplePage
{
    use InteractsWithFormActions;
    use InteractsWithForms;

    protected static string $layout = 'rekrutmen::layouts.form';

    protected string $view = 'rekrutmen::livewire.public-man-power-approval-page';

    public int|string $approvalId;

    public string $approvalToken = '';

    public RequestManPowerApproval $approvalRecord;

    public bool $actionTaken = false;

    public ?array $data = [];

    public function mount(int|string $approval, string $token): void
    {
        $this->approvalId = $approval;
        $this->approvalToken = $token;
        $this->approvalRecord = RequestManPowerApproval::query()
            ->with(['requestManPower.approvals'])
            ->findOrFail($approval);

        if (! hash_equals((string) $this->approvalRecord->action_token, $token)) {
            abort(404);
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('notes')
                    ->label(__('rekrutmen::livewire/public-request-man-power-approval-page.fields.notes'))
                    ->rows(4)
                    ->placeholder(__('rekrutmen::livewire/public-request-man-power-approval-page.placeholders.notes')),
            ])
            ->statePath('data');
    }

    public function approve(): void
    {
        $this->approvalRecord = $this->resolveFreshApprovalRecord();

        if (! $this->canProcessApproval($this->approvalRecord)) {
            return;
        }

        try {
            $this->approvalRecord->requestManPower->approveApprovalStep(
                $this->approvalRecord,
                Arr::get($this->form->getState(), 'notes'),
            );

            $this->refreshState();

            Notification::make()
                ->title(__('rekrutmen::livewire/public-request-man-power-approval-page.notifications.approved'))
                ->success()
                ->send();
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->warning()
                ->send();
        }
    }

    public function reject(): void
    {
        $this->approvalRecord = $this->resolveFreshApprovalRecord();

        if (! $this->canProcessApproval($this->approvalRecord)) {
            return;
        }

        try {
            $this->approvalRecord->requestManPower->rejectApprovalStep(
                $this->approvalRecord,
                Arr::get($this->form->getState(), 'notes'),
            );

            $this->refreshState();

            Notification::make()
                ->title(__('rekrutmen::livewire/public-request-man-power-approval-page.notifications.rejected'))
                ->danger()
                ->send();
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->warning()
                ->send();
        }
    }

    public function getHeading(): string
    {
        return __('rekrutmen::livewire/public-request-man-power-approval-page.heading');
    }

    public function getSubheading(): string
    {
        return __('rekrutmen::livewire/public-request-man-power-approval-page.subheading', [
            'name' => $this->approvalRecord->approver_name,
        ]);
    }

    public function hasLogo(): bool
    {
        return false;
    }

    public function isPendingApproval(): bool
    {
        $this->approvalRecord = $this->resolveFreshApprovalRecord();

        return $this->approvalRecord->requestManPower->isCurrentPendingApproval($this->approvalRecord);
    }

    protected function canProcessApproval(RequestManPowerApproval $approval): bool
    {
        if (! $approval->requestManPower->isCurrentPendingApproval($approval)) {
            Notification::make()
                ->title(__('rekrutmen::livewire/public-request-man-power-approval-page.notifications.already_processed'))
                ->warning()
                ->send();

            return false;
        }

        if ($approval->hasExpiredActionLink()) {
            Notification::make()
                ->title(__('rekrutmen::livewire/public-request-man-power-approval-page.notifications.link_expired'))
                ->danger()
                ->send();

            return false;
        }

        $rateLimitKey = sprintf('rekrutmen:request-man-power-approval:%s:%s', $approval->getKey(), request()->ip() ?: 'guest');
        $maxAttempts = (int) config('rekrutmen.security.approval_rate_limit.max_attempts', 5);
        $decaySeconds = (int) config('rekrutmen.security.approval_rate_limit.decay_seconds', 60);

        if (! RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            RateLimiter::hit($rateLimitKey, $decaySeconds);

            return true;
        }

        Notification::make()
            ->title(__('rekrutmen::livewire/public-request-man-power-approval-page.notifications.rate_limited', [
                'seconds' => max(1, RateLimiter::availableIn($rateLimitKey)),
            ]))
            ->warning()
            ->send();

        return false;
    }

    protected function refreshState(): void
    {
        $this->approvalRecord = $this->resolveFreshApprovalRecord();
        $this->actionTaken = true;
    }

    protected function resolveFreshApprovalRecord(): RequestManPowerApproval
    {
        return RequestManPowerApproval::query()
            ->with(['requestManPower.approvals'])
            ->findOrFail($this->approvalId);
    }
}
