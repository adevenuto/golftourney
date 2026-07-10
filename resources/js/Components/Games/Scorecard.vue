<script setup>
import { computed } from 'vue';
import ScoreMark from '@/Components/Games/ScoreMark.vue';

const props = defineProps({
    players: { type: Array, required: true },
    holeNumbers: { type: Array, required: true },
    holePars: { type: Object, default: () => ({}) },
    holeLengths: { type: Object, default: () => ({}) },
    par: { type: Number, default: 0 },
    meId: { type: Number, default: null },
    onlineIds: { type: Array, default: () => [] },
    currentHole: { type: Number, default: null },
});

const front = computed(() => props.holeNumbers.filter((h) => h <= 9));
const back = computed(() => props.holeNumbers.filter((h) => h > 9));
const hasBack = computed(() => back.value.length > 0);
const hasPars = computed(() => Object.keys(props.holePars || {}).length > 0);
const hasLengths = computed(() => Object.keys(props.holeLengths || {}).length > 0);
const lengthSum = (holes) => holes.reduce((t, h) => t + (Number(props.holeLengths?.[h]) || 0), 0);

const val = (p, h) => {
    const v = p.holes?.[h];
    return v == null || v === '' ? null : Number(v);
};
const sum = (p, holes) => holes.reduce((t, h) => t + (val(p, h) ?? 0), 0);
const total = (p) => sum(p, props.holeNumbers);
const parSum = (holes) => holes.reduce((t, h) => t + (Number(props.holePars?.[h]) || 0), 0);

const fullName = (p) => `${p.first_name} ${p.last_name}`;
const isOnline = (id) => props.onlineIds.includes(id);
const colHi = (h) => (h === props.currentHole ? 'bg-brass/15' : '');

// Shared cell sizing (bigger = easier to read/tap-scroll on a phone).
const hole = 'w-11 min-w-[2.75rem] px-1 py-2.5 text-base tabular-nums';
const sub = 'w-12 min-w-[3rem] px-2 py-2.5 text-sm font-bold tabular-nums';
const sticky = 'sticky left-0 z-10';
// Light-green accents (roster/label column + running-total columns).
const sage = 'bg-[#e9f0e3]';
</script>

<template>
    <div class="overflow-x-auto bg-cream [-webkit-overflow-scrolling:touch]">
        <table class="min-w-full border-separate border-spacing-0 text-center">
            <thead>
                <!-- Hole numbers (green header band) -->
                <tr>
                    <th :class="sticky" class="bg-pine-light pl-4 pr-2 py-2.5 text-left text-[11px] font-bold uppercase tracking-wider text-cream">Hole</th>
                    <th v-for="h in front" :key="h" class="border-b-2 border-pine-light/25 font-bold text-pine" :class="[hole, colHi(h)]">{{ h }}</th>
                    <th v-if="hasBack" class="border-b-2 border-pine-light/25 text-[11px] font-bold uppercase text-pine" :class="[sub, sage]">Out</th>
                    <template v-if="hasBack">
                        <th v-for="h in back" :key="h" class="border-b-2 border-pine-light/25 font-bold text-pine" :class="[hole, colHi(h)]">{{ h }}</th>
                        <th class="border-b-2 border-pine-light/25 text-[11px] font-bold uppercase text-pine" :class="[sub, sage]">In</th>
                    </template>
                    <th class="border-b-2 border-pine-light/25 bg-pine-light/15 text-[11px] font-bold uppercase text-pine" :class="sub">Tot</th>
                </tr>
                <!-- Yards -->
                <tr v-if="hasLengths">
                    <th :class="[sticky, sage]" class="border-b border-parchment-dark pl-4 pr-2 py-1.5 text-left text-[10px] font-semibold uppercase tracking-wider text-pine/70">Yds</th>
                    <th v-for="h in front" :key="h" class="border-b border-parchment-dark px-1 py-1.5 text-[11px] tabular-nums text-ink/40" :class="colHi(h)">{{ holeLengths[h] ?? '·' }}</th>
                    <th v-if="hasBack" class="border-b border-parchment-dark px-2 py-1.5 text-[11px] tabular-nums text-pine/60" :class="sage">{{ lengthSum(front) || '—' }}</th>
                    <template v-if="hasBack">
                        <th v-for="h in back" :key="h" class="border-b border-parchment-dark px-1 py-1.5 text-[11px] tabular-nums text-ink/40" :class="colHi(h)">{{ holeLengths[h] ?? '·' }}</th>
                        <th class="border-b border-parchment-dark px-2 py-1.5 text-[11px] tabular-nums text-pine/60" :class="sage">{{ lengthSum(back) || '—' }}</th>
                    </template>
                    <th class="border-b border-parchment-dark bg-pine-light/10 px-2 py-1.5 text-[11px] tabular-nums text-pine/60">{{ lengthSum(holeNumbers) || '—' }}</th>
                </tr>
                <!-- Par -->
                <tr v-if="hasPars">
                    <th :class="[sticky, sage]" class="border-b border-parchment-dark pl-4 pr-2 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-pine/70">Par</th>
                    <th v-for="h in front" :key="h" class="border-b border-parchment-dark px-1 py-2 text-sm tabular-nums text-ink/50" :class="colHi(h)">{{ holePars[h] ?? '·' }}</th>
                    <th v-if="hasBack" class="border-b border-parchment-dark px-2 py-2 text-sm tabular-nums text-pine/60" :class="sage">{{ parSum(front) || '—' }}</th>
                    <template v-if="hasBack">
                        <th v-for="h in back" :key="h" class="border-b border-parchment-dark px-1 py-2 text-sm tabular-nums text-ink/50" :class="colHi(h)">{{ holePars[h] ?? '·' }}</th>
                        <th class="border-b border-parchment-dark px-2 py-2 text-sm tabular-nums text-pine/60" :class="sage">{{ parSum(back) || '—' }}</th>
                    </template>
                    <th class="border-b border-parchment-dark bg-pine-light/10 px-2 py-2 text-sm tabular-nums text-pine/60">{{ par || '—' }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="p in players" :key="p.user_id" :class="p.user_id === meId ? 'bg-[#f3f7f0]' : ''">
                    <td :class="[sticky, p.user_id === meId ? 'bg-[#dde9d6]' : sage]" class="border-b border-parchment-dark/60 pl-4 pr-2 py-2.5 text-left">
                        <span class="flex items-center gap-2">
                            <span class="h-2 w-2 shrink-0 rounded-full" :class="isOnline(p.user_id) ? 'bg-pine' : 'bg-ink/20'"></span>
                            <span class="max-w-[4.5rem] truncate text-sm font-semibold capitalize text-pine">{{ fullName(p) }}</span>
                        </span>
                    </td>
                    <td v-for="h in front" :key="h" class="border-b border-parchment-dark/60" :class="[hole, colHi(h)]">
                        <ScoreMark :value="val(p, h)" :par="holePars[h] ?? null" />
                    </td>
                    <td v-if="hasBack" class="border-b border-parchment-dark/60 text-pine" :class="[sub, sage]">{{ sum(p, front) || '—' }}</td>
                    <template v-if="hasBack">
                        <td v-for="h in back" :key="h" class="border-b border-parchment-dark/60" :class="[hole, colHi(h)]">
                            <ScoreMark :value="val(p, h)" :par="holePars[h] ?? null" />
                        </td>
                        <td class="border-b border-parchment-dark/60 text-pine" :class="[sub, sage]">{{ sum(p, back) || '—' }}</td>
                    </template>
                    <td class="border-b border-parchment-dark/60 bg-pine-light/10 font-display text-pine" :class="sub">{{ total(p) || '—' }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
