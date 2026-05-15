<?php

return [
    'navigation' => [
        'label' => 'Recruitment Progress',
    ],

    'guide' => [
        'label'         => 'Guide',
        'close'         => 'Close',
        'modal_heading' => 'Recruitment Progress Report Guide',
        'modal_content' => <<<'HTML'
<div class="space-y-4 text-sm text-gray-700 dark:text-gray-300">
    <div>
        <h4 class="font-semibold text-gray-950 dark:text-white">1. Data Sources</h4>
        <ul class="mt-1 list-disc space-y-1 pl-5">
            <li><strong>MPP (Request Man Power)</strong> — official headcount demand per position. Only requests with <em>Approved</em>, <em>Hold</em>, or <em>Pending</em> status are counted.</li>
            <li><strong>Job Posting</strong> — openings linked to MPP. One opening can represent multiple MPP requests.</li>
            <li><strong>Candidates</strong> — applicants registered in job applications. Statuses include <em>In Progress</em>, <em>Hired</em>, <em>Rejected</em>, and <em>Withdrawn</em>.</li>
            <li><strong>Activities</strong> — recruitment history recorded through pipeline activities and stage changes.</li>
        </ul>
    </div>

    <div>
        <h4 class="font-semibold text-gray-950 dark:text-white">2. Period Filters & Snapshot</h4>
        <ul class="mt-1 list-disc space-y-1 pl-5">
            <li><strong>MPP Snapshot Until</strong> — the until date is the cutoff. MPP submitted after this date is <em>not counted</em>.</li>
            <li><strong>Activities From</strong> — limits the activity updates displayed. It does not affect hired or demand totals because those are based on the snapshot.</li>
            <li><strong>Remaining Demand, Demand, and Hired</strong> metrics are always based on the snapshot, not the activity range.</li>
        </ul>
    </div>

    <div>
        <h4 class="font-semibold text-gray-950 dark:text-white">3. Demand Calculation (Demand / Hired / Remaining)</h4>
        <ul class="mt-1 list-disc space-y-1 pl-5">
            <li><strong>Demand</strong> = total headcount from <em>Approved + Hold</em> MPP linked to openings. If none are approved, the oldest <em>Pending</em> MPP is used as fallback.</li>
            <li><strong>Hired</strong> = unique hired candidates entered before the snapshot date.</li>
            <li><strong>Remaining</strong> = Demand − Hired, with a minimum of 0.</li>
            <li>When one opening has multiple MPP requests, hired candidates are allocated to MPP in request-date order.</li>
        </ul>
    </div>

    <div>
        <h4 class="font-semibold text-gray-950 dark:text-white">4. Work Focus (Tabs)</h4>
        <ul class="mt-1 list-disc space-y-1 pl-5">
            <li><strong>Needs Follow-up</strong> — remaining demand is greater than 0 and the request is not on hold.</li>
            <li><strong>Data Needs Cleanup</strong> — the data chain has gaps such as MPP without openings, expired openings, or hires exceeding demand.</li>
            <li><strong>Has Updates</strong> — activities were recorded during the selected period.</li>
            <li><strong>Hold</strong> — all MPP for the position are on hold.</li>
            <li><strong>Fulfilled</strong> — hired count is greater than or equal to demand.</li>
        </ul>
    </div>

    <div>
        <h4 class="font-semibold text-gray-950 dark:text-white">5. Excel Export (3 Sheets)</h4>
        <ul class="mt-1 list-disc space-y-1 pl-5">
            <li><strong>MPP Overview</strong> — monthly snapshot of open positions, demand, joins for the month, remaining demand, holds, and company.</li>
            <li><strong>Position Detail</strong> — end-period snapshot by MPP request with request date, age, demand vs hired vs remaining, fulfillment status, and follow-up priority.</li>
            <li><strong>Recruitment Activities</strong> — all recruitment activity batches for the selected period, including stage, passed/failed/pending counts, and PIC.</li>
        </ul>
    </div>

    <div>
        <h4 class="font-semibold text-gray-950 dark:text-white">6. Cross-checking</h4>
        <ul class="mt-1 list-disc space-y-1 pl-5">
            <li>Compare <strong>Demand</strong> in the UI with the "MPP Demand" column in Position Detail. Both must match for the same snapshot.</li>
            <li><strong>Hired</strong> in the UI equals the number of names in the "Joined Employee" column in Position Detail when the filter period matches.</li>
            <li>If numbers differ, check whether the period filters match, whether MPP is approved, and whether hired candidates fall outside the snapshot.</li>
            <li>The <strong>Data Needs Cleanup</strong> tab marks positions with data gaps and should be reviewed when report data does not line up.</li>
        </ul>
    </div>
</div>
HTML,
    ],
];
