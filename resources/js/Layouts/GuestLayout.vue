<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="relative flex flex-col items-center justify-center min-h-screen px-4 py-10 overflow-hidden font-sans bg-pine text-ink"
    >
        <!-- atmosphere (matches the landing page) -->
        <div aria-hidden="true" class="absolute inset-0 pointer-events-none">
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
            <img src="/img/logo-emblem.svg" alt="GolfTourney" class="w-auto h-28" />
            <!-- <span class="text-2xl font-semibold tracking-tight font-display">GolfTourney</span> -->
        </Link>

        <div
            class="relative z-10 w-full px-6 py-8 mt-6 overflow-hidden border shadow-2xl rounded-2xl border-parchment-dark bg-cream shadow-pine-deep/40 sm:max-w-md sm:px-8"
        >
            <slot />
        </div>
    </div>
</template>
