<?php

namespace App\Http\Controllers;

use App\Models\SupportMail;
use App\Services\Mail\DraftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DraftController extends Controller
{
    public function store(Request $request, DraftService $draftService): JsonResponse
    {
        $validated = $request->validate([
            'support_mail_id' => ['required', 'integer', 'exists:support_mails,id'],
            'tone' => ['required', Rule::in(['calm', 'brief', 'apology'])],
        ]);

        $ticket = SupportMail::query()->findOrFail((int) $validated['support_mail_id']);
        $result = $draftService->generate($ticket, $validated['tone']);

        return response()->json([
            'id' => $result['draft']->id,
            'text' => $result['draft']->text,
            'tone' => $result['draft']->tone,
            'status' => $result['draft']->status,
            'provider' => $result['provider'],
            'fallback' => $result['fallback'],
            'rationale' => $result['rationale'],
        ]);
    }
}
