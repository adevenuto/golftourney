<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center justify-center rounded-full border border-pine/20 bg-cream px-5 py-2.5 text-sm font-medium text-pine shadow-sm transition duration-150 ease-in-out hover:border-brass hover:text-brass-dark focus:outline-none focus:ring-2 focus:ring-brass focus:ring-offset-2 disabled:opacity-50"
    >
        <slot />
    </button>
</template>
