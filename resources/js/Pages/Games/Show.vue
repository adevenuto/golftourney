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
const myScore = computed(() => {
    const v = myPlayer.value?.holes?.[currentHole.value];
    return v == null || v === '' ? null : Number(v);
});
const myTotal = computed(() =>
    myPlayer.value
        ? Object.values(myPlayer.value.holes).reduce((t, v) => t + (v == null || v === '' ? 0 : Number(v)), 0)
        : 0,
);

// Start on the first hole I haven't scored yet.
function jumpToFirstUnentered() {
    const i = game.hole_numbers.findIndex((h) => {
        const v = myPlayer.value?.holes?.[h];
        return v == null || v === '';
    });
    if (i > 0) currentIdx.value = i;
}

/* ---------- score writes (optimistic + debounced) ---------- */
const patchTimers = {};
function setScore(value) {
    if (!myPlayer.value) return;
    const hole = currentHole.value;
    myPlayer.value.holes[hole] = value; // optimistic (null = cleared)
    clearTimeout(patchTimers[hole]);
    patchTimers[hole] = setTimeout(() => {
        window.axios
            .patch(route('games.scores.update', game.id), { hole, strokes: value })
            .catch(() => router.reload({ only: ['game'] }));
    }, 200);
}

/* ---------- hole nav + complete ---------- */
const isLast = computed(() => currentIdx.value >= game.hole_numbers.length - 1);
const completeLabel = computed(() => {
    if (!isLast.value) return 'Complete Hole';
    return isOwner.value ? 'Finish & post rounds' : 'Waiting for host…';
});
const completeDisabled = computed(() => isLast.value && !isOwner.value);
function complete() {
    if (!isLast.value) {
        currentIdx.value++;
        return;
    }
    if (isOwner.value) finalize();
}

/* ---------- lifecycle ---------- */
const act = (name) => router.post(route(name, game.id), {}, { preserveScroll: true });
const start = () => act('games.start');
const finalize = () => act('games.finalize');
const abandon = () => act('games.abandon');

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
            if (p) p.holes[e.hole] = e.strokes;
        })
        .listen('.player.joined', (e) => {
            if (!game.players.some((pl) => pl.user_id === e.player.user_id)) game.players.push(e.player);
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

    <div class="min-h-screen bg-pine-deep sm:py-6">
        <div class="mx-auto flex min-h-screen w-full max-w-md flex-col overflow-hidden bg-cream sm:min-h-0 sm:rounded-3xl sm:shadow-2xl">
            <!-- Header -->
            <header class="bg-pine px-5 pt-5 pb-5 text-cream">
                <div class="flex items-center justify-between">
                    <Link :href="route('games.index')" class="inline-flex items-center gap-1 text-sm text-cream/70 transition hover:text-cream" aria-label="Back to games">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                        Games
                    </Link>
                    <p class="max-w-[9rem] truncate text-sm font-medium capitalize text-cream/90">{{ game.course_name }}</p>
                    <button type="button" @click="share" class="inline-flex items-center gap-1 text-sm text-cream/70 transition hover:text-cream" aria-label="Share game">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.7 10.7l6.6-3.4M8.7 13.3l6.6 3.4M18 8a3 3 0 10-3-3 3 3 0 003 3zm0 8a3 3 0 10-3 3 3 3 0 003-3zM6 15a3 3 0 10-3-3 3 3 0 003 3z" /></svg>
                    </button>
                </div>

                <!-- Players -->
                <div class="mt-5 flex flex-wrap items-start justify-center gap-4">
                    <div v-for="p in game.players" :key="p.user_id" class="flex w-16 flex-col items-center gap-1.5 text-center">
                        <PlayerAvatar
                            :first-name="p.first_name"
                            :last-name="p.last_name"
                            :online="isOnline(p.user_id)"
                            :active="p.user_id === meId"
                            ring-color="ring-cream/25"
                        />
                        <span class="w-full truncate text-xs capitalize text-cream/80">{{ p.first_name }}</span>
                    </div>
                </div>

                <!-- Stat band (active) -->
                <div v-if="game.status === 'active'" class="mt-5 grid grid-cols-3 gap-2 rounded-2xl bg-pine-deep/40 py-3 text-center">
                    <div>
                        <p class="font-display text-2xl font-semibold leading-none text-brass-light tabular-nums">{{ currentHole }}</p>
                        <p class="mt-1 text-[10px] uppercase tracking-widest text-cream/50">Hole</p>
                    </div>
                    <div>
                        <p class="font-display text-2xl font-semibold leading-none tabular-nums">{{ parFor(currentHole) ?? '—' }}</p>
                        <p class="mt-1 text-[10px] uppercase tracking-widest text-cream/50">Par</p>
                    </div>
                    <div>
                        <p class="font-display text-2xl font-semibold leading-none tabular-nums">{{ myTotal || '—' }}</p>
                        <p class="mt-1 text-[10px] uppercase tracking-widest text-cream/50">Score</p>
                    </div>
                </div>
            </header>

            <!-- Body -->
            <main class="flex-1 px-5 py-6">
                <!-- Lobby -->
                <div v-if="game.status === 'lobby'" class="text-center">
                    <p class="text-[11px] font-medium uppercase tracking-widest text-ink/40">Invite code</p>
                    <p class="mt-1 font-display text-4xl font-semibold tracking-[0.3em] text-pine">{{ game.join_code }}</p>
                    <button type="button" @click="share" class="mt-3 inline-flex items-center gap-1.5 rounded-full border border-pine/20 px-4 py-1.5 text-sm font-medium text-pine transition hover:border-brass">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2v-2M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3" /></svg>
                        {{ copied ? 'Link copied' : 'Share' }}
                    </button>

                    <div v-if="isOwner" class="mt-8 flex flex-col gap-3">
                        <button type="button" :disabled="!canStart" @click="start" class="w-full rounded-full bg-pine px-6 py-3 text-sm font-semibold text-cream transition hover:bg-pine-light disabled:opacity-50">
                            {{ canStart ? 'Start game' : 'Waiting for players…' }}
                        </button>
                        <button type="button" @click="abandon" class="text-sm font-medium text-ink/50 transition hover:text-ink">Cancel game</button>
                    </div>
                    <p v-else class="mt-8 text-sm text-ink/50">The host will start the game.</p>
                </div>

                <!-- Active -->
                <div v-else-if="game.status === 'active'" class="space-y-6">
                    <HolePad
                        :hole="currentHole"
                        :par="parFor(currentHole)"
                        :score="myScore"
                        :can-prev="currentIdx > 0"
                        :can-next="currentIdx < game.hole_numbers.length - 1"
                        :complete-label="completeLabel"
                        :complete-disabled="completeDisabled"
                        @set="setScore"
                        @prev="currentIdx--"
                        @next="currentIdx++"
                        @complete="complete"
                    />

                    <div>
                        <p class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-pine/60">Scorecard</p>
                        <Scorecard
                            :players="game.players"
                            :hole-numbers="game.hole_numbers"
                            :hole-pars="game.hole_pars"
                            :par="game.par"
                            :me-id="meId"
                            :online-ids="onlineIds"
                            :current-hole="currentHole"
                        />
                    </div>

                    <div v-if="isOwner" class="flex items-center justify-between pt-2 text-sm">
                        <button type="button" @click="abandon" class="font-medium text-red-700/80 transition hover:text-red-800">Cancel game</button>
                        <button type="button" @click="finalize" class="rounded-full border border-pine/20 px-4 py-2 font-medium text-pine transition hover:border-brass">Finish now</button>
                    </div>
                </div>

                <!-- Completed / canceled -->
                <div v-else class="text-center">
                    <h2 class="font-display text-2xl font-semibold text-pine">{{ game.status === 'completed' ? 'Game finished' : 'Game canceled' }}</h2>
                    <p v-if="game.status === 'completed'" class="mt-1 text-sm text-ink/50">Each player's round was posted to their handicap.</p>
                    <ul v-if="game.status === 'completed'" class="mx-auto mt-6 max-w-xs divide-y divide-parchment-dark">
                        <li v-for="p in game.players" :key="p.user_id" class="flex items-center justify-between py-2.5 text-sm capitalize">
                            <span class="inline-flex items-center gap-2">
                                <PlayerAvatar :first-name="p.first_name" :last-name="p.last_name" size="sm" />
                                <span class="text-ink/80">{{ fullName(p) }}</span>
                            </span>
                            <span class="font-display text-lg font-semibold tabular-nums text-pine">{{ Object.values(p.holes).reduce((t, v) => t + (v ? Number(v) : 0), 0) || '—' }}</span>
                        </li>
                    </ul>
                    <Link :href="route('games.index')" class="mt-8 inline-block text-sm font-medium text-pine hover:text-brass-dark">Back to Games →</Link>
                </div>
            </main>
        </div>
        <FlashToast />
    </div>
</template>
