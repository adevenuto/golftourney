<script setup>
/*
 * Phase 1: a minimal, functional live-game page — enough to play a game through
 * (lobby → active → finish) over plain Inertia posts, no realtime yet.
 * Phase 3 restyles this into the mobile-first scorecard (pinned column +
 * horizontal-scroll holes + per-hole stepper) and Phase 2 adds live sync.
 */
import { computed, reactive } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
    game: { type: Object, required: true },
});

const page = usePage();
const meId = computed(() => page.props.auth.user?.id);
const isOwner = computed(() => props.game.owner_id === meId.value);
const me = computed(() => props.game.players.find((p) => p.user_id === meId.value) ?? null);

const fullName = (p) => `${p.first_name} ${p.last_name}`;

/* ---------- my score entry (own row only) ---------- */
// Local copy of my holes so inputs stay responsive; persisted per-cell on change.
const myHoles = reactive({ ...(me.value?.holes ?? {}) });

function saveHole(hole) {
    const raw = myHoles[hole];
    const strokes = raw === '' || raw == null ? null : Number(raw);
    router.patch(
        route('games.scores.update', props.game.id),
        { hole, strokes },
        { preserveScroll: true },
    );
}

/* ---------- lifecycle actions ---------- */
const post = (name) => router.post(route(name, props.game.id), {}, { preserveScroll: true });
const start = () => post('games.start');
const finalize = () => post('games.finalize');
const abandon = () => post('games.abandon');

const canStart = computed(() => props.game.players.length >= 2);
</script>

<template>
    <Head title="Live game" />

    <AuthenticatedLayout>
        <PageHeader eyebrow="Live game" :title="game.course_name || 'Casual game'" max-width="5xl">
            <template #below>
                <p class="text-sm text-cream/70">
                    {{ game.teebox ? game.teebox + ' tee · ' : '' }}{{ game.holes }} holes · Status: {{ game.status }}
                </p>
            </template>
        </PageHeader>

        <div class="max-w-5xl px-4 py-8 mx-auto sm:px-6">
            <!-- Lobby -->
            <div v-if="game.status === 'lobby'" class="p-6 border rounded-2xl border-parchment-dark bg-cream">
                <h2 class="text-lg font-semibold font-display text-pine">Waiting to start</h2>
                <p class="mt-1 text-sm text-ink/70">
                    Share this code so others can join:
                    <span class="px-2 py-0.5 ml-1 font-mono text-base font-semibold rounded bg-pine/10 text-pine tracking-widest">{{ game.join_code }}</span>
                </p>
                <ul class="mt-4 space-y-1 text-sm text-ink/80">
                    <li v-for="p in game.players" :key="p.user_id" class="capitalize">
                        {{ fullName(p) }}<span v-if="p.is_owner" class="text-ink/40"> · host</span>
                    </li>
                </ul>
                <div v-if="isOwner" class="flex gap-3 mt-6">
                    <button
                        type="button"
                        :disabled="!canStart"
                        @click="start"
                        class="px-5 py-2 text-sm font-medium transition rounded-full bg-pine text-cream hover:bg-pine-light disabled:opacity-50"
                    >
                        Start game
                    </button>
                    <button type="button" @click="abandon" class="px-4 py-2 text-sm font-medium transition rounded-full text-ink/60 hover:text-ink">Cancel</button>
                </div>
                <p v-else class="mt-4 text-sm text-ink/50">The host will start the game.</p>
            </div>

            <!-- Active: the scorecard -->
            <div v-else-if="game.status === 'active'" class="overflow-x-auto border rounded-2xl border-parchment-dark bg-cream">
                <table class="min-w-full text-sm text-center">
                    <thead>
                        <tr class="border-b border-parchment-dark text-pine">
                            <th class="sticky left-0 px-4 py-3 text-left bg-cream">Player</th>
                            <th v-for="h in game.hole_numbers" :key="h" class="px-3 py-3 tabular-nums">{{ h }}</th>
                            <th class="px-4 py-3">Tot</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in game.players" :key="p.user_id" class="border-b border-parchment-dark/60">
                            <td class="sticky left-0 px-4 py-3 font-medium text-left capitalize bg-cream text-ink">{{ fullName(p) }}</td>
                            <template v-if="p.user_id === meId">
                                <td v-for="h in game.hole_numbers" :key="h" class="px-1 py-2">
                                    <input
                                        v-model="myHoles[h]"
                                        @change="saveHole(h)"
                                        type="number"
                                        min="1"
                                        max="20"
                                        class="w-10 text-center rounded border-pine/20 tabular-nums focus:border-brass focus:ring-brass"
                                    />
                                </td>
                            </template>
                            <template v-else>
                                <td v-for="h in game.hole_numbers" :key="h" class="px-3 py-3 tabular-nums text-ink/70">{{ p.holes[h] ?? '—' }}</td>
                            </template>
                            <td class="px-4 py-3 font-semibold tabular-nums text-pine">{{ p.gross || '—' }}</td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="isOwner" class="flex gap-3 p-4 border-t border-parchment-dark">
                    <button type="button" @click="finalize" class="px-5 py-2 text-sm font-medium transition rounded-full bg-pine text-cream hover:bg-pine-light">
                        Finish &amp; post rounds
                    </button>
                    <button type="button" @click="abandon" class="px-4 py-2 text-sm font-medium transition rounded-full text-red-700 hover:text-red-800">Cancel game</button>
                </div>
            </div>

            <!-- Completed -->
            <div v-else class="p-6 text-center border rounded-2xl border-parchment-dark bg-cream">
                <h2 class="text-lg font-semibold font-display text-pine">
                    {{ game.status === 'completed' ? 'Game finished' : 'Game canceled' }}
                </h2>
                <ul v-if="game.status === 'completed'" class="mt-4 space-y-1 text-sm text-ink/80">
                    <li v-for="p in game.players" :key="p.user_id" class="capitalize">
                        {{ fullName(p) }} — <span class="font-semibold tabular-nums text-pine">{{ p.gross }}</span>
                    </li>
                </ul>
                <a href="/my-handicap" class="inline-block mt-6 text-sm font-medium text-pine hover:text-brass-dark">Back to My Handicap →</a>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
