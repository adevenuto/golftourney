<script setup>
defineProps({
    from: { type: Number, required: true },
    to: { type: Number, required: true },
    total: { type: Number, required: true },
});

const fmt = (n) => n.toLocaleString();
</script>

<template>
    <p class="text-sm text-ink/60">
        Showing
        <span class="font-medium tabular-nums text-ink">{{ fmt(from) }}–{{ fmt(to) }}</span>
        of
        <span class="font-medium tabular-nums text-ink">{{ fmt(total) }}</span>
    </p>
</template>
