<script setup>
import { computed } from 'vue';

const props = defineProps({
    players: { type: Array, required: true },
    holeNumbers: { type: Array, required: true },
    par: { type: Number, default: 0 },
    meId: { type: Number, default: null },
    onlineIds: { type: Array, default: () => [] },
});

const front = computed(() => props.holeNumbers.filter((h) => h <= 9));
const back = computed(() => props.holeNumbers.filter((h) => h > 9));
const hasBack = computed(() => back.value.length > 0);

const val = (p, h) => {
    const v = p.holes?.[h];
    return v == null || v === '' ? null : Number(v);
};
const sum = (p, holes) => holes.reduce((t, h) => t + (val(p, h) ?? 0), 0);
const total = (p) => sum(p, props.holeNumbers);

const fullName = (p) => `${p.first_name} ${p.last_name}`;
const isOnline = (id) => props.onlineIds.includes(id);

// Total relative to par, once there's something entered.
const toPar = (p) => {
    const t = total(p);
    if (!t) return '';
    const d = t - props.par;
    return d === 0 ? 'E' : d > 0 ? `+${d}` : `${d}`;
};
</script>

<template>
    <div class="overflow-x-auto border rounded-2xl border-parchment-dark bg-cream">
        <table class="min-w-full border-separate border-spacing-0 text-center text-sm">
            <thead>
                <tr class="text-pine">
                    <th class="sticky left-0 z-10 border-b border-parchment-dark bg-cream px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">
                        Player
                    </th>
                    <th
                        v-for="h in holeNumbers"
                        :key="h"
                        class="border-b border-parchment-dark px-2.5 py-3 tabular-nums text-xs font-semibold"
                    >
                        {{ h }}
                    </th>
                    <th class="border-b border-parchment-dark bg-parchment/40 px-3 py-3 text-xs font-semibold uppercase">Out</th>
                    <th v-if="hasBack" class="border-b border-parchment-dark bg-parchment/40 px-3 py-3 text-xs font-semibold uppercase">In</th>
                    <th class="border-b border-parchment-dark bg-parchment/60 px-4 py-3 text-xs font-semibold uppercase">Tot</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="p in players"
                    :key="p.user_id"
                    :class="p.user_id === meId ? 'bg-brass/[0.06]' : ''"
                >
                    <td
                        class="sticky left-0 z-10 border-b border-parchment-dark/60 px-4 py-3 text-left"
                        :class="p.user_id === meId ? 'bg-[#f7f0e3]' : 'bg-cream'"
                    >
                        <span class="inline-flex items-center gap-2">
                            <span class="h-1.5 w-1.5 shrink-0 rounded-full" :class="isOnline(p.user_id) ? 'bg-pine' : 'bg-ink/20'"></span>
                            <span class="font-medium capitalize text-ink">{{ fullName(p) }}</span>
                        </span>
                    </td>
                    <td
                        v-for="h in holeNumbers"
                        :key="h"
                        class="border-b border-parchment-dark/60 px-2.5 py-3 tabular-nums text-ink/80"
                    >
                        {{ val(p, h) ?? '·' }}
                    </td>
                    <td class="border-b border-parchment-dark/60 bg-parchment/40 px-3 py-3 font-semibold tabular-nums text-pine">{{ sum(p, front) || '—' }}</td>
                    <td v-if="hasBack" class="border-b border-parchment-dark/60 bg-parchment/40 px-3 py-3 font-semibold tabular-nums text-pine">{{ sum(p, back) || '—' }}</td>
                    <td class="border-b border-parchment-dark/60 bg-parchment/60 px-4 py-3 font-display font-semibold tabular-nums text-pine">
                        {{ total(p) || '—' }}
                        <span v-if="toPar(p)" class="ml-0.5 text-xs font-normal text-ink/50">{{ toPar(p) }}</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
