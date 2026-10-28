<?php

namespace App\Http\Controllers;

use App\Models\SupportMail;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudioController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Welcome', [
            'tickets' => SupportMail::query()
                ->with(['drafts' => fn ($query) => $query->latest()])
                ->orderBy('id')
                ->get()
                ->map(fn (SupportMail $ticket): array => [
                    'id' => $ticket->id,
                    'subject' => $ticket->subject,
                    'body' => $ticket->body,
                    'customerName' => $ticket->customer_name,
                    'category' => $ticket->category,
                    'draft' => $ticket->drafts->first() ? [
                        'id' => $ticket->drafts->first()->id,
                        'tone' => $ticket->drafts->first()->tone,
                        'text' => $ticket->drafts->first()->text,
                    ] : null,
                ]),
            'provider' => config('services.gemini.key') ? 'Gemini enabled' : 'Local demo mode',
            'flash' => [
                'generatedDraft' => $request->session()->get('generatedDraft'),
            ],
        ]);
    }
}
