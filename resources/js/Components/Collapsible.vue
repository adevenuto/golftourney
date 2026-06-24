<script setup>
import { ref } from 'vue';

const props = defineProps({
    title: { type: String, default: '' },
    // Whether the section starts expanded.
    defaultOpen: { type: Boolean, default: true },
});

const open = ref(props.defaultOpen);
</script>

<template>
    <section>
        <button
            type="button"
            @click="open = !open"
            :aria-expanded="open"
            class="group flex w-full items-center justify-between gap-3 text-left"
        >
            <slot name="title">
                <h2 class="font-display text-2xl font-semibold text-pine transition group-hover:text-brass-dark">
                    {{ title }}
                </h2>
            </slot>

            <!-- Toggle chip — echoes the rounded, brass-on-hover nav buttons. -->
            <span
                class="inline-flex shrink-0 items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-medium tracking-wide transition"
                :class="
                    open
                        ? 'border-brass/50 bg-brass/10 text-brass-dark'
                        : 'border-pine/20 text-pine/70 group-hover:border-brass/60 group-hover:text-brass-dark'
                "
            >
                {{ open ? 'Hide' : 'Show' }}
                <svg
                    class="h-3.5 w-3.5 transition-transform duration-300"
                    :class="open ? 'rotate-180' : ''"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2.5"
                    aria-hidden="true"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </span>
        </button>

        <!-- Animate auto-height content by transitioning the grid row from 0fr to 1fr. -->
        <div
            class="grid transition-[grid-template-rows] duration-300 ease-out"
            :class="open ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'"
        >
            <div class="overflow-hidden">
                <div class="pt-4">
                    <slot />
                </div>
            </div>
        </div>
    </section>
</template>
