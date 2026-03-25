<?php

namespace Cesa\ExitClearance\Tests\Feature;

use Cesa\ExitClearance\Filament\Resources\RequestResource\Pages\ListRequests;
use Cesa\ExitClearance\Models\Request;
use Cesa\ExitClearance\Tests\ExitClearanceTestCase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Webkul\Security\Models\User as SecurityUser;

class RequestListFiltersTest extends ExitClearanceTestCase
{
    public function test_request_date_filter_limits_records_to_selected_date(): void
    {
        $this->actingAs($this->fakeExitClearanceUser([
            'view_any_exit_clearance_request',
        ]));

        $matchingRequest = Request::query()->create([
            'name'         => 'Alice',
            'email'        => 'alice@example.com',
            'request_date' => '2026-03-25',
        ]);

        $otherRequest = Request::query()->create([
            'name'         => 'Bob',
            'email'        => 'bob@example.com',
            'request_date' => '2026-03-24',
        ]);

        if (! Route::has('filament.admin.resources.requests.index')) {
            Route::get('/filament/admin/requests', fn (): string => 'requests')
                ->name('filament.admin.resources.requests.index');
        }

        Livewire::test(ListRequests::class)
            ->assertTableFilterExists('request_date')
            ->filterTable('request_date', [
                'request_date' => '2026-03-25',
            ])
            ->assertCanSeeTableRecords([$matchingRequest])
            ->assertCanNotSeeTableRecords([$otherRequest]);
    }

    private function fakeExitClearanceUser(array $abilities): SecurityUser
    {
        $user = new class extends SecurityUser
        {
            /** @var array<int, string> */
            public array $grantedAbilities = [];

            public function can($ability, $arguments = []): bool
            {
                return in_array($ability, $this->grantedAbilities, true);
            }
        };

        $user->id = 1;
        $user->grantedAbilities = $abilities;

        return $user;
    }
}
