@include('rekrutmen::mail._layout', [
    'heading'        => __('rekrutmen::mail/request-man-power-status-changed.heading'),
    'greeting'       => __('rekrutmen::mail/request-man-power-status-changed.greeting', ['name' => $request->nama_pengaju]),
    'body'           => __('rekrutmen::mail/request-man-power-status-changed.body'),
    'summaryHeading' => __('rekrutmen::mail/request-man-power-status-changed.summary_heading'),
    'summary'        => $summary,
    'progressUrl'    => $progressUrl,
    'actionLabel'    => __('rekrutmen::mail/request-man-power-status-changed.view_progress'),
])
