<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    hole: { type: Number, required: true },
    par: { type: Number, default: null }, // null = unknown (no colouring)
    strokes: { type: Number, default: null }, // my strokes this hole
    putts: { type: Number, default: null }, // my putts this hole
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
const allTiles = Array.from({ length: 15 }, (_, i) => i + 1);
const allPutts = Array.from({ length: 9 }, (_, i) => i);

// Result-based colour for the *selected* stroke tile; neutral otherwise.
function strokeClass(value) {
    const selected = value === props.strokes;
    if (!selected) return 'border-pine/15 bg-parchment/60 text-ink hover:border-brass';
    if (!props.par) return 'border-transparent bg-pine text-cream';
    const d = value - props.par;
    if (d <= -2) return 'border-transparent bg-emerald-600 text-white';
    if (d === -1) return 'border-transparent bg-emerald-500 text-white';
    if (d === 0) return 'border-transparent bg-brass text-white';
    if (d === 1) return 'border-transparent bg-amber-500 text-white';
    return 'border-transparent bg-red-600 text-white';
}
function puttClass(value) {
    return value === props.putts
        ? 'border-transparent bg-pine text-cream'
        : 'border-pine/15 bg-parchment/60 text-ink hover:border-brass';
}
</script>

<template>
    <div class="space-y-4">
        <!-- Strokes -->
        <div>
            <div class="flex items-center justify-between">
                <p class="text-sm font-semibold text-pine">Strokes</p>
                <button v-if="strokes != null" type="button" @click="emit('set-strokes', null)" class="text-xs font-medium text-ink/40 transition hover:text-red-700">Clear</button>
            </div>

            <div v-if="!strokesExpanded" class="mt-2 grid grid-cols-3 gap-2">
                <button
                    v-for="v in strokeTiles"
                    :key="v"
                    type="button"
                    @click="emit('set-strokes', v)"
                    :aria-pressed="v === strokes"
                    class="flex h-14 flex-col items-center justify-center rounded-xl border text-xl font-semibold tabular-nums transition focus:outline-none focus:ring-2 focus:ring-brass"
                    :class="strokeClass(v)"
                >
                    {{ v }}
                    <span v-if="par && v === par" class="text-[9px] font-medium uppercase tracking-wider opacity-70">Par</span>
                </button>
                <button type="button" @click="strokesExpanded = true" class="flex h-14 items-center justify-center rounded-xl border border-pine/15 bg-parchment/60 text-xl text-pine transition hover:border-brass focus:outline-none focus:ring-2 focus:ring-brass" aria-label="More strokes">…</button>
            </div>
            <div v-else class="mt-2">
                <div class="grid grid-cols-5 gap-2">
                    <button v-for="v in allTiles" :key="v" type="button" @click="emit('set-strokes', v); strokesExpanded = false" class="flex h-12 items-center justify-center rounded-lg border text-lg font-semibold tabular-nums transition focus:outline-none focus:ring-2 focus:ring-brass" :class="strokeClass(v)">{{ v }}</button>
                </div>
                <button type="button" @click="strokesExpanded = false" class="mt-2 w-full text-xs font-medium text-pine/60 hover:text-pine">Back</button>
            </div>
        </div>

        <!-- Putts -->
        <div>
            <div class="flex items-center justify-between">
                <p class="text-sm font-semibold text-pine">Putts</p>
                <button v-if="putts != null" type="button" @click="emit('set-putts', null)" class="text-xs font-medium text-ink/40 transition hover:text-red-700">Clear</button>
            </div>

            <div v-if="!puttsExpanded" class="mt-2 grid grid-cols-3 gap-2">
                <button
                    v-for="v in puttTiles"
                    :key="v"
                    type="button"
                    @click="emit('set-putts', v)"
                    :aria-pressed="v === putts"
                    class="flex h-12 items-center justify-center rounded-xl border text-lg font-semibold tabular-nums transition focus:outline-none focus:ring-2 focus:ring-brass"
                    :class="puttClass(v)"
                >{{ v }}</button>
                <button type="button" @click="puttsExpanded = true" class="flex h-12 items-center justify-center rounded-xl border border-pine/15 bg-parchment/60 text-lg text-pine transition hover:border-brass focus:outline-none focus:ring-2 focus:ring-brass" aria-label="More putts">…</button>
            </div>
            <div v-else class="mt-2">
                <div class="grid grid-cols-5 gap-2">
                    <button v-for="v in allPutts" :key="v" type="button" @click="emit('set-putts', v); puttsExpanded = false" class="flex h-11 items-center justify-center rounded-lg border text-base font-semibold tabular-nums transition focus:outline-none focus:ring-2 focus:ring-brass" :class="puttClass(v)">{{ v }}</button>
                </div>
                <button type="button" @click="puttsExpanded = false" class="mt-2 w-full text-xs font-medium text-pine/60 hover:text-pine">Back</button>
            </div>
        </div>

        <!-- Hole navigation -->
        <div class="flex gap-3 pt-1">
            <button
                type="button"
                :disabled="!canPrev"
                @click="emit('prev')"
                class="flex-1 rounded-full border border-pine/20 px-6 py-3 text-sm font-semibold text-pine transition hover:border-brass disabled:opacity-40"
            >
                Back
            </button>
            <button
                type="button"
                :disabled="!canNext"
                @click="emit('next')"
                class="flex-1 rounded-full bg-pine px-6 py-3 text-sm font-semibold text-cream transition hover:bg-pine-light disabled:opacity-40"
            >
                Next Hole
            </button>
        </div>
    </div>
</template>
