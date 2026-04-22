<?php

use Filament\Facades\Filament;
use Livewire\Livewire;
use Webkul\Security\Livewire\AcceptInvitation;
use Webkul\Security\Models\Invitation;

require_once __DIR__.'/../../../support/tests/Helpers/TestBootstrapHelper.php';

beforeEach(function () {
    TestBootstrapHelper::ensureERPInstalled();
    Filament::setCurrentPanel('admin');
});

it('redirects accepted invitations to the admin panel home url', function () {
    $invitation = Invitation::query()->create([
        'email' => 'invitee@example.com',
    ]);

    Livewire::test(AcceptInvitation::class, [
        'invitation' => $invitation->id,
    ])
        ->set('data.name', 'Invited User')
        ->set('data.password', 'password123')
        ->set('data.passwordConfirmation', 'password123')
        ->call('create')
        ->assertRedirect(Filament::getPanel('admin')->getUrl());

    expect(Invitation::query()->whereKey($invitation->id)->exists())->toBeFalse();
});
