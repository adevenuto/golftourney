<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: { type: Boolean, default: false },
    canRegister: { type: Boolean, default: false },
});

const features = [
    {
        title: 'Live handicaps',
        body: 'Every handicap is computed from each golfer’s best recent rounds — always current.',
        icon: 'M5 13l4 4L19 7',
    },
    {
        title: 'Round history',
        body: 'Log scores in seconds and see exactly which rounds count toward each golfer’s handicap.',
        icon: 'M9 5l7 7-7 7',
    },
    {
        title: 'The full roster',
        body: 'Search, sort, and export your league’s handicap sheet to a clean PDF whenever you need it.',
        icon: 'M3 7h18M3 12h18M3 17h18',
    },
];
</script>

<template>
    <Head title="GolfTourney" />

    <div class="min-h-screen font-sans bg-pine text-cream">
        <!-- Hero -->
        <section class="relative overflow-hidden isolate">
            <!-- decorative atmosphere -->
            <div
                aria-hidden="true"
                class="absolute inset-0 pointer-events-none -z-10"
            >
                <div
                    class="absolute inset-0"
                    style="background: radial-gradient(120% 90% at 80% 0%, rgba(176,141,87,0.18), transparent 55%), radial-gradient(90% 70% at 0% 100%, rgba(13,46,32,0.9), transparent 60%);"
                ></div>
                <div class="absolute -right-40 -top-40 h-[28rem] w-[28rem] rounded-full border border-brass/15"></div>
                <div class="absolute border rounded-full -right-24 -top-24 h-72 w-72 border-brass/10"></div>
                <div class="absolute -bottom-48 -left-48 h-[34rem] w-[34rem] rounded-full border border-cream/5"></div>
            </div>

            <!-- Nav -->
            <header class="relative z-10">
                <nav class="flex items-center justify-between px-6 py-6 mx-auto max-w-7xl lg:px-8">
                    <div class="flex items-center gap-2.5">
                        <img src="/img/logo-emblem.svg?v=2" alt="GolfTourney" class="w-auto h-32" />
                        <!-- <span class="text-xl font-semibold tracking-tight font-display text-cream">
                            GolfTourney
                        </span> -->
                    </div>

                    <div v-if="canLogin" class="flex items-center gap-2 sm:gap-3">
                        <Link
                            :href="route('login')"
                            class="px-4 py-2 text-sm font-medium transition rounded-full text-cream/80 hover:text-cream"
                        >
                            Log in
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="px-4 py-2 text-sm font-medium transition rounded-full bg-brass text-pine-deep hover:bg-brass-light"
                        >
                            Register
                        </Link>
                    </div>
                </nav>
            </header>

            <!-- Headline -->
            <div class="px-6 pt-20 mx-auto max-w-7xl pb-28 sm:pt-28 lg:px-8 lg:pb-40">
                <p class="text-xs uppercase tracking-[0.35em] text-brass-light">
                    Multi-league handicap tracking
                </p>
                <h1 class="mt-5 max-w-3xl font-display text-5xl font-semibold leading-[1.05] sm:text-6xl lg:text-7xl">
                    Where every league keeps score.
                </h1>
                <p class="max-w-xl mt-6 text-lg leading-relaxed text-cream/70">
                    Create a league, track handicaps, log rounds, and keep an
                    honest roster — all in one place.
                </p>

                <div v-if="canLogin" class="flex flex-wrap items-center gap-4 mt-10">
                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="inline-flex items-center gap-2 rounded-full bg-brass px-7 py-3.5 text-sm font-semibold text-pine-deep shadow-lg shadow-pine-deep/40 transition hover:bg-brass-light"
                    >
                        Join a league
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                    <Link
                        :href="route('login')"
                        class="inline-flex items-center rounded-full border border-cream/25 px-7 py-3.5 text-sm font-semibold text-cream transition hover:border-brass hover:text-brass-light"
                    >
                        Member log in
                    </Link>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section class="bg-parchment text-ink">
            <div class="px-6 py-16 mx-auto max-w-7xl lg:px-8 lg:py-20">
                <div class="grid gap-px overflow-hidden border rounded-2xl border-parchment-dark bg-parchment-dark sm:grid-cols-3">
                    <div
                        v-for="feature in features"
                        :key="feature.title"
                        class="p-8 bg-cream"
                    >
                        <span class="flex items-center justify-center rounded-full h-11 w-11 bg-pine/5 text-pine">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="feature.icon" />
                            </svg>
                        </span>
                        <h2 class="mt-5 text-xl font-semibold font-display text-pine">
                            {{ feature.title }}
                        </h2>
                        <p class="mt-2 text-sm leading-relaxed text-ink/70">
                            {{ feature.body }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-pine-deep">
            <div class="flex flex-col items-center justify-between gap-3 px-6 py-8 mx-auto text-sm max-w-7xl text-cream/50 sm:flex-row lg:px-8">
                <p class="font-display text-cream/70">GolfTourney</p>
                <p>Golf league handicaps, made simple.</p>
            </div>
        </footer>
    </div>
</template>
