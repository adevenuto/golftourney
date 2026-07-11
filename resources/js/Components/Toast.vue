<script setup>
/*
 * A single toast pill. Presentational only — pass `type` (success | warning |
 * danger | info) and `message`; emits `close` when the dismiss button is tapped.
 * Driven globally by FlashToast, but reusable anywhere.
 */
import { computed } from 'vue';

const props = defineProps({
    type: { type: String, default: 'success' },
    message: { type: String, default: '' },
});
defineEmits(['close']);

const STATES = {
    success: {
        badge: 'bg-pine/10 text-pine',
        border: 'border-pine/15',
        path: 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    warning: {
        badge: 'bg-amber-500/15 text-amber-600',
        border: 'border-amber-500/30',
        path: 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z',
    },
    danger: {
        badge: 'bg-red-500/15 text-red-600',
        border: 'border-red-500/25',
        path: 'M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    info: {
        badge: 'bg-sky-500/15 text-sky-700',
        border: 'border-sky-500/25',
        path: 'M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z',
    },
};

const state = computed(() => STATES[props.type] ?? STATES.success);
</script>

<template>
    <div class="flex items-center gap-3 rounded-2xl border bg-cream/95 py-3 pl-3.5 pr-2.5 shadow-xl backdrop-blur" :class="state.border">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full" :class="state.badge">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" :d="state.path" />
            </svg>
        </span>
        <p class="text-sm font-medium leading-snug text-ink">{{ message }}</p>
        <button
            type="button"
            @click="$emit('close')"
            class="ml-auto shrink-0 rounded-full p-1 text-ink/30 transition hover:bg-ink/5 hover:text-ink"
            aria-label="Dismiss"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" /></svg>
        </button>
    </div>
</template>
