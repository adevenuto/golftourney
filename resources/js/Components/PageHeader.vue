<script setup>
import { computed } from 'vue';

const props = defineProps({
    eyebrow: { type: String, default: null },
    title: { type: String, default: null },
    // Inner content width — the pine band is always full-bleed.
    maxWidth: { type: String, default: '5xl' }, // '3xl' | '5xl' | '7xl'
    capitalizeTitle: { type: Boolean, default: false },
});

const maxWidthClass = computed(
    () =>
        ({
            '3xl': 'max-w-3xl',
            '5xl': 'max-w-5xl',
            '7xl': 'max-w-7xl',
        })[props.maxWidth] ?? 'max-w-5xl',
);
</script>

<template>
    <header class="border-b border-parchment-dark bg-pine text-cream">
        <div :class="['mx-auto px-4 py-10 sm:px-6 lg:px-8', maxWidthClass]">
            <!-- Optional row above the title (e.g. a back link) -->
            <slot name="top" />

            <div
                class="flex flex-wrap items-end justify-between gap-4"
                :class="$slots.top ? 'mt-4' : ''"
            >
                <div>
                    <p
                        v-if="eyebrow"
                        class="text-xs uppercase tracking-[0.35em] text-brass-light"
                    >
                        {{ eyebrow }}
                    </p>
                    <h1
                        class="text-4xl font-semibold leading-none font-display sm:text-5xl"
                        :class="[eyebrow ? 'mt-3' : '', capitalizeTitle ? 'capitalize' : '']"
                    >
                        <slot name="title">{{ title }}</slot>
                    </h1>
                </div>

                <!-- Optional right-aligned content (counts, stats, actions) -->
                <slot name="actions" />
            </div>

            <!-- Optional content below the title block (helper text, etc.) -->
            <slot name="below" />
        </div>
    </header>
</template>
