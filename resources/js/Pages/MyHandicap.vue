<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import RoundHistory from '@/Components/Rounds/RoundHistory.vue';
import StartGameModal from '@/Components/Games/StartGameModal.vue';
import GamesList from '@/Components/Games/GamesList.vue';
import InputError from '@/Components/InputError.vue';

defineProps({
    index: { type: String, default: 'N/A' },
    rounds: { type: Array, default: () => [] },
    usedRoundIds: { type: Array, default: () => [] },
    userId: { type: Number, required: true },
    recentWindow: { type: Number, default: 0 },
    leagues: { type: Array, default: () => [] },
    games: { type: Array, default: () => [] },
});

const showStart = ref(false);
const joinForm = useForm({ join_code: '' });
const join = () => joinForm.post(route('games.join'));
</script>

<template>
    <Head title="My Handicap" />

    <AuthenticatedLayout>
        <PageHeader eyebrow="Your handicap" title="My Handicap" max-width="5xl">
            <template #actions>
                <dl class="flex items-end gap-8">
                    <div>
                        <dt class="text-xs uppercase tracking-widest text-cream/50">Index</dt>
                        <dd class="font-display text-4xl font-semibold tabular-nums text-brass-light">
                            {{ index }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-widest text-cream/50">Rounds</dt>
                        <dd class="font-display text-4xl font-semibold tabular-nums">{{ rounds.length }}</dd>
                    </div>
                </dl>
            </template>

            <template #below>
                <p class="mt-4 text-sm text-cream/60">
                    Your <span class="text-cream">Index</span> is one portable number, built from your
                    lowest differentials over your most recent
                    <span class="text-cream">{{ recentWindow }}</span>
                    round{{ recentWindow === 1 ? '' : 's' }} —
                    those that count are marked <span class="text-brass-light">●</span> below. Log rounds
                    you play <span class="text-cream">anywhere</span> to keep it accurate.
                    <Link :href="route('handicaps')" class="text-brass-light underline-offset-2 hover:underline">
                        How this is calculated →
                    </Link>
                </p>
            </template>
        </PageHeader>

        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            <!-- Live games -->
            <div class="mb-8 rounded-2xl border border-parchment-dark bg-cream p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="font-display text-lg font-semibold text-pine">Live games</h2>
                    <div class="flex items-center gap-2">
                        <form @submit.prevent="join" class="flex items-center gap-2">
                            <input
                                v-model="joinForm.join_code"
                                type="text"
                                placeholder="Join code"
                                maxlength="8"
                                class="w-28 rounded-lg border-pine/20 bg-cream text-sm font-mono uppercase tracking-widest text-ink shadow-sm placeholder:text-pine/30 placeholder:tracking-normal placeholder:font-sans focus:border-brass focus:ring-brass"
                            />
                            <button
                                type="submit"
                                :disabled="joinForm.processing || !joinForm.join_code"
                                class="shrink-0 rounded-full border border-pine/20 px-4 py-2 text-sm font-medium text-pine transition hover:border-brass disabled:opacity-50"
                            >
                                Join
                            </button>
                        </form>
                        <button
                            type="button"
                            @click="showStart = true"
                            class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-pine px-4 py-2 text-sm font-medium text-cream transition hover:bg-pine-light"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                            New game
                        </button>
                    </div>
                </div>
                <InputError :message="joinForm.errors.join_code" class="mt-1" />
                <div v-if="games.length" class="mt-3">
                    <GamesList :games="games" />
                </div>
            </div>

            <RoundHistory
                :rounds="rounds"
                :used-round-ids="usedRoundIds"
                :user-id="userId"
                :can-manage="true"
                :leagues="leagues"
            />
        </div>

        <StartGameModal :show="showStart" @close="showStart = false" />
    </AuthenticatedLayout>
</template>
