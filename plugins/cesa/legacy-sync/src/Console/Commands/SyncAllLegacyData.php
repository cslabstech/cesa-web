<?php

namespace Cesa\LegacySync\Console\Commands;

use Illuminate\Console\Command;
use Throwable;
use Webkul\PluginManager\Package;

class SyncAllLegacyData extends Command
{
    protected $signature = 'legacy:sync-all
                            {--connection=legacy_sync : Legacy database connection name}
                            {--host= : Override legacy DB host}
                            {--port= : Override legacy DB port}
                            {--username= : Override legacy DB username}
                            {--password= : Override legacy DB password}
                            {--document-database=app_cesa : Source database for document}
                            {--form-transfer-database=app_cesa : Source database for form-transfer}
                            {--exit-clearance-database=app_cesa : Source database for exit-clearance}
                            {--lead-database=app_lead : Source database for lead}
                            {--presensi-database=app_presensi : Source database for presensi}
                            {--shelf-database=app_shelf : Source database for shelf}
                            {--helpdesk-database=app_helpdesk : Source database for helpdesk}
                            {--truncate : Truncate target module tables before syncing}
                            {--chunk=250 : Chunk size for large legacy tables}
                            {--skip-install : Skip running plugin install commands before syncing}
                            {--force-install : Re-run plugin install commands even when the plugin is already installed}
                            {--skip-missing-users : Skip creating web-cesa users from legacy users when no match is found}
                            {--trust-legacy-user-ids : Fallback to legacy user IDs when no email mapping is available}
                            {--trust-legacy-company-ids : Fallback to legacy company IDs when no company mapping is available}';

    protected $description = 'Install required plugins and sync all supported legacy source databases.';

    /**
     * @var array<int, string>
     */
    protected array $pluginsToInstall = [
        'kepegawaian',
        'document',
        'exit-clearance',
        'form-transfer',
        'lead',
        'presensi',
        'payroll',
        'shelf',
        'helpdesk',
    ];

    public function handle(): int
    {
        try {
            if (! $this->shouldSkipInstall()) {
                foreach ($this->pluginsToInstall as $plugin) {
                    if (! $this->shouldRunInstallCommand($plugin)) {
                        $this->info(__('legacy-sync::console.plugin_already_installed', [
                            'plugin' => $plugin,
                        ]));

                        continue;
                    }

                    $this->info(__('legacy-sync::console.running_install', [
                        'plugin' => $plugin,
                    ]));

                    $exitCode = $this->callCommand($plugin.':install', [
                        '--no-interaction' => true,
                    ]);

                    if ($exitCode !== self::SUCCESS) {
                        $this->error(__('legacy-sync::console.install_failed', [
                            'plugin' => $plugin,
                        ]));

                        return $exitCode;
                    }
                }
            } else {
                $this->info(__('legacy-sync::console.skip_install'));
            }

            foreach ($this->syncJobs() as $job) {
                $this->info(__('legacy-sync::console.syncing_database', [
                    'modules'  => implode(', ', $job['modules']),
                    'database' => $job['database'],
                ]));

                $exitCode = $this->callCommand('legacy:sync', $this->buildLegacySyncArguments($job));

                if ($exitCode !== self::SUCCESS) {
                    $this->error(__('legacy-sync::console.database_sync_failed', [
                        'database' => $job['database'],
                    ]));

                    return $exitCode;
                }
            }

            $this->info(__('legacy-sync::console.full_sync_completed'));

            return self::SUCCESS;
        } catch (Throwable $throwable) {
            report($throwable);
            $this->error(__('legacy-sync::console.full_sync_failed', [
                'message' => $throwable->getMessage(),
            ]));

            return self::FAILURE;
        }
    }

    protected function shouldSkipInstall(): bool
    {
        return (bool) $this->option('skip-install');
    }

    protected function shouldForceInstall(): bool
    {
        return (bool) $this->option('force-install');
    }

    protected function shouldRunInstallCommand(string $plugin): bool
    {
        if ($this->shouldForceInstall()) {
            return true;
        }

        return ! Package::isPluginInstalled($plugin);
    }

    protected function callCommand(string $command, array $arguments = []): int
    {
        return (int) $this->call($command, $arguments);
    }

    /**
     * @return array<int, array{database: string, modules: array<int, string>}>
     */
    protected function syncJobs(): array
    {
        return [
            [
                'database' => (string) $this->option('document-database'),
                'modules'  => ['document'],
            ],
            [
                'database' => (string) $this->option('form-transfer-database'),
                'modules'  => ['form-transfer'],
            ],
            [
                'database' => (string) $this->option('exit-clearance-database'),
                'modules'  => ['exit-clearance'],
            ],
            [
                'database' => (string) $this->option('lead-database'),
                'modules'  => ['lead'],
            ],
            [
                'database' => (string) $this->option('presensi-database'),
                'modules'  => ['presensi'],
            ],
            [
                'database' => (string) $this->option('shelf-database'),
                'modules'  => ['shelf'],
            ],
            [
                'database' => (string) $this->option('helpdesk-database'),
                'modules'  => ['helpdesk'],
            ],
        ];
    }

    /**
     * @param  array{database: string, modules: array<int, string>}  $job
     * @return array<string, mixed>
     */
    protected function buildLegacySyncArguments(array $job): array
    {
        $arguments = [
            '--module'         => $job['modules'],
            '--connection'     => (string) $this->option('connection'),
            '--database'       => $job['database'],
            '--chunk'          => (string) $this->option('chunk'),
            '--no-interaction' => true,
        ];

        foreach (['host', 'port', 'username', 'password'] as $option) {
            $value = $this->option($option);

            if ($value !== null && $value !== '') {
                $arguments['--'.$option] = (string) $value;
            }
        }

        foreach ([
            'truncate',
            'skip-missing-users',
            'trust-legacy-user-ids',
            'trust-legacy-company-ids',
        ] as $flag) {
            if ((bool) $this->option($flag)) {
                $arguments['--'.$flag] = true;
            }
        }

        return $arguments;
    }
}
