<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import RoundHistory from '@/Components/Rounds/RoundHistory.vue';

defineProps({
    index: { type: String, default: 'N/A' },
    rounds: { type: Array, default: () => [] },
    usedRoundIds: { type: Array, default: () => [] },
    userId: { type: Number, required: true },
    recentWindow: { type: Number, default: 0 },
});
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
            <RoundHistory
                :rounds="rounds"
                :used-round-ids="usedRoundIds"
                :user-id="userId"
                :can-manage="true"
                :allow-league-round="false"
            />
        </div>
    </AuthenticatedLayout>
</template>
