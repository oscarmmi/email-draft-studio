<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

type Tone = 'calm' | 'brief' | 'apology';

interface DraftPreview {
    id: number;
    tone: Tone;
    text: string;
}

interface Ticket {
    id: number;
    subject: string;
    body: string;
    customerName: string;
    category: string;
    draft: DraftPreview | null;
}

interface GeneratedDraft extends DraftPreview {
    status: string;
    provider: string;
    fallback: boolean;
    rationale: string;
}

const props = defineProps<{
    tickets: Ticket[];
    provider: string;
}>();

const selectedId = ref<number | null>(props.tickets[0]?.id ?? null);
const tone = ref<Tone>('calm');
const generatedDraft = ref<GeneratedDraft | null>(null);
const loading = ref(false);
const error = ref('');
const copied = ref(false);

const selectedTicket = computed(
    () =>
        props.tickets.find((ticket) => ticket.id === selectedId.value) ?? null,
);

watch(
    selectedTicket,
    (ticket) => {
        tone.value = ticket?.draft?.tone ?? 'calm';
        generatedDraft.value = ticket?.draft
            ? {
                  ...ticket.draft,
                  status: 'draft',
                  provider: 'Saved draft',
                  fallback: false,
                  rationale:
                      'A saved draft for this ticket. Review the wording before using it.',
              }
            : null;
        error.value = '';
    },
    { immediate: true },
);

const tones: { value: Tone; title: string; description: string }[] = [
    { value: 'calm', title: 'Calm', description: 'Steady & reassuring' },
    { value: 'brief', title: 'Brief', description: 'Clear & concise' },
    { value: 'apology', title: 'Apology', description: 'Lead with empathy' },
];

const categoryLabels: Record<string, string> = {
    refund: 'Refund',
    delay: 'Delivery delay',
    'how-to': 'How-to',
    billing: 'Billing',
    feature: 'Feature request',
    unknown: 'Needs review',
};

async function generateDraft() {
    if (!selectedTicket.value || loading.value) return;

    loading.value = true;
    error.value = '';
    generatedDraft.value = null;

    try {
        const response = await fetch('/drafts', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN':
                    document.querySelector<HTMLMetaElement>(
                        'meta[name="csrf-token"]',
                    )?.content ?? '',
            },
            body: JSON.stringify({
                support_mail_id: selectedTicket.value.id,
                tone: tone.value,
            }),
        });

        const payload = await response.json();

        if (!response.ok) {
            throw new Error(
                payload.message ??
                    'We could not create a draft. Please try again.',
            );
        }

        generatedDraft.value = payload as GeneratedDraft;
        const ticket = props.tickets.find(
            (item) => item.id === selectedId.value,
        );
        if (ticket) ticket.draft = generatedDraft.value;
    } catch (cause) {
        error.value =
            cause instanceof Error
                ? cause.message
                : 'We could not create a draft. Please try again.';
    } finally {
        loading.value = false;
    }
}

async function copyDraft() {
    if (!generatedDraft.value) return;
    await navigator.clipboard.writeText(generatedDraft.value.text);
    copied.value = true;
    window.setTimeout(() => (copied.value = false), 1800);
}
</script>

<template>
    <Head title="Email Draft Studio">
        <meta
            name="description"
            content="Turn support tickets into thoughtful, editable email drafts. Nothing is ever sent."
        />
    </Head>

    <div class="min-h-screen bg-[#f7f8fa] text-slate-900">
        <header class="border-b border-slate-200/80 bg-white">
            <div
                class="mx-auto flex h-[72px] max-w-[1440px] items-center justify-between px-5 sm:px-8"
            >
                <a
                    href="/"
                    class="flex items-center gap-3"
                    aria-label="Harbor Desk home"
                >
                    <span
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#163d3b] text-white shadow-sm shadow-emerald-950/10"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            class="h-5 w-5"
                            aria-hidden="true"
                        >
                            <path
                                d="M4 11.5 12 5l8 6.5v7a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 18.5v-7Z"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linejoin="round"
                            />
                            <path
                                d="M9 20v-6h6v6M3 11l9-7 9 7"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </span>
                    <span>
                        <span
                            class="block text-[15px] font-semibold tracking-[-0.02em]"
                            >Harbor Desk</span
                        >
                        <span
                            class="block text-[11px] font-medium tracking-[0.04em] text-slate-500"
                            >EMAIL DRAFT STUDIO</span
                        >
                    </span>
                </a>

                <div class="flex items-center gap-3">
                    <span
                        class="hidden items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-600 sm:inline-flex"
                    >
                        <span
                            class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                        ></span>
                        {{ provider }}
                    </span>
                    <span
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-[#e9efed] text-xs font-semibold text-[#315c54]"
                        aria-label="Harbor Desk support team"
                        >HD</span
                    >
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-[1440px] px-5 pt-8 pb-12 sm:px-8 sm:pt-10">
            <div
                class="mb-7 flex flex-col justify-between gap-4 md:flex-row md:items-end"
            >
                <div>
                    <div
                        class="mb-2 flex items-center gap-2 text-xs font-medium text-slate-500"
                    >
                        <span>Workspace</span
                        ><span class="text-slate-300">/</span
                        ><span class="text-slate-700">Reply drafts</span>
                    </div>
                    <h1
                        class="text-[28px] font-semibold tracking-[-0.04em] text-slate-900 sm:text-[32px]"
                    >
                        A thoughtful reply, in a few clicks.
                    </h1>
                    <p class="mt-1.5 text-sm text-slate-500">
                        Choose a ticket, set the tone, and shape a first draft.
                    </p>
                </div>
                <div
                    class="flex w-fit items-center gap-2 rounded-lg border border-amber-200/80 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-800"
                >
                    <svg
                        viewBox="0 0 20 20"
                        fill="none"
                        class="h-4 w-4"
                        aria-hidden="true"
                    >
                        <path
                            d="M10 6.2v4.1m0 3.4h.01M8.4 3.5 2.8 13.2A1.8 1.8 0 0 0 4.4 16h11.2a1.8 1.8 0 0 0 1.6-2.8l-5.6-9.7a1.8 1.8 0 0 0-3.2 0Z"
                            stroke="currentColor"
                            stroke-width="1.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                    Drafts only · nothing is sent
                </div>
            </div>

            <div
                class="grid min-h-[650px] gap-5 lg:grid-cols-[340px_minmax(0,1fr)]"
            >
                <aside
                    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_2px_8px_rgba(15,23,42,0.025)]"
                >
                    <div
                        class="flex items-center justify-between border-b border-slate-100 px-5 py-4"
                    >
                        <div>
                            <h2 class="text-sm font-semibold">
                                Support tickets
                            </h2>
                            <p class="mt-0.5 text-xs text-slate-500">
                                {{ tickets.length }} sample conversations
                            </p>
                        </div>
                        <span
                            class="rounded-md bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-600"
                            >INBOX</span
                        >
                    </div>

                    <div v-if="tickets.length" class="space-y-1 p-2.5">
                        <button
                            v-for="ticket in tickets"
                            :key="ticket.id"
                            type="button"
                            class="group w-full rounded-xl border px-3.5 py-3 text-left transition-colors"
                            :class="
                                selectedId === ticket.id
                                    ? 'border-[#d4e5df] bg-[#f3f8f6]'
                                    : 'border-transparent hover:bg-slate-50'
                            "
                            :aria-pressed="selectedId === ticket.id"
                            @click="selectedId = ticket.id"
                        >
                            <div
                                class="mb-2 flex items-center justify-between gap-2"
                            >
                                <span
                                    class="truncate text-xs font-semibold text-slate-800"
                                    >{{ ticket.customerName }}</span
                                >
                                <span
                                    class="h-1.5 w-1.5 shrink-0 rounded-full"
                                    :class="
                                        selectedId === ticket.id
                                            ? 'bg-[#438775]'
                                            : 'bg-slate-300'
                                    "
                                ></span>
                            </div>
                            <p
                                class="line-clamp-2 text-[13px] leading-[1.4] font-medium text-slate-700"
                            >
                                {{ ticket.subject }}
                            </p>
                            <div
                                class="mt-2.5 flex items-center justify-between gap-2"
                            >
                                <span
                                    class="rounded-md px-2 py-1 text-[10px] font-medium capitalize"
                                    :class="
                                        selectedId === ticket.id
                                            ? 'bg-white text-[#477366]'
                                            : 'bg-slate-100 text-slate-500'
                                    "
                                    >{{
                                        categoryLabels[ticket.category] ??
                                        ticket.category
                                    }}</span
                                >
                                <span
                                    v-if="ticket.draft"
                                    class="text-[10px] font-medium text-emerald-700"
                                    >Draft saved</span
                                >
                                <span v-else class="text-[10px] text-slate-400"
                                    >Needs reply</span
                                >
                            </div>
                        </button>
                    </div>
                    <div v-else class="p-6 text-sm leading-6 text-slate-500">
                        No sample tickets yet. Run
                        <code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs"
                            >php artisan db:seed</code
                        >
                        to load the fictional Harbor Desk inbox.
                    </div>

                    <div
                        class="mx-4 mt-2 border-t border-slate-100 px-1 py-4 text-[11px] leading-5 text-slate-400"
                    >
                        Sample data is fictional and for demonstration only.
                    </div>
                </aside>

                <section
                    v-if="selectedTicket"
                    class="grid min-w-0 gap-5 xl:grid-cols-[minmax(0,0.88fr)_minmax(380px,1.12fr)]"
                >
                    <div class="flex min-w-0 flex-col gap-5">
                        <article
                            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-[0_2px_8px_rgba(15,23,42,0.025)] sm:p-6"
                        >
                            <div class="mb-5 flex items-center justify-between">
                                <div
                                    class="flex items-center gap-2 text-xs font-semibold tracking-[0.08em] text-slate-400 uppercase"
                                >
                                    <svg
                                        viewBox="0 0 20 20"
                                        fill="none"
                                        class="h-4 w-4"
                                        aria-hidden="true"
                                    >
                                        <path
                                            d="M3.3 5.1A1.6 1.6 0 0 1 4.9 3.5h10.2a1.6 1.6 0 0 1 1.6 1.6v9.8a1.6 1.6 0 0 1-1.6 1.6H4.9a1.6 1.6 0 0 1-1.6-1.6V5.1Z"
                                            stroke="currentColor"
                                            stroke-width="1.5"
                                        />
                                        <path
                                            d="m4 5 6 5 6-5"
                                            stroke="currentColor"
                                            stroke-width="1.5"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                    </svg>
                                    Incoming email
                                </div>
                                <span class="text-[11px] text-slate-400"
                                    >Ticket #{{
                                        String(selectedTicket.id).padStart(
                                            4,
                                            '0',
                                        )
                                    }}</span
                                >
                            </div>
                            <h2
                                class="text-[19px] leading-snug font-semibold tracking-[-0.025em] text-slate-900"
                            >
                                {{ selectedTicket.subject }}
                            </h2>
                            <div
                                class="mt-4 flex items-center gap-3 border-y border-slate-100 py-3.5"
                            >
                                <span
                                    class="flex h-9 w-9 items-center justify-center rounded-full bg-[#f1ece4] text-xs font-semibold text-[#85694d]"
                                    >{{
                                        selectedTicket.customerName
                                            .split(' ')
                                            .map((part) => part[0])
                                            .join('')
                                    }}</span
                                >
                                <div>
                                    <p
                                        class="text-xs font-semibold text-slate-800"
                                    >
                                        {{ selectedTicket.customerName }}
                                    </p>
                                    <p
                                        class="mt-0.5 text-[11px] text-slate-400"
                                    >
                                        Customer ·
                                        {{
                                            categoryLabels[
                                                selectedTicket.category
                                            ] ?? 'Other'
                                        }}
                                    </p>
                                </div>
                            </div>
                            <p
                                class="pt-4 text-[13px] leading-6 whitespace-pre-line text-slate-600"
                            >
                                {{ selectedTicket.body }}
                            </p>
                        </article>

                        <section
                            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-[0_2px_8px_rgba(15,23,42,0.025)] sm:p-6"
                        >
                            <div class="mb-4">
                                <h2 class="text-sm font-semibold">
                                    Choose a tone
                                </h2>
                                <p class="mt-1 text-xs text-slate-500">
                                    Set the feel of the reply. You can edit the
                                    draft afterward.
                                </p>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <label
                                    v-for="option in tones"
                                    :key="option.value"
                                    class="cursor-pointer rounded-xl border p-3 transition-colors"
                                    :class="
                                        tone === option.value
                                            ? 'border-[#92b5a9] bg-[#f2f8f5] ring-1 ring-[#92b5a9]'
                                            : 'border-slate-200 hover:border-slate-300'
                                    "
                                >
                                    <input
                                        v-model="tone"
                                        type="radio"
                                        name="tone"
                                        :value="option.value"
                                        class="sr-only"
                                    />
                                    <span
                                        class="block text-xs font-semibold"
                                        :class="
                                            tone === option.value
                                                ? 'text-[#295d4d]'
                                                : 'text-slate-700'
                                        "
                                        >{{ option.title }}</span
                                    >
                                    <span
                                        class="mt-1 block text-[10px] leading-4 text-slate-400"
                                        >{{ option.description }}</span
                                    >
                                </label>
                            </div>
                            <button
                                type="button"
                                class="mt-5 inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-[#183e39] px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-[#22534c] focus:ring-4 focus:ring-emerald-900/10 focus:outline-none disabled:cursor-wait disabled:opacity-70"
                                :disabled="loading"
                                @click="generateDraft"
                            >
                                <svg
                                    v-if="!loading"
                                    viewBox="0 0 20 20"
                                    fill="none"
                                    class="h-4 w-4"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="m10 2 1.5 5.2L17 9l-5.5 1.8L10 16l-1.6-5.2L3 9l5.4-1.8L10 2Z"
                                        stroke="currentColor"
                                        stroke-width="1.4"
                                        stroke-linejoin="round"
                                    />
                                    <path
                                        d="m16 13 .8 2.2L19 16l-2.2.8L16 19l-.8-2.2L13 16l2.2-.8L16 13Z"
                                        fill="currentColor"
                                    />
                                </svg>
                                <svg
                                    v-else
                                    viewBox="0 0 20 20"
                                    fill="none"
                                    class="h-4 w-4 animate-spin"
                                    aria-hidden="true"
                                >
                                    <circle
                                        cx="10"
                                        cy="10"
                                        r="7"
                                        stroke="currentColor"
                                        stroke-opacity=".35"
                                        stroke-width="2"
                                    />
                                    <path
                                        d="M17 10a7 7 0 0 0-7-7"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                    />
                                </svg>
                                {{
                                    loading
                                        ? 'Writing your draft…'
                                        : generatedDraft
                                          ? 'Regenerate draft'
                                          : 'Generate draft'
                                }}
                            </button>
                            <p
                                v-if="error"
                                role="alert"
                                class="mt-3 rounded-lg bg-rose-50 px-3 py-2 text-xs text-rose-700"
                            >
                                {{ error }}
                            </p>
                        </section>
                    </div>

                    <section
                        class="flex min-h-[460px] min-w-0 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_2px_8px_rgba(15,23,42,0.025)]"
                    >
                        <div
                            class="flex items-center justify-between border-b border-slate-100 px-5 py-4 sm:px-6"
                        >
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="text-sm font-semibold">
                                        Reply draft
                                    </h2>
                                    <span
                                        class="rounded-full bg-[#edf5f1] px-2 py-0.5 text-[10px] font-semibold tracking-wide text-[#3c7965] uppercase"
                                        >Not sent</span
                                    >
                                </div>
                                <p class="mt-1 text-xs text-slate-400">
                                    A starting point for your review
                                </p>
                            </div>
                            <button
                                v-if="generatedDraft"
                                type="button"
                                class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-200 px-2.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50"
                                @click="copyDraft"
                            >
                                <svg
                                    v-if="!copied"
                                    viewBox="0 0 20 20"
                                    fill="none"
                                    class="h-3.5 w-3.5"
                                    aria-hidden="true"
                                >
                                    <rect
                                        x="7"
                                        y="6"
                                        width="9"
                                        height="11"
                                        rx="1.5"
                                        stroke="currentColor"
                                        stroke-width="1.4"
                                    />
                                    <path
                                        d="M13 6V4.8A1.8 1.8 0 0 0 11.2 3H4.8A1.8 1.8 0 0 0 3 4.8v7.4A1.8 1.8 0 0 0 4.8 14H7"
                                        stroke="currentColor"
                                        stroke-width="1.4"
                                    />
                                </svg>
                                <svg
                                    v-else
                                    viewBox="0 0 20 20"
                                    fill="none"
                                    class="h-3.5 w-3.5"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="m4 10 4 4 8-8"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                                {{ copied ? 'Copied' : 'Copy draft' }}
                            </button>
                        </div>

                        <div v-if="generatedDraft" class="flex flex-1 flex-col">
                            <textarea
                                v-model="generatedDraft.text"
                                aria-label="Editable email draft"
                                class="min-h-[360px] flex-1 resize-y border-0 bg-white px-5 py-5 text-[13px] leading-[1.8] text-slate-700 outline-none focus:ring-0 sm:px-6"
                                spellcheck="true"
                            ></textarea>
                            <div
                                class="mx-5 border-t border-slate-100 py-4 sm:mx-6"
                            >
                                <div
                                    class="flex items-start gap-2.5 rounded-xl bg-[#f6f8f7] p-3.5"
                                >
                                    <span
                                        class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-white text-[#4a806d] shadow-sm"
                                    >
                                        <svg
                                            viewBox="0 0 20 20"
                                            fill="none"
                                            class="h-3.5 w-3.5"
                                            aria-hidden="true"
                                        >
                                            <path
                                                d="M10 2.5v2m0 11v2m7.5-7.5h-2m-11 0h-2m12.8-5.3-1.4 1.4m-7.8 7.8-1.4 1.4m10.6 0-1.4-1.4m-7.8-7.8L5.7 4.7"
                                                stroke="currentColor"
                                                stroke-width="1.4"
                                                stroke-linecap="round"
                                            />
                                            <circle
                                                cx="10"
                                                cy="10"
                                                r="4"
                                                stroke="currentColor"
                                                stroke-width="1.4"
                                            />
                                        </svg>
                                    </span>
                                    <div>
                                        <p
                                            class="text-[11px] font-semibold text-slate-700"
                                        >
                                            Why this draft
                                        </p>
                                        <p
                                            class="mt-1 text-[11px] leading-[1.55] text-slate-500"
                                        >
                                            {{ generatedDraft.rationale }}
                                        </p>
                                        <p
                                            class="mt-1.5 text-[10px] text-slate-400"
                                        >
                                            Generated with
                                            {{ generatedDraft.provider
                                            }}<span
                                                v-if="generatedDraft.fallback"
                                            >
                                                · local backup used</span
                                            >
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            v-else
                            class="flex flex-1 flex-col items-center justify-center px-8 py-14 text-center"
                        >
                            <span
                                class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#f2f6f4] text-[#527c6d]"
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    class="h-6 w-6"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M5 4.5h14a1.5 1.5 0 0 1 1.5 1.5v12a1.5 1.5 0 0 1-1.5 1.5h-14A1.5 1.5 0 0 1 3.5 18V6A1.5 1.5 0 0 1 5 4.5Z"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                    />
                                    <path
                                        d="M7.5 9h9m-9 3.5h6"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                        stroke-linecap="round"
                                    />
                                    <path
                                        d="m17.5 15.5 1 1 2-2"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </span>
                            <h3
                                class="mt-4 text-sm font-semibold text-slate-800"
                            >
                                Your draft will appear here
                            </h3>
                            <p
                                class="mt-1.5 max-w-[260px] text-xs leading-5 text-slate-500"
                            >
                                Pick a tone and generate a first reply. It stays
                                here for you to edit and review.
                            </p>
                            <div
                                class="mt-5 flex items-center gap-1.5 text-[10px] font-medium text-slate-400"
                            >
                                <svg
                                    viewBox="0 0 16 16"
                                    fill="none"
                                    class="h-3.5 w-3.5"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M5.5 7V5a2.5 2.5 0 0 1 5 0v2m-6.2 0h7.4c.7 0 1.3.6 1.3 1.3v4.4c0 .7-.6 1.3-1.3 1.3H4.3c-.7 0-1.3-.6-1.3-1.3V8.3c0-.7.6-1.3 1.3-1.3Z"
                                        stroke="currentColor"
                                        stroke-width="1.2"
                                        stroke-linecap="round"
                                    />
                                </svg>
                                Private workspace · no email connection
                            </div>
                        </div>
                    </section>
                </section>

                <section
                    v-else
                    class="flex min-h-[420px] items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center"
                >
                    <div class="max-w-sm">
                        <div
                            class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-500"
                        >
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                class="h-6 w-6"
                                aria-hidden="true"
                            >
                                <path
                                    d="M4 5.5A1.5 1.5 0 0 1 5.5 4h13A1.5 1.5 0 0 1 20 5.5v13a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 18.5v-13Z"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                />
                                <path
                                    d="M7.5 9h9m-9 4h6"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </div>
                        <h2 class="mt-4 text-base font-semibold">
                            Load the sample inbox
                        </h2>
                        <p class="mt-1.5 text-sm leading-6 text-slate-500">
                            Seed the database with six fictional Harbor Desk
                            tickets to try the studio.
                        </p>
                        <code
                            class="mt-4 inline-block rounded-lg bg-slate-100 px-3 py-2 text-xs text-slate-700"
                            >php artisan db:seed</code
                        >
                    </div>
                </section>
            </div>
            <footer
                class="mt-6 flex flex-col justify-between gap-2 px-1 text-[11px] text-slate-400 sm:flex-row"
            >
                <span>Harbor Desk · A support-writing demo</span>
                <span
                    >AI suggestions need a human review. Nothing is sent from
                    this app.</span
                >
            </footer>
        </main>
    </div>
</template>
