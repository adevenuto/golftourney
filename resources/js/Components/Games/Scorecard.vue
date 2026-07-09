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

const toPar = (p) => {
    const t = total(p);
    if (!t) return '';
    const d = t - props.par;
    return d === 0 ? 'E' : d > 0 ? `+${d}` : `${d}`;
};

function scoreColor(p, h) {
    const v = val(p, h);
    if (v == null) return 'text-ink/25';
    const par = Number(props.holePars?.[h]) || 0;
    if (!par) return 'text-ink/80';
    const d = v - par;
    if (d <= -1) return 'font-semibold text-emerald-600';
    if (d === 0) return 'text-ink/80';
    if (d === 1) return 'text-amber-600';
    return 'font-semibold text-red-600';
}
const colHi = (h) => (h === props.currentHole ? 'bg-brass/10' : '');

const stickyShadow = 'shadow-[1px_0_0_0_theme(colors.parchment.dark)]';
</script>

<template>
    <div class="overflow-x-auto bg-cream [-webkit-overflow-scrolling:touch]">
        <table class="min-w-full border-separate border-spacing-0 text-center text-[13px]">
            <thead>
                <!-- Hole numbers -->
                <tr class="text-pine">
                    <th :class="stickyShadow" class="sticky left-0 z-10 border-b border-parchment-dark bg-cream px-3 py-1.5 text-left text-[10px] font-semibold uppercase tracking-wider">Hole</th>
                    <th v-for="h in holeNumbers" :key="h" class="w-8 border-b border-parchment-dark px-1.5 py-1.5 text-[11px] font-semibold tabular-nums" :class="colHi(h)">{{ h }}</th>
                    <th class="border-b border-parchment-dark bg-parchment/40 px-2 py-1.5 text-[10px] font-semibold uppercase">Out</th>
                    <th v-if="hasBack" class="border-b border-parchment-dark bg-parchment/40 px-2 py-1.5 text-[10px] font-semibold uppercase">In</th>
                    <th class="border-b border-parchment-dark bg-parchment/60 px-2.5 py-1.5 text-[10px] font-semibold uppercase">Tot</th>
                </tr>
                <!-- Par -->
                <tr v-if="hasPars" class="text-ink/45">
                    <th :class="stickyShadow" class="sticky left-0 z-10 border-b border-parchment-dark bg-cream px-3 py-1 text-left text-[10px] font-medium uppercase tracking-wider">Par</th>
                    <th v-for="h in holeNumbers" :key="h" class="px-1.5 py-1 text-[11px] tabular-nums" :class="colHi(h)">{{ holePars[h] ?? '·' }}</th>
                    <th class="bg-parchment/40 px-2 py-1 text-[11px] tabular-nums">{{ parSum(front) || '—' }}</th>
                    <th v-if="hasBack" class="bg-parchment/40 px-2 py-1 text-[11px] tabular-nums">{{ parSum(back) || '—' }}</th>
                    <th class="bg-parchment/60 px-2.5 py-1 text-[11px] tabular-nums">{{ par || '—' }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="p in players" :key="p.user_id" :class="p.user_id === meId ? 'bg-brass/[0.06]' : ''">
                    <td :class="[stickyShadow, p.user_id === meId ? 'bg-[#f7f0e3]' : 'bg-cream']" class="sticky left-0 z-10 border-t border-parchment-dark/60 px-3 py-1.5 text-left">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="h-1.5 w-1.5 shrink-0 rounded-full" :class="isOnline(p.user_id) ? 'bg-pine' : 'bg-ink/20'"></span>
                            <span class="max-w-[5.5rem] truncate font-medium capitalize text-ink">{{ fullName(p) }}</span>
                        </span>
                    </td>
                    <td v-for="h in holeNumbers" :key="h" class="border-t border-parchment-dark/60 px-1.5 py-1.5 tabular-nums" :class="[colHi(h), scoreColor(p, h)]">{{ val(p, h) ?? '·' }}</td>
                    <td class="border-t border-parchment-dark/60 bg-parchment/40 px-2 py-1.5 font-semibold tabular-nums text-pine">{{ sum(p, front) || '—' }}</td>
                    <td v-if="hasBack" class="border-t border-parchment-dark/60 bg-parchment/40 px-2 py-1.5 font-semibold tabular-nums text-pine">{{ sum(p, back) || '—' }}</td>
                    <td class="border-t border-parchment-dark/60 bg-parchment/60 px-2.5 py-1.5 font-semibold tabular-nums text-pine">
                        {{ total(p) || '—' }}<span v-if="toPar(p)" class="ml-0.5 text-[10px] font-normal text-ink/50">{{ toPar(p) }}</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
