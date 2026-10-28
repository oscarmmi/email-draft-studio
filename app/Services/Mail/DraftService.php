<?php

namespace App\Services\Mail;

use App\Models\Draft;
use App\Models\SupportMail;
use App\Services\Llm\FakeLlmClient;
use App\Services\Llm\LlmClient;
use Illuminate\Support\Facades\Log;
use Throwable;

class DraftService
{
    public function __construct(private readonly LlmClient $llm) {}

    /** @return array{draft: Draft, provider: string, fallback: bool, rationale: string} */
    public function generate(SupportMail $ticket, string $tone): array
    {
        $fallback = false;

        try {
            $text = $this->llm->generateDraft($ticket, $tone);
            $provider = $this->llm->name();
        } catch (Throwable $exception) {
            Log::warning('Draft generation fell back to the local writer.', [
                'support_mail_id' => $ticket->id,
                'exception' => $exception::class,
            ]);
            $fallback = true;
            $provider = 'Harbor Desk demo writer';
            $text = (new FakeLlmClient)->generateDraft($ticket, $tone);
        }

        $draft = $ticket->drafts()->create([
            'tone' => $tone,
            'text' => $text,
            'status' => 'draft',
        ]);

        return [
            'draft' => $draft,
            'provider' => $provider,
            'fallback' => $fallback,
            'rationale' => $this->rationale($ticket, $tone),
        ];
    }

    private function rationale(SupportMail $ticket, string $tone): string
    {
        $toneReason = match ($tone) {
            'brief' => 'keeps the reply focused and easy to scan',
            'apology' => 'acknowledges the customer’s frustration before offering help',
            default => 'uses a steady, reassuring voice',
        };

        $category = match ($ticket->category) {
            'how-to' => 'how-to',
            'feature' => 'feature request',
            'unknown' => 'general support',
            default => $ticket->category,
        };

        return ucfirst($toneReason)." and addresses the {$category} topic from “{$ticket->subject}.” Review the wording before using it.";
    }
}
