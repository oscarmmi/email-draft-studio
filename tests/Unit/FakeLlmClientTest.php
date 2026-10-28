<?php

use App\Models\SupportMail;
use App\Services\Llm\FakeLlmClient;

it('includes the ticket subject and changes its opening for the selected tone', function () {
    $ticket = new SupportMail([
        'subject' => 'A sample subject to reply to',
        'body' => 'Please help with my order.',
        'customer_name' => 'Riley Park',
        'category' => 'unknown',
    ]);
    $client = new FakeLlmClient;

    $calmDraft = $client->generateDraft($ticket, 'calm');
    $apologyDraft = $client->generateDraft($ticket, 'apology');

    expect($calmDraft)->toContain($ticket->subject)
        ->and($calmDraft)->toContain('Thanks for letting us know')
        ->and($apologyDraft)->toContain('I’m sorry for the trouble')
        ->and($apologyDraft)->not->toBe($calmDraft);
});
