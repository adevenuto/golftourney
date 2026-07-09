<script setup>
import { computed } from 'vue';

const props = defineProps({
    players: { type: Array, required: true },
    holeNumbers: { type: Array, required: true },
    holePars: { type: Object, default: () => ({}) },
    par: { type: Number, default: 0 },
    meId: { type: Number, default: null },
    onlineIds: { type: Array, default: () => [] },
    currentHole: { type: Number, default: null },
});

const front = computed(() => props.holeNumbers.filter((h) => h <= 9));
const back = computed(() => props.holeNumbers.filter((h) => h > 9));
const hasBack = computed(() => back.value.length > 0);
const hasPars = computed(() => Object.keys(props.holePars || {}).length > 0);

const val = (p, h) => {
    const v = p.holes?.[h];
    return v == null || v === '' ? null : Number(v);
};
const sum = (p, holes) => holes.reduce((t, h) => t + (val(p, h) ?? 0), 0);
const total = (p) => sum(p, props.holeNumbers);
const parSum = (holes) => holes.reduce((t, h) => t + (Number(props.holePars?.[h]) || 0), 0);

const fullName = (p) => `${p.first_name} ${p.last_name}`;
const isOnline = (id) => props.onlineIds.includes(id);

function scoreColor(p, h) {
    const v = val(p, h);
    if (v == null) return 'text-ink/25';
    const par = Number(props.holePars?.[h]) || 0;
    if (!par) return 'text-ink';
    const d = v - par;
    if (d <= -1) return 'font-bold text-emerald-600';
    if (d === 0) return 'text-ink';
    if (d === 1) return 'text-amber-600';
    return 'font-bold text-red-600';
}
const colHi = (h) => (h === props.currentHole ? 'bg-brass/15' : '');

// Shared cell sizing (bigger = easier to read/tap-scroll on a phone).
const hole = 'w-11 min-w-[2.75rem] px-1.5 py-3.5 text-base tabular-nums';
const sub = 'w-12 min-w-[3rem] px-2 py-3.5 text-base font-semibold tabular-nums text-pine';
const sticky = 'sticky left-0 z-10 shadow-[1px_0_0_0_theme(colors.parchment.dark)]';
</script>

<template>
    <div class="overflow-x-auto bg-cream [-webkit-overflow-scrolling:touch]">
        <table class="min-w-full border-separate border-spacing-0 text-center">
            <thead>
                <!-- Hole numbers -->
                <tr class="text-pine">
                    <th :class="sticky" class="border-b-2 border-parchment-dark bg-cream px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Hole</th>
                    <th v-for="h in front" :key="h" class="border-b-2 border-parchment-dark font-bold" :class="[hole, colHi(h)]">{{ h }}</th>
                    <th v-if="hasBack" class="border-b-2 border-parchment-dark bg-parchment/50 text-xs font-bold uppercase" :class="sub">Out</th>
                    <template v-if="hasBack">
                        <th v-for="h in back" :key="h" class="border-b-2 border-parchment-dark font-bold" :class="[hole, colHi(h)]">{{ h }}</th>
                        <th class="border-b-2 border-parchment-dark bg-parchment/50 text-xs font-bold uppercase" :class="sub">In</th>
                    </template>
                    <th class="border-b-2 border-parchment-dark bg-brass/15 text-xs font-bold uppercase text-brass-dark" :class="sub">Tot</th>
                </tr>
                <!-- Par -->
                <tr v-if="hasPars" class="text-ink/50">
                    <th :class="sticky" class="border-b border-parchment-dark bg-cream px-4 py-2 text-left text-[11px] font-semibold uppercase tracking-wider">Par</th>
                    <th v-for="h in front" :key="h" class="border-b border-parchment-dark px-1.5 py-2 text-sm tabular-nums" :class="colHi(h)">{{ holePars[h] ?? '·' }}</th>
                    <th v-if="hasBack" class="border-b border-parchment-dark bg-parchment/50 px-2 py-2 text-sm tabular-nums">{{ parSum(front) || '—' }}</th>
                    <template v-if="hasBack">
                        <th v-for="h in back" :key="h" class="border-b border-parchment-dark px-1.5 py-2 text-sm tabular-nums" :class="colHi(h)">{{ holePars[h] ?? '·' }}</th>
                        <th class="border-b border-parchment-dark bg-parchment/50 px-2 py-2 text-sm tabular-nums">{{ parSum(back) || '—' }}</th>
                    </template>
                    <th class="border-b border-parchment-dark bg-brass/15 px-2 py-2 text-sm tabular-nums">{{ par || '—' }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="p in players" :key="p.user_id" :class="p.user_id === meId ? 'bg-brass/[0.07]' : ''">
                    <td :class="[sticky, p.user_id === meId ? 'bg-[#f7f0e3]' : 'bg-cream']" class="border-b border-parchment-dark/60 px-4 py-3.5 text-left">
                        <span class="flex items-center gap-2">
                            <span class="h-2 w-2 shrink-0 rounded-full" :class="isOnline(p.user_id) ? 'bg-pine' : 'bg-ink/20'"></span>
                            <span class="max-w-[7rem] truncate text-sm font-semibold capitalize text-ink">{{ fullName(p) }}</span>
                        </span>
                    </td>
                    <td v-for="h in front" :key="h" class="border-b border-parchment-dark/60" :class="[hole, colHi(h), scoreColor(p, h)]">{{ val(p, h) ?? '·' }}</td>
                    <td v-if="hasBack" class="border-b border-parchment-dark/60 bg-parchment/50" :class="sub">{{ sum(p, front) || '—' }}</td>
                    <template v-if="hasBack">
                        <td v-for="h in back" :key="h" class="border-b border-parchment-dark/60" :class="[hole, colHi(h), scoreColor(p, h)]">{{ val(p, h) ?? '·' }}</td>
                        <td class="border-b border-parchment-dark/60 bg-parchment/50" :class="sub">{{ sum(p, back) || '—' }}</td>
                    </template>
                    <td class="border-b border-parchment-dark/60 bg-brass/15 font-display" :class="sub">{{ total(p) || '—' }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
