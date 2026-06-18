<script setup>
import { computed } from 'vue';

const props = defineProps({
    page: { type: Number, required: true },
    pageCount: { type: Number, required: true },
});
const emit = defineEmits(['update:page']);

// Windowed page list with ellipses, e.g. 1 … 4 5 6 … 20
const pages = computed(() => {
    const total = props.pageCount;
    const cur = props.page;
    if (total <= 7) {
        return Array.from({ length: total }, (_, i) => i + 1);
    }
    const wanted = [1, total, cur, cur - 1, cur + 1].filter(
        (p) => p >= 1 && p <= total,
    );
    const unique = [...new Set(wanted)].sort((a, b) => a - b);

    const out = [];
    let prev = 0;
    for (const p of unique) {
        if (p - prev > 1) out.push('…');
        out.push(p);
        prev = p;
    }
    return out;
});

function go(target) {
    if (target !== props.page) emit('update:page', target);
}

const arrowClass =
    'inline-flex h-8 w-8 items-center justify-center rounded-lg text-ink/70 transition hover:bg-parchment-dark disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-transparent';
</script>

<template>
    <nav class="flex items-center gap-1" aria-label="Pagination">
        <button
            type="button"
            :class="arrowClass"
            :disabled="page <= 1"
            aria-label="Previous page"
            @click="emit('update:page', page - 1)"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
        </button>

        <template v-for="(p, i) in pages" :key="i">
            <span v-if="p === '…'" class="px-1.5 text-ink/40">…</span>
            <button
                v-else
                type="button"
                @click="go(p)"
                :aria-current="p === page ? 'page' : undefined"
                class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2.5 text-sm font-medium tabular-nums transition"
                :class="
                    p === page
                        ? 'bg-pine text-cream'
                        : 'text-ink/70 hover:bg-parchment-dark'
                "
            >
                {{ p }}
            </button>
        </template>

        <button
            type="button"
            :class="arrowClass"
            :disabled="page >= pageCount"
            aria-label="Next page"
            @click="emit('update:page', page + 1)"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
        </button>
    </nav>
</template>
