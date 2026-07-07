<script setup>
/*
 * The live game page. Server props are the source of truth on load and after
 * lifecycle actions; Laravel Echo (Pusher) carries live deltas. Own-scores-only
 * writes go through axios (optimistic, silent 204) so peers update via
 * ->toOthers() without the editor reloading.
 */
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Scorecard from '@/Components/Games/Scorecard.vue';
import HoleEntry from '@/Components/Games/HoleEntry.vue';

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

/* ---------- presence (who's live) ---------- */
const online = reactive(new Set());
const onlineIds = computed(() => [...online]);
const isOnline = (id) => online.has(id);

/* ---------- view + share ---------- */
const view = ref('mine'); // 'mine' (stepper) | 'card' (grid)
const copied = ref(false);
function copyLink() {
    navigator.clipboard?.writeText(window.location.href);
    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
}

/* ---------- score entry (own row only, optimistic) ---------- */
function saveHole(hole) {
    if (!myPlayer.value) return;
    const raw = myPlayer.value.holes[hole];
    const strokes = raw === '' || raw == null ? null : Number(raw);
    window.axios
        .patch(route('games.scores.update', game.id), { hole, strokes })
        .catch(() => router.reload({ only: ['game'] }));
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
                    {{ game.teebox ? game.teebox + ' tee · ' : '' }}{{ game.holes }} holes
                </p>
            </template>
        </PageHeader>

        <div class="max-w-5xl px-4 py-8 mx-auto sm:px-6 lg:px-8">
            <!-- Lobby -->
            <div v-if="game.status === 'lobby'" class="p-6 border rounded-2xl border-parchment-dark bg-cream sm:p-8">
                <p class="text-xs font-medium tracking-widest text-center uppercase text-ink/40">Invite code</p>
                <p class="mt-1 font-mono text-4xl font-semibold tracking-[0.3em] text-center text-pine">{{ game.join_code }}</p>
                <div class="flex justify-center mt-3">
                    <button
                        type="button"
                        @click="copyLink"
                        class="inline-flex items-center gap-1.5 rounded-full border border-pine/20 px-4 py-1.5 text-sm font-medium text-pine transition hover:border-brass"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2v-2M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3" /></svg>
                        {{ copied ? 'Link copied' : 'Copy share link' }}
                    </button>
                </div>

                <ul class="max-w-sm mx-auto mt-6 space-y-2">
                    <li v-for="p in game.players" :key="p.user_id" class="flex items-center gap-2 text-sm capitalize text-ink/80">
                        <span class="h-1.5 w-1.5 rounded-full" :class="isOnline(p.user_id) ? 'bg-pine' : 'bg-ink/20'"></span>
                        {{ fullName(p) }}<span v-if="p.is_owner" class="text-ink/40"> · host</span>
                    </li>
                </ul>

                <div v-if="isOwner" class="flex justify-center gap-3 mt-8">
                    <button
                        type="button"
                        :disabled="!canStart"
                        @click="start"
                        class="px-6 py-2.5 text-sm font-medium transition rounded-full bg-pine text-cream hover:bg-pine-light disabled:opacity-50"
                    >
                        {{ canStart ? 'Start game' : 'Waiting for players…' }}
                    </button>
                    <button type="button" @click="abandon" class="px-4 py-2.5 text-sm font-medium transition rounded-full text-ink/60 hover:text-ink">Cancel</button>
                </div>
                <p v-else class="mt-8 text-sm text-center text-ink/50">The host will start the game.</p>
            </div>

            <!-- Active -->
            <div v-else-if="game.status === 'active'">
                <!-- View toggle -->
                <div class="flex justify-center mb-4">
                    <div class="inline-flex p-1 rounded-full bg-pine/10">
                        <button
                            type="button"
                            @click="view = 'mine'"
                            class="rounded-full px-4 py-1.5 text-sm font-medium transition"
                            :class="view === 'mine' ? 'bg-pine text-cream' : 'text-pine'"
                        >My card</button>
                        <button
                            type="button"
                            @click="view = 'card'"
                            class="rounded-full px-4 py-1.5 text-sm font-medium transition"
                            :class="view === 'card' ? 'bg-pine text-cream' : 'text-pine'"
                        >Full card</button>
                    </div>
                </div>

                <HoleEntry
                    v-if="view === 'mine' && myPlayer"
                    :hole-numbers="game.hole_numbers"
                    :my-holes="myPlayer.holes"
                    :par="game.par"
                    @save="saveHole"
                />
                <Scorecard
                    v-else
                    :players="game.players"
                    :hole-numbers="game.hole_numbers"
                    :par="game.par"
                    :me-id="meId"
                    :online-ids="onlineIds"
                />

                <div v-if="isOwner" class="flex justify-center gap-3 mt-6">
                    <button type="button" @click="finalize" class="px-6 py-2.5 text-sm font-medium transition rounded-full bg-pine text-cream hover:bg-pine-light">
                        Finish &amp; post rounds
                    </button>
                    <button type="button" @click="abandon" class="px-4 py-2.5 text-sm font-medium transition rounded-full text-red-700 hover:text-red-800">Cancel game</button>
                </div>
            </div>

            <!-- Completed / canceled -->
            <div v-else class="p-8 text-center border rounded-2xl border-parchment-dark bg-cream">
                <h2 class="text-2xl font-semibold font-display text-pine">
                    {{ game.status === 'completed' ? 'Game finished' : 'Game canceled' }}
                </h2>
                <p v-if="game.status === 'completed'" class="mt-1 text-sm text-ink/50">Each player's round was posted to their handicap.</p>
                <ul v-if="game.status === 'completed'" class="max-w-xs mx-auto mt-6 divide-y divide-parchment-dark">
                    <li v-for="p in game.players" :key="p.user_id" class="flex items-center justify-between py-2 text-sm capitalize">
                        <span class="text-ink/80">{{ fullName(p) }}</span>
                        <span class="font-display text-lg font-semibold tabular-nums text-pine">{{ Object.values(p.holes).reduce((t, v) => t + (v ? Number(v) : 0), 0) || '—' }}</span>
                    </li>
                </ul>
                <a href="/my-handicap" class="inline-block mt-8 text-sm font-medium text-pine hover:text-brass-dark">Back to My Handicap →</a>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
