<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-brass text-start text-base font-medium text-pine bg-brass/10 focus:outline-none focus:text-pine focus:bg-brass/20 focus:border-brass transition duration-150 ease-in-out'
        : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-ink/70 hover:text-pine hover:bg-parchment hover:border-parchment-dark focus:outline-none focus:text-pine focus:bg-parchment focus:border-parchment-dark transition duration-150 ease-in-out',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
