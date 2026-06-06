<?php

namespace Cesa\Rekrutmen\Models;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JobApplicationSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly JobApplication $jobApplication)
    {
        $this->onQueue(config('rekrutmen.notifications.queue', 'notifications'));
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $position = $this->jobApplication->jobPosting?->title;

        $message = (new MailMessage)
            ->subject(
                is_string($position) && $position !== ''
                    ? __('rekrutmen::mail/job-application-submitted.subject', ['position' => $position])
                    : __('rekrutmen::mail/job-application-submitted.subject_generic')
            )
            ->view('rekrutmen::mail.job-application-submitted', [
                'heading'        => __('rekrutmen::mail/job-application-submitted.heading'),
                'greeting'       => __('rekrutmen::mail/job-application-submitted.greeting', ['name' => $this->jobApplication->full_name]),
                'body'           => __('rekrutmen::mail/job-application-submitted.body'),
                'summaryHeading' => __('rekrutmen::mail/job-application-submitted.summary_heading'),
                'summary'        => $this->buildSummary(),
                'progressUrl'    => null,
                'actionLabel'    => null,
                'footerNote'     => __('rekrutmen::mail/job-application-submitted.footer_note'),
            ]);

        $mailer = config('rekrutmen.mail.job_application.mailer');

        if (is_string($mailer) && $mailer !== '') {
            $message->mailer($mailer);
        }

        $fromAddress = config('rekrutmen.mail.job_application.from.address');
        $fromName = config('rekrutmen.mail.job_application.from.name');

        if (is_string($fromAddress) && $fromAddress !== '') {
            $message->from($fromAddress, is_string($fromName) && $fromName !== '' ? $fromName : null);
        }

        $replyToAddress = config('rekrutmen.mail.job_application.reply_to.address');
        $replyToName = config('rekrutmen.mail.job_application.reply_to.name');

        if (is_string($replyToAddress) && $replyToAddress !== '') {
            $message->replyTo($replyToAddress, is_string($replyToName) && $replyToName !== '' ? $replyToName : null);
        }

        return $message;
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function buildSummary(): array
    {
        return [
            [
                'label' => __('rekrutmen::mail/job-application-submitted.summary_fields.application_id'),
                'value' => (string) $this->jobApplication->getKey(),
            ],
            [
                'label' => __('rekrutmen::mail/job-application-submitted.summary_fields.submission_date'),
                'value' => $this->jobApplication->created_at?->translatedFormat('d F Y H:i') ?? '-',
            ],
            [
                'label' => __('rekrutmen::mail/job-application-submitted.summary_fields.position'),
                'value' => $this->jobApplication->jobPosting?->title ?? '-',
            ],
        ];
    }
}
