<script setup>
/**
 * Global toast: watches the shared `flash` bag and shows a modern toast for any
 * state — success / warning / danger / info. Mounted once in AuthenticatedLayout
 * (and the game screen), so any request that flashes a message gets it. Flash
 * with ->with('success'|'warning'|'error'|'info', '…'); 'error' maps to danger.
 */
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Toast from '@/Components/Toast.vue';

const page = usePage();
const active = ref(null); // { type, message }
let timer;

// Session key → toast state (first match wins if several are set).
const KEYS = [
    ['error', 'danger'],
    ['warning', 'warning'],
    ['success', 'success'],
    ['info', 'info'],
];

watch(
    () => page.props.flash,
    (flash) => {
        if (!flash) return;
        const hit = KEYS.map(([key, type]) => (flash[key] ? { type, message: flash[key] } : null)).find(Boolean);
        if (!hit) return;
        active.value = hit;
        clearTimeout(timer);
        timer = setTimeout(() => (active.value = null), 4200);
    },
    { immediate: true, deep: true },
);

function dismiss() {
    clearTimeout(timer);
    active.value = null;
}
</script>

<template>
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-y-3 opacity-0"
        leave-active-class="transition duration-200 ease-in"
        leave-to-class="translate-y-3 opacity-0"
    >
        <div
            v-if="active"
            role="status"
            aria-live="polite"
            class="fixed bottom-6 left-1/2 z-50 w-[calc(100%-2rem)] max-w-sm -translate-x-1/2"
        >
            <Toast :type="active.type" :message="active.message" @close="dismiss" />
        </div>
    </Transition>
</template>
