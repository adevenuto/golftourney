<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    hole: { type: Number, required: true },
    par: { type: Number, default: null }, // null = unknown (no coloring)
    score: { type: Number, default: null }, // my strokes for this hole
    canPrev: { type: Boolean, default: false },
    canNext: { type: Boolean, default: false },
    completeLabel: { type: String, default: 'Complete Hole' },
    completeDisabled: { type: Boolean, default: false },
});
const emit = defineEmits(['set', 'prev', 'next', 'complete']);

const expanded = ref(false);

// Par-centered tiles (par-2 … par+2), clamped to ≥1; default center 4 when par unknown.
const base = computed(() => props.par || 4);
const tiles = computed(() => {
    const start = Math.max(1, base.value - 2);
    return [0, 1, 2, 3, 4].map((i) => start + i);
});
const allTiles = Array.from({ length: 15 }, (_, i) => i + 1);

function pick(value) {
    emit('set', value); // explicit set; clearing is a separate action
    expanded.value = false;
}

// Result-based colour for the *selected* tile (golf convention); neutral otherwise.
function tileClass(value) {
    const selected = value === props.score;
    if (!selected) return 'border-pine/15 bg-parchment/60 text-ink hover:border-brass';
    if (!props.par) return 'border-transparent bg-pine text-cream';
    const d = value - props.par;
    if (d <= -2) return 'border-transparent bg-emerald-600 text-white';
    if (d === -1) return 'border-transparent bg-emerald-500 text-white';
    if (d === 0) return 'border-transparent bg-brass text-white';
    if (d === 1) return 'border-transparent bg-amber-500 text-white';
    return 'border-transparent bg-red-600 text-white';
}
</script>

<template>
    <div>
        <!-- Hole nav + entry heading -->
        <div class="flex items-center justify-between">
            <button
                type="button"
                :disabled="!canPrev"
                @click="emit('prev')"
                class="inline-flex h-9 w-9 items-center justify-center rounded-full text-pine transition hover:bg-pine/10 disabled:opacity-25"
                aria-label="Previous hole"
            >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            </button>
            <p class="text-lg font-semibold font-display text-pine">Enter strokes</p>
            <button
                type="button"
                :disabled="!canNext"
                @click="emit('next')"
                class="inline-flex h-9 w-9 items-center justify-center rounded-full text-pine transition hover:bg-pine/10 disabled:opacity-25"
                aria-label="Next hole"
            >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
            </button>
        </div>

        <!-- Par-centered pad -->
        <div v-if="!expanded" class="grid grid-cols-3 gap-2.5 mt-4">
            <button
                v-for="v in tiles"
                :key="v"
                type="button"
                @click="pick(v)"
                :aria-pressed="v === score"
                class="flex aspect-square flex-col items-center justify-center rounded-2xl border text-2xl font-semibold tabular-nums transition focus:outline-none focus:ring-2 focus:ring-brass"
                :class="tileClass(v)"
            >
                {{ v }}
                <span v-if="par && v === par" class="mt-0.5 text-[10px] font-medium uppercase tracking-wider opacity-70">Par</span>
            </button>
            <button
                type="button"
                @click="expanded = true"
                class="flex aspect-square items-center justify-center rounded-2xl border border-pine/15 bg-parchment/60 text-2xl text-pine transition hover:border-brass focus:outline-none focus:ring-2 focus:ring-brass"
                aria-label="More scores"
            >
                …
            </button>
        </div>

        <!-- Expanded pad (rare high/low scores) -->
        <div v-else class="mt-4">
            <div class="grid grid-cols-5 gap-2">
                <button
                    v-for="v in allTiles"
                    :key="v"
                    type="button"
                    @click="pick(v)"
                    :aria-pressed="v === score"
                    class="flex aspect-square items-center justify-center rounded-xl border text-lg font-semibold tabular-nums transition focus:outline-none focus:ring-2 focus:ring-brass"
                    :class="tileClass(v)"
                >{{ v }}</button>
            </div>
            <button type="button" @click="expanded = false" class="mt-2 w-full text-xs font-medium text-pine/60 hover:text-pine">Back</button>
        </div>

        <!-- Clear (only when a score is set) -->
        <div class="mt-2 h-4 text-right">
            <button v-if="score != null" type="button" @click="emit('set', null)" class="text-xs font-medium text-ink/40 transition hover:text-red-700">Clear score</button>
        </div>

        <!-- Complete Hole -->
        <button
            type="button"
            :disabled="completeDisabled"
            @click="emit('complete')"
            class="mt-5 w-full rounded-full bg-pine px-6 py-3.5 text-sm font-semibold text-cream transition hover:bg-pine-light disabled:opacity-40"
        >
            {{ completeLabel }}
        </button>
    </div>
</template>
