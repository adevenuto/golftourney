<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import RoundHistory from '@/Components/Rounds/RoundHistory.vue';

const props = defineProps({
    golfer: { type: Object, required: true },
    rounds: { type: Array, default: () => [] },
    usedRoundIds: { type: Array, default: () => [] },
});

const page = usePage();
const isAdmin = computed(() => page.props.auth.user?.role === 'admin');
const fullName = computed(() => `${props.golfer.first_name} ${props.golfer.last_name}`);
</script>

<template>
    <Head :title="`${fullName} — Rounds`" />

    <AuthenticatedLayout>
        <PageHeader eyebrow="Member" :title="fullName" max-width="5xl" capitalize-title>
            <template #top>
                <Link
                    :href="route('golfers.index')"
                    class="inline-flex items-center gap-1.5 text-sm text-cream/70 transition hover:text-brass-light"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                    All golfers
                </Link>
            </template>

            <template #actions>
                <dl class="flex items-end gap-8">
                    <div>
                        <dt class="text-xs uppercase tracking-widest text-cream/50">Index</dt>
                        <dd class="font-display text-4xl font-semibold tabular-nums text-brass-light">
                            {{ golfer.index }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-widest text-cream/50">Course Hcp</dt>
                        <dd class="font-display text-4xl font-semibold tabular-nums">
                            {{ golfer.course_handicap ?? '—' }}
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
                    The <span class="text-cream">Index</span> is a portable handicap from this golfer's
                    lowest differentials over their most recent
                    <span class="text-cream">{{ golfer.recent_window }}</span>
                    round{{ golfer.recent_window === 1 ? '' : 's' }} — those that count are marked
                    <span class="text-brass-light">●</span> below. The
                    <span class="text-cream">Course Handicap</span> applies it to this course's
                    rating, slope, and par.
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
                :user-id="golfer.id"
                :can-manage="isAdmin"
                :allow-league-round="isAdmin"
                :league-name="golfer.league"
                :for-label="`For ${fullName}`"
            />
        </div>
    </AuthenticatedLayout>
</template>
