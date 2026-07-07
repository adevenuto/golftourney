<script setup>
/*
 * Phase 2: the live game page. Server props are the source of truth on load
 * and after lifecycle actions; Laravel Echo (Pusher) carries live deltas.
 * Own-scores-only writes go through axios (optimistic, silent 204) so peers
 * update via ->toOthers() without the editor reloading. Phase 3 restyles this
 * minimal layout into the mobile-first scorecard.
 */
import { computed, onBeforeUnmount, onMounted, reactive, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
    game: { type: Object, required: true },
});

const clone = (v) => JSON.parse(JSON.stringify(v));

// Local, mutable copy — Echo deltas and optimistic edits patch this; a fresh
// server load (start/finalize reloads) re-syncs it.
const game = reactive(clone(props.game));
watch(
    () => props.game,
    (g) => Object.assign(game, clone(g)),
    { deep: true },
);

const page = usePage();
const meId = computed(() => page.props.auth.user?.id);
const isOwner = computed(() => game.owner_id === meId.value);
const myPlayer = computed(() => game.players.find((p) => p.user_id === meId.value) ?? null);
const canStart = computed(() => game.players.length >= 2);

const fullName = (p) => `${p.first_name} ${p.last_name}`;
const grossOf = (p) =>
    Object.values(p.holes ?? {}).reduce((sum, v) => sum + (v == null || v === '' ? 0 : Number(v)), 0);

/* ---------- presence (who's live) ---------- */
const online = reactive(new Set());
const isOnline = (userId) => online.has(userId);

/* ---------- my score entry (own row only, optimistic) ---------- */
function saveHole(hole) {
    if (!myPlayer.value) return;
    const raw = myPlayer.value.holes[hole];
    const strokes = raw === '' || raw == null ? null : Number(raw);

    // Optimistic already applied via v-model; persist + let peers know.
    window.axios
        .patch(route('games.scores.update', game.id), { hole, strokes })
        .catch(() => router.reload({ only: ['game'] })); // resync on failure
}

/* ---------- lifecycle actions ---------- */
const act = (name) => router.post(route(name, game.id), {}, { preserveScroll: true });
const start = () => act('games.start');
const finalize = () => act('games.finalize');
const abandon = () => act('games.abandon');

/* ---------- realtime wiring ---------- */
let hadConnection = false;

onMounted(() => {
    if (!window.Echo) return;

    window.Echo.join(`game.${game.id}`)
        .here((users) => {
            online.clear();
            users.forEach((u) => online.add(u.id));
        })
        .joining((u) => online.add(u.id))
        .leaving((u) => online.delete(u.id))
        .listen('.score.updated', (e) => {
            const p = game.players.find((pl) => pl.user_id === e.userId);
            if (p) p.holes[e.hole] = e.strokes;
        })
        .listen('.player.joined', (e) => {
            if (!game.players.some((pl) => pl.user_id === e.player.user_id)) {
                game.players.push(e.player);
            }
        })
        .listen('.game.started', () => router.reload({ only: ['game'] }))
        .listen('.game.completed', () => router.reload({ only: ['game'] }));

    // On reconnect (not the first connect), re-sync any deltas we missed.
    const pusher = window.Echo.connector?.pusher;
    pusher?.connection.bind('connected', () => {
        if (hadConnection) router.reload({ only: ['game'] });
        hadConnection = true;
    });
});

onBeforeUnmount(() => {
    if (window.Echo) window.Echo.leave(`game.${game.id}`);
});
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
                    <li v-for="p in game.players" :key="p.user_id" class="flex items-center gap-2 capitalize">
                        <span class="h-1.5 w-1.5 rounded-full" :class="isOnline(p.user_id) ? 'bg-pine' : 'bg-ink/20'"></span>
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
                            <td class="sticky left-0 px-4 py-3 font-medium text-left capitalize bg-cream text-ink">
                                <span class="inline-flex items-center gap-2">
                                    <span class="h-1.5 w-1.5 rounded-full" :class="isOnline(p.user_id) ? 'bg-pine' : 'bg-ink/20'"></span>
                                    {{ fullName(p) }}
                                </span>
                            </td>
                            <template v-if="p.user_id === meId">
                                <td v-for="h in game.hole_numbers" :key="h" class="px-1 py-2">
                                    <input
                                        v-model="p.holes[h]"
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
                            <td class="px-4 py-3 font-semibold tabular-nums text-pine">{{ grossOf(p) || '—' }}</td>
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

            <!-- Completed / canceled -->
            <div v-else class="p-6 text-center border rounded-2xl border-parchment-dark bg-cream">
                <h2 class="text-lg font-semibold font-display text-pine">
                    {{ game.status === 'completed' ? 'Game finished' : 'Game canceled' }}
                </h2>
                <ul v-if="game.status === 'completed'" class="mt-4 space-y-1 text-sm text-ink/80">
                    <li v-for="p in game.players" :key="p.user_id" class="capitalize">
                        {{ fullName(p) }} — <span class="font-semibold tabular-nums text-pine">{{ grossOf(p) }}</span>
                    </li>
                </ul>
                <a href="/my-handicap" class="inline-block mt-6 text-sm font-medium text-pine hover:text-brass-dark">Back to My Handicap →</a>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
