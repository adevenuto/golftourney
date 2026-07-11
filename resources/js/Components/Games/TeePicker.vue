<script setup>
defineProps({
    teeboxes: { type: Array, default: () => [] }, // [{ name, rating, slope }]
    modelValue: { type: String, default: '' },
});
const emit = defineEmits(['update:modelValue']);

// Map a real tee name to a colour dot (tee names vary by course).
function colorFor(name) {
    const n = (name || '').toLowerCase();
    if (n.includes('black')) return '#1b1d1a';
    if (n.includes('blue')) return '#2b6cb0';
    if (n.includes('white')) return '#f4f1e8';
    if (n.includes('gold') || n.includes('yellow')) return '#caa86e';
    if (n.includes('red')) return '#c0392b';
    if (n.includes('green')) return '#2f6d4f';
    if (n.includes('silver') || n.includes('gray') || n.includes('grey')) return '#9aa0a6';
    return '#b08d57'; // brass fallback
}
</script>

<template>
    <div class="grid grid-cols-2 gap-2">
        <button
            v-for="t in teeboxes"
            :key="t.name"
            type="button"
            @click="emit('update:modelValue', t.name)"
            class="flex items-center gap-2.5 rounded-xl border px-3 py-2.5 text-left transition"
            :class="modelValue === t.name ? 'border-pine bg-pine/5 ring-1 ring-pine' : 'border-pine/15 hover:border-brass'"
        >
            <span class="h-4 w-4 shrink-0 rounded-full border border-ink/10 shadow-sm" :style="{ backgroundColor: colorFor(t.name) }"></span>
            <span class="min-w-0">
                <span class="block truncate text-sm font-medium capitalize text-ink">{{ t.name }}</span>
                <span v-if="t.rating" class="block text-xs tabular-nums text-ink/50">{{ t.rating }} / {{ t.slope }}</span>
            </span>
        </button>
    </div>
</template>
