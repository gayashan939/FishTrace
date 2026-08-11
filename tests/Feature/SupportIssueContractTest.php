<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SupportIssueContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_mobile_user_can_submit_a_support_issue(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'fisher@fishtrace.demo')->firstOrFail();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/support/issues', [
            'subject' => '  Sync failed  ',
            'description' => '  A catch remains pending after reconnecting.  ',
        ])->assertCreated()
            ->assertJsonPath('data.subject', 'Sync failed')
            ->assertJsonPath('data.status', 'OPEN')
            ->assertJsonMissingPath('data.description');

        $this->assertDatabaseHas('support_issues', [
            'user_id' => $user->id,
            'organization_id' => $user->primaryOrganization()?->id,
            'subject' => 'Sync failed',
            'description' => 'A catch remains pending after reconnecting.',
            'status' => 'OPEN',
        ]);
    }

    public function test_support_issue_requires_authentication_and_valid_content(): void
    {
        $this->postJson('/api/v1/support/issues', [
            'subject' => 'Sync',
            'description' => 'A valid description.',
        ])->assertUnauthorized();

        $this->seed();
        Sanctum::actingAs(User::query()->firstOrFail());
        $this->postJson('/api/v1/support/issues', [
            'subject' => '',
            'description' => 'short',
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'subject',
            'description',
        ]);
    }
}
