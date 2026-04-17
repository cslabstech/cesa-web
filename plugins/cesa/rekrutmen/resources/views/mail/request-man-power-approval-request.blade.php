@include('rekrutmen::mail._layout', [
    'heading' => __('rekrutmen::mail/request-man-power-approval-request.heading'),
    'greeting' => __('rekrutmen::mail/request-man-power-approval-request.greeting', ['name' => $approverName]),
    'body' => __('rekrutmen::mail/request-man-power-approval-request.body'),
    'summaryHeading' => __('rekrutmen::mail/request-man-power-approval-request.summary_heading'),
    'summary' => $summary,
    'actionUrl' => $actionUrl,
    'progressUrl' => $progressUrl,
    'actionLabel' => __('rekrutmen::mail/request-man-power-approval-request.action'),
    'progressLabel' => __('rekrutmen::mail/request-man-power-approval-request.progress_action'),
    'footerNote' => __('rekrutmen::mail/request-man-power-approval-request.footer_note'),
])
