<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    hole: { type: Number, required: true },
    par: { type: Number, default: null },
    strokes: { type: Number, default: null },
    putts: { type: Number, default: null },
});
const emit = defineEmits(['set-strokes', 'set-putts']);

const strokesExpanded = ref(false);
const puttsExpanded = ref(false);

// Par-centered stroke tiles (par-2 … par+2). Floored at 2 so a hole-in-one (1)
// never sits on the compact pad — it lives under the "…" expander, since an ace
// is rare. Default centre 4.
const base = computed(() => props.par || 4);
const strokeTiles = computed(() => {
    const start = Math.max(2, base.value - 2);
    return [0, 1, 2, 3, 4].map((i) => start + i);
});
const allStrokes = Array.from({ length: 15 }, (_, i) => i + 1);

// Putts can't exceed strokes - 1, so the pad only offers valid counts. Until a
// score is entered there's nothing to base putts on.
const maxPutts = computed(() => (props.strokes != null ? Math.max(0, props.strokes - 1) : null));
const puttsDisabled = computed(() => maxPutts.value === null);
const puttTiles = computed(() => Array.from({ length: Math.min(maxPutts.value ?? 0, 4) + 1 }, (_, i) => i));
const allPutts = computed(() => Array.from({ length: (maxPutts.value ?? 0) + 1 }, (_, i) => i));

const neutral = 'bg-[#eae7df] text-pine hover:bg-[#e3ded1]';
// Par tile: a brass ring makes it obvious which number is par (no label needed).
const parAccent = 'bg-[#eae7df] text-pine ring-2 ring-inset ring-brass/70 hover:bg-[#e3ded1]';
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
                    :aria-label="par && v === par ? `${v} (par)` : `${v}`"
                    class="flex h-16 items-center justify-center rounded-2xl text-2xl font-semibold tabular-nums transition active:scale-95 focus:outline-none focus:ring-2 focus:ring-brass"
                    :class="strokeCls(v)"
                >
                    {{ v }}
                </button>
                <button type="button" @click="strokesExpanded = true" class="flex h-16 items-center justify-center rounded-2xl text-2xl text-pine/50 transition active:scale-95 focus:outline-none focus:ring-2 focus:ring-brass" :class="neutral" aria-label="More strokes">…</button>
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
                <button v-if="putts != null && !puttsDisabled" type="button" @click="emit('set-putts', null)" class="text-xs font-medium text-ink/40 transition hover:text-red-700">Clear</button>
            </div>

            <p v-if="puttsDisabled" class="mt-3 flex h-16 items-center justify-center rounded-2xl bg-[#eae7df]/50 text-sm font-medium text-ink/35">Enter strokes first</p>

            <template v-else>
                <div v-if="!puttsExpanded" class="mt-3 grid grid-cols-3 gap-3">
                    <button
                        v-for="v in puttTiles"
                        :key="v"
                        type="button"
                        @click="emit('set-putts', v)"
                        :aria-pressed="v === putts"
                        class="flex h-16 items-center justify-center rounded-2xl text-2xl font-semibold tabular-nums transition active:scale-95 focus:outline-none focus:ring-2 focus:ring-brass"
                        :class="puttCls(v)"
                    >{{ v }}</button>
                    <button v-if="maxPutts > 4" type="button" @click="puttsExpanded = true" class="flex h-16 items-center justify-center rounded-2xl text-2xl text-pine/50 transition active:scale-95 focus:outline-none focus:ring-2 focus:ring-brass" :class="neutral" aria-label="More putts">…</button>
                </div>
                <div v-else class="mt-3">
                    <div class="grid grid-cols-5 gap-2">
                        <button v-for="v in allPutts" :key="v" type="button" @click="emit('set-putts', v); puttsExpanded = false" class="flex h-12 items-center justify-center rounded-xl text-base font-semibold tabular-nums transition active:scale-95 focus:outline-none focus:ring-2 focus:ring-brass" :class="puttCls(v)">{{ v }}</button>
                    </div>
                    <button type="button" @click="puttsExpanded = false" class="mt-2 w-full text-xs font-medium text-pine/60 hover:text-pine">Back</button>
                </div>
            </template>
        </div>
    </div>
</template>
