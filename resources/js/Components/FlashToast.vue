<script setup>
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Global success toast: the small pine pill that slides up from the bottom
 * whenever a request flashes `success`. Mounted once in AuthenticatedLayout,
 * so any page (or redirect) that flashes a message gets it automatically.
 */
const page = usePage();
const toast = ref('');
let toastTimer;

watch(
    () => page.props.flash?.success,
    (msg) => {
        if (!msg) return;
        toast.value = msg;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => (toast.value = ''), 3200);
    },
    { immediate: true },
);
</script>

<template>
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-y-2 opacity-0"
        leave-active-class="transition duration-200 ease-in"
        leave-to-class="translate-y-2 opacity-0"
    >
        <div
            v-if="toast"
            role="status"
            aria-live="polite"
            class="fixed z-50 px-5 -translate-x-1/2 rounded-full shadow-lg bottom-6 left-1/2 py-2.5 text-sm font-medium bg-pine text-cream"
        >
            {{ toast }}
        </div>
    </Transition>
</template>
