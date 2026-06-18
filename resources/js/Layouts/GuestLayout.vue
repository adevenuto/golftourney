<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-pine px-4 py-10 font-sans text-ink"
    >
        <!-- atmosphere (matches the landing page) -->
        <div aria-hidden="true" class="pointer-events-none absolute inset-0">
            <div
                class="absolute inset-0"
                style="background: radial-gradient(120% 80% at 80% 0%, rgba(176,141,87,0.16), transparent 55%), radial-gradient(90% 70% at 0% 100%, rgba(13,46,32,0.9), transparent 60%);"
            ></div>
            <div class="absolute -right-40 -top-40 h-[26rem] w-[26rem] rounded-full border border-brass/15"></div>
            <div class="absolute -bottom-44 -left-44 h-[30rem] w-[30rem] rounded-full border border-cream/5"></div>
        </div>

        <Link
            href="/"
            class="relative z-10 flex items-center gap-2.5 text-cream transition hover:opacity-90"
        >
            <img src="/img/logo-emblem.svg" alt="GolfTourney" class="h-12 w-auto" />
            <span class="font-display text-2xl font-semibold tracking-tight">GolfTourney</span>
        </Link>

        <div
            class="relative z-10 mt-6 w-full overflow-hidden rounded-2xl border border-parchment-dark bg-cream px-6 py-8 shadow-2xl shadow-pine-deep/40 sm:max-w-md sm:px-8"
        >
            <slot />
        </div>
    </div>
</template>
