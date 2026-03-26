<?php

namespace Cesa\Document\Tests\Feature;

use Cesa\Document\Models\Document;
use Cesa\Document\Policies\DocumentPolicy;
use Mockery;
use Tests\TestCase;
use Webkul\Security\Models\User;

class DocumentPolicyTest extends TestCase
{
    public function test_view_any_uses_generated_document_permission_key(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('can')->once()->with('view_any_document_document')->andReturnTrue();

        $this->assertTrue((new DocumentPolicy)->viewAny($user));
    }

    public function test_view_uses_generated_document_permission_key(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('can')->once()->with('view_document_document')->andReturnTrue();

        $document = Mockery::mock(Document::class);

        $this->assertTrue((new DocumentPolicy)->view($user, $document));
    }

    public function test_create_uses_generated_document_permission_key(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('can')->once()->with('create_document_document')->andReturnTrue();

        $this->assertTrue((new DocumentPolicy)->create($user));
    }
}
