@include('rekrutmen::mail._layout', [
    'heading'        => __('rekrutmen::mail/request-man-power-submitted.heading'),
    'greeting'       => __('rekrutmen::mail/request-man-power-submitted.greeting', ['name' => $request->nama_pengaju]),
    'body'           => __('rekrutmen::mail/request-man-power-submitted.body'),
    'summaryHeading' => __('rekrutmen::mail/request-man-power-submitted.summary_heading'),
    'summary'        => $summary,
    'progressUrl'    => $progressUrl,
    'actionLabel'    => __('rekrutmen::mail/request-man-power-submitted.view_progress'),
])
