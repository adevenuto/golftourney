<script setup>
import { computed } from 'vue';

const props = defineProps({
    firstName: { type: String, default: '' },
    lastName: { type: String, default: '' },
    size: { type: String, default: 'md' }, // sm | md | lg
    online: { type: Boolean, default: false },
    // Identity ring — green for the current user, brass for others.
    ringColor: { type: String, default: 'ring-brass' },
});

const initials = computed(() => {
    const a = (props.firstName || '').trim()[0] || '';
    const b = (props.lastName || '').trim()[0] || '';
    return ((a + b) || '?').toUpperCase();
});

const sizes = {
    sm: 'h-9 w-9 text-xs ring-2',
    md: 'h-12 w-12 text-sm ring-[3px]',
    lg: 'h-16 w-16 text-xl ring-[3px]',
};
</script>

<template>
    <span class="relative inline-flex shrink-0">
        <span :class="[sizes[size], ringColor]" class="flex items-center justify-center rounded-full bg-pine-light font-semibold uppercase text-cream">
            {{ initials }}
        </span>
        <span
            v-if="online"
            class="absolute bottom-0 right-0 h-3.5 w-3.5 rounded-full bg-brass ring-2 ring-pine-deep"
            aria-hidden="true"
        ></span>
    </span>
</template>
