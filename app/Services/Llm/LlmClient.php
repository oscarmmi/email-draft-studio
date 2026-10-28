<?php

namespace App\Services\Llm;

use App\Models\SupportMail;

interface LlmClient
{
    public function generateDraft(SupportMail $ticket, string $tone): string;

    public function name(): string;
}
