<script setup>
/*
 * A single score cell with the traditional golf notation:
 *   birdie (−1)        → green circle
 *   eagle+ (−2 better) → green double circle
 *   par (0)            → plain number
 *   bogey (+1)         → dark square
 *   double+ (+2 worse) → dark double square
 * Falls back to a plain number when there's no par to compare against.
 */
import { computed } from 'vue';

const props = defineProps({
    value: { type: Number, default: null },
    par: { type: Number, default: null },
});

const kind = computed(() => {
    if (props.value == null) return 'empty';
    if (!props.par) return 'plain';
    const d = props.value - props.par;
    if (d <= -2) return 'eagle';
    if (d === -1) return 'birdie';
    if (d === 0) return 'plain';
    if (d === 1) return 'bogey';
    return 'double';
});
</script>

<template>
    <span v-if="kind === 'empty'" class="text-ink/25">·</span>
    <span v-else-if="kind === 'plain'" class="font-medium tabular-nums text-ink">{{ value }}</span>

    <!-- Birdie: single green circle -->
    <span v-else-if="kind === 'birdie'" class="inline-flex h-7 w-7 items-center justify-center rounded-full border-[1.5px] border-pine-light text-sm font-bold tabular-nums text-pine">{{ value }}</span>

    <!-- Eagle or better: green double circle -->
    <span v-else-if="kind === 'eagle'" class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-pine-light p-[2px] text-pine">
        <span class="inline-flex h-full w-full items-center justify-center rounded-full border-[1.5px] border-pine-light text-[13px] font-bold tabular-nums">{{ value }}</span>
    </span>

    <!-- Bogey: single dark square -->
    <span v-else-if="kind === 'bogey'" class="inline-flex h-7 w-7 items-center justify-center rounded-[5px] border-[1.5px] border-ink/55 text-sm font-semibold tabular-nums text-ink">{{ value }}</span>

    <!-- Double bogey or worse: dark double square -->
    <span v-else class="inline-flex h-7 w-7 items-center justify-center rounded-[6px] border border-ink/45 p-[2px] text-ink">
        <span class="inline-flex h-full w-full items-center justify-center rounded-[3px] border-[1.5px] border-ink/55 text-[13px] font-semibold tabular-nums">{{ value }}</span>
    </span>
</template>
