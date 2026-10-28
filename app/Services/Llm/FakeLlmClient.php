<?php

namespace App\Services\Llm;

use App\Models\SupportMail;

class FakeLlmClient implements LlmClient
{
    public function generateDraft(SupportMail $ticket, string $tone): string
    {
        $opening = match ($tone) {
            'apology' => 'I’m sorry for the trouble this has caused.',
            'brief' => 'Thanks for reaching out.',
            default => 'Thanks for letting us know. I’m here to help.',
        };

        $nextStep = match ($ticket->category) {
            'refund' => 'I’ve reviewed your refund request and will make sure it gets to the right person for a quick review.',
            'delay' => 'I understand waiting is frustrating. I’ll check the latest tracking details and share an update as soon as I have one.',
            'how-to' => 'I can walk you through this. Tell me which step you’re on and I’ll point you in the right direction.',
            'billing' => 'I’ll take a closer look at the charge with you. Please share the invoice date if it isn’t already included in your account details.',
            'feature' => 'Thanks for the thoughtful suggestion. I’ll pass this along to our product team for consideration.',
            default => 'I’d be glad to look into this. Could you share a little more detail so I can point you to the right solution?',
        };

        return "Subject: Re: {$ticket->subject}\n\nHi {$ticket->customer_name},\n\n{$opening} {$nextStep}\n\nPlease reply if there’s anything else I can help with.\n\nBest,\nHarbor Desk Support";
    }

    public function name(): string
    {
        return 'Harbor Desk demo writer';
    }
}
