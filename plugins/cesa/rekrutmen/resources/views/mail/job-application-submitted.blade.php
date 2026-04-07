@include('rekrutmen::mail._layout', [
    'heading'        => $heading,
    'greeting'       => $greeting,
    'body'           => $body,
    'summaryHeading' => $summaryHeading,
    'summary'        => $summary,
    'progressUrl'    => $progressUrl,
    'actionLabel'    => $actionLabel,
])
