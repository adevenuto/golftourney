<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    hole: { type: Number, required: true },
    par: { type: Number, default: null },
    strokes: { type: Number, default: null },
    putts: { type: Number, default: null },
    canPrev: { type: Boolean, default: false },
    canNext: { type: Boolean, default: false },
});
const emit = defineEmits(['set-strokes', 'set-putts', 'prev', 'next']);

const strokesExpanded = ref(false);
const puttsExpanded = ref(false);

// Par-centered stroke tiles (par-2 … par+2), clamped ≥1; default centre 4.
const base = computed(() => props.par || 4);
const strokeTiles = computed(() => {
    const start = Math.max(1, base.value - 2);
    return [0, 1, 2, 3, 4].map((i) => start + i);
});
const puttTiles = [0, 1, 2, 3, 4];
const allStrokes = Array.from({ length: 15 }, (_, i) => i + 1);
const allPutts = Array.from({ length: 9 }, (_, i) => i);

const neutral = 'bg-[#eae7df] text-pine hover:bg-[#e3ded1]';
const parAccent = 'bg-[#f3f0e9] text-pine ring-1 ring-inset ring-pine/10 hover:bg-[#ece9e0]';
const picked = 'bg-pine text-cream';
const strokeCls = (v) => {
    if (v === props.strokes) return picked;
    if (props.par && v === props.par) return parAccent; // subtle par highlight
    return neutral;
};
const puttCls = (v) => (v === props.putts ? picked : neutral);
</script>

<template>
    <div class="space-y-6">
        <!-- Strokes -->
        <div>
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-pine">Strokes</h3>
                <button v-if="strokes != null" type="button" @click="emit('set-strokes', null)" class="text-xs font-medium text-ink/40 transition hover:text-red-700">Clear</button>
            </div>

            <div v-if="!strokesExpanded" class="mt-3 grid grid-cols-3 gap-3">
                <button
                    v-for="v in strokeTiles"
                    :key="v"
                    type="button"
                    @click="emit('set-strokes', v)"
                    :aria-pressed="v === strokes"
                    class="flex h-20 flex-col items-center justify-center rounded-2xl text-2xl font-semibold tabular-nums transition active:scale-95 focus:outline-none focus:ring-2 focus:ring-brass"
                    :class="strokeCls(v)"
                >
                    {{ v }}
                    <span v-if="par && v === par" class="mt-0.5 text-[10px] font-medium uppercase tracking-wider" :class="v === strokes ? 'text-cream/60' : 'text-pine/40'">Par</span>
                </button>
                <button type="button" @click="strokesExpanded = true" class="flex h-20 items-center justify-center rounded-2xl text-2xl text-pine/50 transition active:scale-95 focus:outline-none focus:ring-2 focus:ring-brass" :class="neutral" aria-label="More strokes">…</button>
            </div>
            <div v-else class="mt-3">
                <div class="grid grid-cols-5 gap-2">
                    <button v-for="v in allStrokes" :key="v" type="button" @click="emit('set-strokes', v); strokesExpanded = false" class="flex h-14 items-center justify-center rounded-xl text-lg font-semibold tabular-nums transition active:scale-95 focus:outline-none focus:ring-2 focus:ring-brass" :class="strokeCls(v)">{{ v }}</button>
                </div>
                <button type="button" @click="strokesExpanded = false" class="mt-2 w-full text-xs font-medium text-pine/60 hover:text-pine">Back</button>
            </div>
        </div>

        <!-- Putts -->
        <div>
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-pine">Putts</h3>
                <button v-if="putts != null" type="button" @click="emit('set-putts', null)" class="text-xs font-medium text-ink/40 transition hover:text-red-700">Clear</button>
            </div>

            <div v-if="!puttsExpanded" class="mt-3 grid grid-cols-3 gap-3">
                <button
                    v-for="v in puttTiles"
                    :key="v"
                    type="button"
                    @click="emit('set-putts', v)"
                    :aria-pressed="v === putts"
                    class="flex h-20 items-center justify-center rounded-2xl text-2xl font-semibold tabular-nums transition active:scale-95 focus:outline-none focus:ring-2 focus:ring-brass"
                    :class="puttCls(v)"
                >{{ v }}</button>
                <button type="button" @click="puttsExpanded = true" class="flex h-20 items-center justify-center rounded-2xl text-2xl text-pine/50 transition active:scale-95 focus:outline-none focus:ring-2 focus:ring-brass" :class="neutral" aria-label="More putts">…</button>
            </div>
            <div v-else class="mt-3">
                <div class="grid grid-cols-5 gap-2">
                    <button v-for="v in allPutts" :key="v" type="button" @click="emit('set-putts', v); puttsExpanded = false" class="flex h-12 items-center justify-center rounded-xl text-base font-semibold tabular-nums transition active:scale-95 focus:outline-none focus:ring-2 focus:ring-brass" :class="puttCls(v)">{{ v }}</button>
                </div>
                <button type="button" @click="puttsExpanded = false" class="mt-2 w-full text-xs font-medium text-pine/60 hover:text-pine">Back</button>
            </div>
        </div>

        <!-- Hole navigation -->
        <div class="flex items-center justify-between pt-1">
            <button
                type="button"
                :disabled="!canPrev"
                @click="emit('prev')"
                class="inline-flex items-center gap-1 px-2 py-2 text-sm font-semibold text-pine transition hover:text-brass-dark disabled:opacity-30"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                Back
            </button>
            <button
                type="button"
                :disabled="!canNext"
                @click="emit('next')"
                class="inline-flex items-center gap-1.5 rounded-full bg-pine px-9 py-3.5 text-sm font-semibold text-cream transition hover:bg-pine-light active:scale-[0.98] disabled:opacity-40"
            >
                Next Hole
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
            </button>
        </div>
    </div>
</template>
