<?php

use App\Models\Draft;
use App\Models\SupportMail;
use App\Services\Llm\FakeLlmClient;
use App\Services\Llm\LlmClient;
use App\Services\Mail\DraftService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Str;
use Mockery\MockInterface;

function createSupportTicket(array $attributes = []): SupportMail
{
    return SupportMail::query()->create([
        'subject' => 'A sample ticket '.Str::random(8),
        'body' => 'Please help with my order.',
        'customer_name' => 'Riley Park',
        'category' => 'unknown',
        ...$attributes,
    ]);
}

it('shows the seeded fictional inbox on the studio page', function () {
    $this->seed(DatabaseSeeder::class);
    $this->withoutVite();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Welcome')
            ->has('tickets', 6)
            ->where('tickets.0.category', 'refund'));
});

it('binds the deterministic local writer when Gemini is not configured', function () {
    config(['services.gemini.key' => null]);

    expect(app(LlmClient::class))->toBeInstanceOf(FakeLlmClient::class);
});

it('lets a guest generate and persist a calm draft that includes the ticket subject', function () {
    config(['services.gemini.key' => null]);
    $ticket = createSupportTicket(['subject' => 'Package has not arrived', 'category' => 'delay']);

    $response = $this->postJson(route('drafts.store'), [
        'support_mail_id' => $ticket->id,
        'tone' => 'calm',
    ]);

    $response->assertOk()
        ->assertJsonPath('status', 'draft')
        ->assertJsonPath('tone', 'calm')
        ->assertJsonPath('provider', 'Harbor Desk demo writer')
        ->assertJsonPath('fallback', false);

    $draft = Draft::query()->sole();
    expect($draft->text)->toContain($ticket->subject)
        ->and($draft->status)->toBe('draft')
        ->and($draft->support_mail_id)->toBe($ticket->id);
});

it('falls back to the local writer when the configured provider times out', function () {
    $ticket = createSupportTicket(['subject' => 'Question about a duplicate charge', 'category' => 'billing']);

    $this->mock(LlmClient::class, function (MockInterface $mock): void {
        $mock->shouldReceive('generateDraft')->once()->andThrow(new RuntimeException('Provider timed out.'));
    });

    $this->postJson(route('drafts.store'), [
        'support_mail_id' => $ticket->id,
        'tone' => 'apology',
    ])
        ->assertOk()
        ->assertJsonPath('fallback', true)
        ->assertJsonPath('provider', 'Harbor Desk demo writer')
        ->assertJsonPath('tone', 'apology');

    expect(Draft::query()->sole()->text)->toContain($ticket->subject);
});

it('rejects unknown tones and missing tickets without creating a draft', function () {
    $this->postJson(route('drafts.store'), [
        'support_mail_id' => 999,
        'tone' => 'cheerful',
    ])->assertUnprocessable();

    expect(Draft::query()->count())->toBe(0);
});

it('explains the tone and ticket context used for each draft', function () {
    $ticket = createSupportTicket(['subject' => 'A feature idea for shared lists', 'category' => 'feature']);
    $service = app(DraftService::class);

    $result = $service->generate($ticket, 'brief');

    expect($result['rationale'])->toContain('focused and easy to scan')
        ->and($result['rationale'])->toContain('feature request')
        ->and($result['rationale'])->toContain($ticket->subject);
});
