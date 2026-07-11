<script setup>
/*
 * The live game — an immersive, app-like screen (its own pine/cream shell, no
 * web nav; centered + width-locked on desktop). Server props are the source of
 * truth on load and after lifecycle actions; Laravel Echo (Pusher) carries live
 * deltas. Own-scores-only writes go through axios (optimistic, debounced) so
 * peers update via ->toOthers() without the editor reloading.
 */
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import FlashToast from '@/Components/FlashToast.vue';
import PlayerAvatar from '@/Components/Games/PlayerAvatar.vue';
import HolePad from '@/Components/Games/HolePad.vue';
import Scorecard from '@/Components/Games/Scorecard.vue';

const props = defineProps({ game: { type: Object, required: true } });

const clone = (v) => JSON.parse(JSON.stringify(v));
const game = reactive(clone(props.game));
watch(() => props.game, (g) => Object.assign(game, clone(g)), { deep: true });

const page = usePage();
const meId = computed(() => page.props.auth.user?.id);
const isOwner = computed(() => game.owner_id === meId.value);
const myPlayer = computed(() => game.players.find((p) => p.user_id === meId.value) ?? null);
const canStart = computed(() => game.players.length >= 2);
const fullName = (p) => `${p.first_name} ${p.last_name}`;

// Each player finishes on their own: once I've finished I see the results
// screen while peers keep playing. The game completes when everyone's done.
const iFinished = computed(() => !!myPlayer.value?.finished);
const playing = computed(() => game.status === 'active' && !iFinished.value);
// On a completed game everyone is done, regardless of the per-player flag.
const playerDone = (p) => p.finished || game.status === 'completed';
const grossOf = (p) => Object.values(p.holes || {}).reduce((t, v) => t + (v == null || v === '' ? 0 : Number(v)), 0);

/* ---------- presence ---------- */
const online = reactive(new Set());
const onlineIds = computed(() => [...online]);
const isOnline = (id) => online.has(id);

/* ---------- current hole + my score ---------- */
const currentIdx = ref(0);
const currentHole = computed(() => game.hole_numbers[currentIdx.value]);
const parFor = (h) => {
    const p = game.hole_pars?.[h];
    return p == null ? null : Number(p);
};
const lengthFor = (h) => {
    const l = game.hole_lengths?.[h];
    return l == null || l === '' ? null : Number(l);
};
const numOr = (v) => (v == null || v === '' ? null : Number(v));
const myStrokes = computed(() => numOr(myPlayer.value?.holes?.[currentHole.value]));
const myPutts = computed(() => numOr(myPlayer.value?.putts?.[currentHole.value]));
// My running score relative to par, across the holes I've actually scored.
const toPar = computed(() => {
    let diff = 0;
    let counted = 0;
    for (const h of game.hole_numbers) {
        const s = numOr(myPlayer.value?.holes?.[h]);
        const p = parFor(h);
        if (s != null && p != null) {
            diff += s - p;
            counted++;
        }
    }
    return counted ? diff : null;
});
const toParDisplay = computed(() => {
    if (toPar.value == null) return '—';
    if (toPar.value === 0) return 'E';
    return toPar.value > 0 ? `+${toPar.value}` : `${toPar.value}`;
});

// A hole is "done" once strokes are in — putts follow automatically and aren't
// required to move on.
const currentHoleComplete = computed(() => myStrokes.value != null);

// Start on the first hole I haven't scored yet.
function jumpToFirstUnentered() {
    const i = game.hole_numbers.findIndex((h) => {
        const v = myPlayer.value?.holes?.[h];
        return v == null || v === '';
    });
    if (i > 0) currentIdx.value = i;
}

/* ---------- score writes (optimistic + debounced; sends strokes + putts) ---------- */
const patchTimers = {};
function writeCell(hole) {
    clearTimeout(patchTimers[hole]);
    patchTimers[hole] = setTimeout(() => {
        window.axios
            .patch(route('games.scores.update', game.id), {
                hole,
                strokes: numOr(myPlayer.value?.holes?.[hole]),
                putts: numOr(myPlayer.value?.putts?.[hole]),
            })
            .catch(() => router.reload({ only: ['game'] }));
    }, 200);
}
function setStrokes(value) {
    if (!myPlayer.value) return;
    myPlayer.value.holes[currentHole.value] = value;
    syncPutts(value); // keep putts consistent with the new stroke count
    writeCell(currentHole.value);
}
function setPutts(value) {
    if (!myPlayer.value) return;
    if (!myPlayer.value.putts) myPlayer.value.putts = {};
    myPlayer.value.putts[currentHole.value] = value;
    writeCell(currentHole.value);
}

// Putts can never exceed strokes - 1 (the tee shot is never a putt). Given a
// score, assume greens-in-regulation and infer the putts: par-3 birdie ⇒ 1,
// a par ⇒ 2, etc. — capped at a realistic 2 for over-regulation scores.
function expectedPutts(strokes, par) {
    if (strokes == null || strokes <= 1) return 0;
    const cap = Math.min(strokes - 1, 2);
    if (!par) return cap; // no par data — default to a sensible 2 (capped)
    return Math.max(0, Math.min(strokes - (par - 2), cap));
}
// After strokes change: auto-fill putts if unset, or clamp if now out of range.
function syncPutts(strokes) {
    if (!myPlayer.value || strokes == null) return;
    if (!myPlayer.value.putts) myPlayer.value.putts = {};
    const hole = currentHole.value;
    const current = numOr(myPlayer.value.putts[hole]);
    const cap = Math.max(0, strokes - 1);
    if (current == null) {
        myPlayer.value.putts[hole] = expectedPutts(strokes, parFor(hole));
    } else if (current > cap) {
        myPlayer.value.putts[hole] = cap;
    }
}

/* ---------- lifecycle ---------- */
const act = (name) => router.post(route(name, game.id), {}, { preserveScroll: true });
const start = () => act('games.start');
const finish = () => act('games.finish'); // finish my own round
const reopen = () => act('games.reopen'); // go back and edit my card
const finalize = () => act('games.finalize'); // owner: end for everyone (fallback)
const abandon = () => act('games.abandon'); // owner: cancel the whole game
const leave = () => act('games.leave'); // non-host: leave before it starts

/* ---------- bottom nav (Next Hole morphs into Finish on the last hole) ---------- */
const isLastHole = computed(() => currentIdx.value >= game.hole_numbers.length - 1);
const primaryLabel = computed(() => (isLastHole.value ? 'Finish & post round' : 'Next Hole'));
// Can't advance (or finish) until the current hole's strokes + putts are entered.
const primaryDisabled = computed(() => !currentHoleComplete.value);
function primaryAction() {
    if (!isLastHole.value) {
        currentIdx.value++;
        return;
    }
    finish();
}

/* ---------- ui ---------- */
const scorecardOpen = ref(false);
const confirmCancel = ref(false);
function doCancel() {
    confirmCancel.value = false;
    abandon();
}
const ringFor = (userId) => (userId === meId.value ? 'ring-[#43a06a]' : 'ring-brass');

/* ---------- share ---------- */
const copied = ref(false);
async function share() {
    const url = window.location.href;
    if (navigator.share) {
        try {
            await navigator.share({ title: 'Join my GolfTourney game', text: `Join with code ${game.join_code}`, url });
            return;
        } catch (e) {
            if (e?.name === 'AbortError') return; // user dismissed the share sheet
            // otherwise fall through to clipboard
        }
    }
    navigator.clipboard?.writeText(url);
    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
}

/* ---------- realtime ---------- */
let hadConnection = false;
onMounted(() => {
    jumpToFirstUnentered();
    if (!window.Echo) return;

    window.Echo.join(`game.${game.id}`)
        .here((users) => { online.clear(); users.forEach((u) => online.add(u.id)); })
        .joining((u) => online.add(u.id))
        .leaving((u) => online.delete(u.id))
        .listen('.score.updated', (e) => {
            const p = game.players.find((pl) => pl.user_id === e.userId);
            if (p) {
                p.holes[e.hole] = e.strokes;
                if (!p.putts) p.putts = {};
                p.putts[e.hole] = e.putts;
            }
        })
        .listen('.player.joined', (e) => {
            if (!game.players.some((pl) => pl.user_id === e.player.user_id)) game.players.push(e.player);
        })
        .listen('.player.left', (e) => {
            const i = game.players.findIndex((pl) => pl.user_id === e.userId);
            if (i !== -1) game.players.splice(i, 1);
        })
        .listen('.player.finished', (e) => {
            const p = game.players.find((pl) => pl.user_id === e.userId);
            if (p) p.finished = true;
        })
        .listen('.player.reopened', (e) => {
            const p = game.players.find((pl) => pl.user_id === e.userId);
            if (p) p.finished = false;
        })
        .listen('.game.started', () => router.reload({ only: ['game'] }))
        .listen('.game.completed', () => router.reload({ only: ['game'] }));

    const pusher = window.Echo.connector?.pusher;
    pusher?.connection.bind('connected', () => {
        if (hadConnection) router.reload({ only: ['game'] });
        hadConnection = true;
    });
});
onBeforeUnmount(() => { if (window.Echo) window.Echo.leave(`game.${game.id}`); });
</script>

<template>
    <Head title="Live game" />

    <div class="bg-pine-deep sm:min-h-screen sm:py-6">
        <div class="mx-auto flex h-[100dvh] w-full max-w-md flex-col overflow-hidden bg-cream sm:h-[calc(100dvh-3rem)] sm:max-h-[900px] sm:rounded-3xl sm:shadow-2xl">
            <!-- Sticky header -->
            <header
                class="shrink-0 rounded-b-[2rem] px-5 pt-5 pb-6 text-cream"
                style="background: radial-gradient(120% 90% at 50% -12%, #1f6146 0%, #14432f 48%, #0d2e20 100%)"
            >
                <div class="flex items-center justify-between">
                    <Link :href="route('games.index')" class="inline-flex items-center gap-1 text-sm font-medium text-cream/80 transition hover:text-cream" aria-label="Back to games">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                        Games
                    </Link>
                    <div class="flex items-center gap-3.5">
                        <!-- Host: throw in the towel (white flag) → fills solid on hover, confirm first. Everyone else can share. -->
                        <button v-if="isOwner && game.status !== 'completed'" type="button" @click="confirmCancel = true" class="group text-cream/75 transition hover:text-cream" aria-label="Cancel game">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path class="fill-transparent transition-colors group-hover:fill-cream" stroke-linecap="round" stroke-linejoin="round" d="M4 5h13l-2.5 4L17 14H4z" />
                                <path stroke-linecap="round" d="M4 21V4" />
                            </svg>
                        </button>
                        <button v-else-if="isOwner" type="button" @click="share" class="text-cream/80 transition hover:text-cream" aria-label="Share game">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.7 10.7l6.6-3.4M8.7 13.3l6.6 3.4M18 8a3 3 0 10-3-3 3 3 0 003 3zm0 8a3 3 0 10-3 3 3 3 0 003-3zM6 15a3 3 0 10-3-3 3 3 0 003 3z" /></svg>
                        </button>
                        <!-- Scorecard — larger + more prominent (easy phone tap target) -->
                        <button v-if="game.status !== 'lobby'" type="button" @click="scorecardOpen = true" class="-m-1 p-1 text-cream transition hover:text-brass-light" aria-label="Open scorecard">
                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9"><rect x="3" y="4" width="18" height="16" rx="2" /><path stroke-linecap="round" d="M3 9h18M9 9v11M15 9v11" /></svg>
                        </button>
                    </div>
                </div>

                <!-- Course name -->
                <div class="mt-4 text-center">
                    <p class="truncate text-base font-semibold capitalize">{{ game.course_name }}</p>
                    <p v-if="game.course_sub" class="truncate text-xs capitalize text-cream/50">{{ game.course_sub }}</p>
                </div>

                <!-- Players -->
                <div class="mt-4 flex flex-wrap items-start justify-center gap-6">
                    <div v-for="p in game.players" :key="p.user_id" class="flex w-16 flex-col items-center gap-2 text-center">
                        <PlayerAvatar :first-name="p.first_name" :last-name="p.last_name" size="lg" :online="isOnline(p.user_id)" :ring-color="ringFor(p.user_id)" />
                        <span class="w-full truncate text-[13px] font-medium capitalize text-cream/90">{{ p.first_name }}</span>
                    </div>
                </div>

                <!-- Stat band (while I'm still playing) -->
                <div v-if="playing" class="mt-6 grid grid-cols-3 items-center rounded-2xl bg-[#0b241a]/70 px-3 py-4 text-center">
                    <div>
                        <p class="font-display text-3xl font-semibold leading-none tabular-nums text-brass-light">{{ toParDisplay }}</p>
                        <p class="mt-1.5 text-[10px] font-medium uppercase tracking-widest text-cream/45">To Par</p>
                    </div>
                    <div>
                        <p class="mb-1 text-xs font-semibold uppercase tracking-widest text-cream/85">Hole</p>
                        <p class="font-display text-6xl font-semibold leading-none tabular-nums">{{ currentHole }}</p>
                        <p class="mt-1.5 text-[11px] font-semibold uppercase tracking-widest text-cream/60">Par {{ parFor(currentHole) ?? '—' }}</p>
                        <p v-if="lengthFor(currentHole)" class="mt-0.5 text-[10px] font-medium tabular-nums text-cream/40">{{ lengthFor(currentHole) }} yds</p>
                    </div>
                    <div>
                        <p class="font-display text-3xl font-semibold leading-none tabular-nums">{{ myStrokes ?? '—' }}</p>
                        <p class="mt-1.5 text-[10px] font-medium uppercase tracking-widest text-cream/45">Score</p>
                    </div>
                </div>
            </header>

            <!-- Scrollable content -->
            <main class="flex-1 overflow-y-auto overscroll-contain">
                <!-- Lobby -->
                <div v-if="game.status === 'lobby'" class="px-5 py-8 text-center">
                    <p class="text-[11px] font-medium uppercase tracking-widest text-ink/40">Invite code</p>
                    <p class="mt-1 font-display text-4xl font-semibold tracking-[0.3em] text-pine">{{ game.join_code }}</p>
                    <button type="button" @click="share" class="mt-3 inline-flex items-center gap-1.5 rounded-full border border-pine/20 px-4 py-1.5 text-sm font-medium text-pine transition hover:border-brass">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2v-2M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3" /></svg>
                        {{ copied ? 'Link copied' : 'Share' }}
                    </button>

                    <!-- Live roster — players appear here by name as they join -->
                    <div class="mx-auto mt-8 max-w-xs">
                        <p class="text-[11px] font-medium uppercase tracking-widest text-ink/40">
                            {{ game.players.length }} {{ game.players.length === 1 ? 'player' : 'players' }}
                        </p>
                        <ul class="mt-3 space-y-2">
                            <li v-for="p in game.players" :key="p.user_id" class="flex items-center gap-3 rounded-xl border border-parchment-dark bg-parchment/40 px-3 py-2 text-left">
                                <PlayerAvatar :first-name="p.first_name" :last-name="p.last_name" size="sm" :online="isOnline(p.user_id)" :ring-color="ringFor(p.user_id)" />
                                <span class="min-w-0 flex-1 truncate text-sm font-medium capitalize text-ink">{{ fullName(p) }}</span>
                                <span v-if="p.is_owner" class="shrink-0 text-[10px] font-semibold uppercase tracking-wide text-brass-dark">Host</span>
                            </li>
                        </ul>
                    </div>

                    <div v-if="isOwner" class="mt-8 flex flex-col gap-3">
                        <!-- Solo escape hatch while waiting on others -->
                        <button v-if="!canStart" type="button" @click="start" class="w-full rounded-full bg-pine px-6 py-3 text-sm font-semibold text-cream transition hover:bg-pine-light active:scale-[0.99]">
                            Play solo
                        </button>
                        <!-- Start (enabled once someone joins) / waiting indicator -->
                        <button
                            type="button"
                            :disabled="!canStart"
                            @click="start"
                            class="w-full rounded-full px-6 py-3 text-sm font-semibold transition disabled:cursor-default"
                            :class="canStart ? 'bg-pine text-cream hover:bg-pine-light' : 'border border-pine/20 text-pine/50'"
                        >
                            {{ canStart ? 'Start game' : 'Waiting for players…' }}
                        </button>
                    </div>
                    <div v-else class="mt-8 flex flex-col gap-2">
                        <p class="text-sm text-ink/50">The host will start the game.</p>
                        <button type="button" @click="leave" class="text-sm font-medium text-ink/50 transition hover:text-red-700">Leave game</button>
                    </div>
                </div>

                <!-- Active (still playing) -->
                <template v-else-if="playing">
                    <div class="px-5 py-6">
                        <HolePad
                            :hole="currentHole"
                            :par="parFor(currentHole)"
                            :strokes="myStrokes"
                            :putts="myPutts"
                            @set-strokes="setStrokes"
                            @set-putts="setPutts"
                        />
                    </div>
                </template>

                <!-- Results — I've finished (or the whole game is complete) -->
                <div v-else class="px-5 py-8">
                    <div class="text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-pine/10 text-pine">
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        </div>
                        <h2 class="mt-4 font-display text-2xl font-semibold text-pine">{{ game.status === 'completed' ? 'Game finished' : 'You’re all done' }}</h2>
                        <p class="mt-1 text-sm text-ink/50">
                            {{ game.status === 'completed'
                                ? 'Every round was posted to each player’s handicap.'
                                : 'Waiting for the others to finish. You can still reopen your card to fix a hole.' }}
                        </p>
                    </div>

                    <ul class="mx-auto mt-6 max-w-xs divide-y divide-parchment-dark">
                        <li v-for="p in game.players" :key="p.user_id" class="flex items-center justify-between gap-3 py-3">
                            <span class="inline-flex min-w-0 items-center gap-2.5">
                                <PlayerAvatar :first-name="p.first_name" :last-name="p.last_name" size="sm" :online="isOnline(p.user_id)" :ring-color="ringFor(p.user_id)" />
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-medium capitalize text-ink">{{ fullName(p) }}</span>
                                    <span class="text-[11px]" :class="playerDone(p) ? 'font-medium text-pine/70' : 'text-ink/40'">
                                        {{ playerDone(p) ? 'Finished' : 'In-round' }}
                                    </span>
                                </span>
                            </span>
                            <span class="shrink-0 font-display text-lg font-semibold tabular-nums text-pine">{{ grossOf(p) || '—' }}</span>
                        </li>
                    </ul>

                    <div class="mt-8 flex flex-col items-center gap-3">
                        <button
                            v-if="game.status === 'active'"
                            type="button"
                            @click="reopen"
                            class="inline-flex items-center gap-1.5 rounded-full bg-pine px-6 py-2.5 text-sm font-semibold text-cream transition hover:bg-pine-light active:scale-[0.99]"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                            Resume my card
                        </button>
                        <button
                            v-if="isOwner && game.status !== 'completed'"
                            type="button"
                            @click="finalize"
                            class="text-sm font-medium text-ink/50 transition hover:text-ink"
                        >
                            End game for everyone
                        </button>
                        <Link :href="route('games.index')" class="text-sm font-medium text-pine/70 transition hover:text-brass-dark">Back to Games →</Link>
                    </div>
                </div>
            </main>

            <!-- Sticky bottom nav (while I'm still playing) -->
            <footer
                v-if="playing"
                class="shrink-0 border-t border-parchment-dark bg-cream px-5 py-4"
                style="box-shadow: 0 -8px 20px -16px rgba(0, 0, 0, 0.3)"
            >
                <p v-if="!currentHoleComplete" class="mb-2.5 text-center text-xs text-ink/45">Enter your strokes to continue.</p>
                <div class="flex gap-3">
                    <button
                        type="button"
                        :disabled="currentIdx === 0"
                        @click="currentIdx--"
                        class="flex-[1] inline-flex items-center justify-center gap-1 rounded-full border border-pine/25 py-3.5 text-sm font-semibold text-pine transition hover:border-brass hover:text-brass-dark disabled:opacity-30"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                        Back
                    </button>
                    <button
                        type="button"
                        :disabled="primaryDisabled"
                        @click="primaryAction"
                        class="flex-[3] inline-flex items-center justify-center gap-1.5 rounded-full bg-pine py-3.5 text-sm font-semibold text-cream transition hover:bg-pine-light active:scale-[0.99] disabled:opacity-50"
                    >
                        {{ primaryLabel }}
                        <svg v-if="!isLastHole" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    </button>
                </div>
            </footer>
        </div>

        <!-- Scorecard modal -->
        <Modal :show="scorecardOpen" @close="scorecardOpen = false" max-width="2xl">
            <div class="overflow-hidden rounded-lg bg-cream">
                <div class="flex items-center justify-between border-b border-parchment-dark px-4 py-4">
                    <h2 class="font-display text-lg font-semibold text-pine">Scorecard</h2>
                    <button type="button" @click="scorecardOpen = false" class="text-ink/40 transition hover:text-ink" aria-label="Close">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" /></svg>
                    </button>
                </div>
                <Scorecard
                    :players="game.players"
                    :hole-numbers="game.hole_numbers"
                    :hole-pars="game.hole_pars"
                    :hole-lengths="game.hole_lengths"
                    :par="game.par"
                    :me-id="meId"
                    :online-ids="onlineIds"
                    :current-hole="currentHole"
                />
            </div>
        </Modal>

        <!-- Confirm cancel (host) -->
        <Modal :show="confirmCancel" @close="confirmCancel = false" max-width="sm">
            <div class="overflow-hidden rounded-lg bg-cream p-6 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 text-red-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 21V4m0 1h13l-2.5 4L17 14H4" /></svg>
                </div>
                <h2 class="mt-4 font-display text-xl font-semibold text-pine">Call it off?</h2>
                <p class="mt-1.5 text-sm text-ink/60">This ends the game for everyone and discards all scores — no rounds will be posted. This can’t be undone.</p>
                <div class="mt-6 flex gap-3">
                    <button type="button" @click="confirmCancel = false" class="flex-1 rounded-full border border-pine/20 py-2.5 text-sm font-semibold text-pine transition hover:border-brass">Keep playing</button>
                    <button type="button" @click="doCancel" class="flex-1 rounded-full bg-red-600 py-2.5 text-sm font-semibold text-cream transition hover:bg-red-700">Cancel game</button>
                </div>
            </div>
        </Modal>

        <FlashToast />
    </div>
</template>
