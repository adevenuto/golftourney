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

// A non-admin viewing their own record can't manage rounds here — point them to
// their self-service My Handicap page instead.
const isSelf = computed(() => page.props.auth.user?.id === props.golfer.id);
const showMyHandicapLink = computed(() => isSelf.value && !isAdmin.value);
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
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                    All golfers
                </Link>
            </template>

            <template #actions>
                <dl class="flex items-end gap-8">
                    <div>
                        <dt class="text-xs tracking-widest uppercase text-cream/50">Index</dt>
                        <dd class="text-4xl font-semibold font-display tabular-nums text-brass-light">
                            {{ golfer.index }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs tracking-widest uppercase text-cream/50">Course Hcp</dt>
                        <dd class="text-4xl font-semibold font-display tabular-nums">
                            {{ golfer.course_handicap ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs tracking-widest uppercase text-cream/50">Rounds</dt>
                        <dd class="text-4xl font-semibold font-display tabular-nums">{{ rounds.length }}</dd>
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

        <div class="max-w-5xl px-4 py-8 mx-auto sm:px-6 lg:px-8">
            <RoundHistory
                :rounds="rounds"
                :used-round-ids="usedRoundIds"
                :user-id="golfer.id"
                :can-manage="isAdmin"
                :leagues="isAdmin ? [{ id: golfer.league_id, name: golfer.league }] : []"
                :for-label="`For ${fullName}`"
            >
                <template v-if="showMyHandicapLink" #empty>
                    <p class="mt-1 text-sm text-ink/50">
                        Log the rounds you play to build your handicap.
                    </p>
                    <Link
                        :href="route('my-handicap')"
                        class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-brass px-4 py-1.5 text-sm font-medium text-white transition hover:bg-brass-light"
                    >
                        Log your rounds on My Handicap
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                </template>
            </RoundHistory>
        </div>
    </AuthenticatedLayout>
</template>
