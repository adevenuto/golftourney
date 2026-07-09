<script setup>
import { computed } from 'vue';

const props = defineProps({
    firstName: { type: String, default: '' },
    lastName: { type: String, default: '' },
    size: { type: String, default: 'md' }, // sm | md | lg
    online: { type: Boolean, default: false },
    active: { type: Boolean, default: false }, // e.g. it's me / current
    ringColor: { type: String, default: 'ring-cream/30' },
});

const initials = computed(() => {
    const a = (props.firstName || '').trim()[0] || '';
    const b = (props.lastName || '').trim()[0] || '';
    return ((a + b) || '?').toUpperCase();
});

// Deterministic brand-tinted background from the name.
const palette = ['bg-pine', 'bg-pine-light', 'bg-brass-dark', 'bg-[#3a5a44]', 'bg-[#7a5b34]', 'bg-[#2f6d4f]'];
const bg = computed(() => {
    const s = props.firstName + props.lastName;
    let h = 0;
    for (let i = 0; i < s.length; i++) h = (h * 31 + s.charCodeAt(i)) >>> 0;
    return palette[h % palette.length];
});

const sizes = {
    sm: 'h-9 w-9 text-xs',
    md: 'h-12 w-12 text-sm',
    lg: 'h-20 w-20 text-2xl',
};
</script>

<template>
    <span class="relative inline-flex shrink-0">
        <span
            :class="[sizes[size], bg, active ? 'ring-brass' : ringColor]"
            class="flex items-center justify-center rounded-full font-semibold uppercase text-cream ring-2"
        >
            {{ initials }}
        </span>
        <span
            v-if="online"
            class="absolute bottom-0 right-0 h-3 w-3 rounded-full bg-brass-light ring-2 ring-pine"
            aria-hidden="true"
        ></span>
    </span>
</template>
