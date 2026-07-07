<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    holeNumbers: { type: Array, required: true },
    myHoles: { type: Object, required: true }, // reactive: hole -> strokes
    par: { type: Number, default: 0 },
});
const emit = defineEmits(['save']);

const idx = ref(0);
const hole = computed(() => props.holeNumbers[idx.value]);
const score = computed(() => {
    const v = props.myHoles[hole.value];
    return v == null || v === '' ? null : Number(v);
});

const played = computed(() =>
    props.holeNumbers.reduce((t, h) => {
        const v = props.myHoles[h];
        return t + (v == null || v === '' ? 0 : Number(v));
    }, 0),
);
const enteredCount = computed(() => props.holeNumbers.filter((h) => {
    const v = props.myHoles[h];
    return v != null && v !== '';
}).length);

function set(value) {
    const clamped = Math.max(1, Math.min(20, value));
    props.myHoles[hole.value] = clamped;
    emit('save', hole.value);
}
const bump = (d) => set((score.value ?? props.par ?? 4) + (score.value == null ? 0 : d));

const quick = [3, 4, 5, 6, 7];
const canPrev = computed(() => idx.value > 0);
const canNext = computed(() => idx.value < props.holeNumbers.length - 1);
</script>

<template>
    <div class="p-6 border rounded-2xl border-parchment-dark bg-cream">
        <!-- Hole header -->
        <div class="flex items-center justify-between">
            <button
                type="button"
                :disabled="!canPrev"
                @click="idx--"
                class="inline-flex items-center justify-center w-10 h-10 transition rounded-full text-pine hover:bg-pine/10 disabled:opacity-30"
                aria-label="Previous hole"
            >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            </button>
            <div class="text-center">
                <p class="text-xs font-medium tracking-widest uppercase text-ink/40">Hole</p>
                <p class="font-display text-4xl font-semibold leading-none text-pine tabular-nums">{{ hole }}</p>
            </div>
            <button
                type="button"
                :disabled="!canNext"
                @click="idx++"
                class="inline-flex items-center justify-center w-10 h-10 transition rounded-full text-pine hover:bg-pine/10 disabled:opacity-30"
                aria-label="Next hole"
            >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
            </button>
        </div>

        <!-- Big stepper -->
        <div class="flex items-center justify-center gap-6 mt-6">
            <button
                type="button"
                @click="bump(-1)"
                :disabled="score == null || score <= 1"
                class="flex items-center justify-center text-2xl transition border rounded-full h-14 w-14 border-pine/20 text-pine hover:border-brass hover:text-brass-dark disabled:opacity-30"
                aria-label="Decrease"
            >−</button>

            <div class="w-20 text-center">
                <span class="font-display text-6xl font-semibold leading-none tabular-nums" :class="score == null ? 'text-ink/25' : 'text-pine'">
                    {{ score ?? '–' }}
                </span>
            </div>

            <button
                type="button"
                @click="bump(1)"
                class="flex items-center justify-center text-2xl transition rounded-full h-14 w-14 bg-pine text-cream hover:bg-pine-light"
                aria-label="Increase"
            >+</button>
        </div>

        <!-- Quick picks -->
        <div class="flex justify-center gap-2 mt-6">
            <button
                v-for="n in quick"
                :key="n"
                type="button"
                @click="set(n)"
                class="h-10 w-10 rounded-full border text-sm font-semibold tabular-nums transition"
                :class="score === n ? 'border-pine bg-pine text-cream' : 'border-pine/20 text-pine hover:border-brass'"
            >{{ n }}</button>
        </div>

        <!-- Progress + running total -->
        <div class="flex items-center justify-between pt-5 mt-6 text-sm border-t border-parchment-dark">
            <span class="text-ink/50">{{ enteredCount }}/{{ holeNumbers.length }} holes</span>
            <span class="text-ink/60">Total <span class="font-display text-lg font-semibold text-pine tabular-nums">{{ played || '—' }}</span></span>
        </div>
    </div>
</template>
