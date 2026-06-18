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

    <div class="min-h-screen bg-pine font-sans text-cream">
        <!-- Hero -->
        <section class="relative isolate overflow-hidden">
            <!-- decorative atmosphere -->
            <div
                aria-hidden="true"
                class="pointer-events-none absolute inset-0 -z-10"
            >
                <div
                    class="absolute inset-0"
                    style="background: radial-gradient(120% 90% at 80% 0%, rgba(176,141,87,0.18), transparent 55%), radial-gradient(90% 70% at 0% 100%, rgba(13,46,32,0.9), transparent 60%);"
                ></div>
                <div class="absolute -right-40 -top-40 h-[28rem] w-[28rem] rounded-full border border-brass/15"></div>
                <div class="absolute -right-24 -top-24 h-72 w-72 rounded-full border border-brass/10"></div>
                <div class="absolute -bottom-48 -left-48 h-[34rem] w-[34rem] rounded-full border border-cream/5"></div>
            </div>

            <!-- Nav -->
            <header class="relative z-10">
                <nav class="mx-auto flex max-w-7xl items-center justify-between px-6 py-6 lg:px-8">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full border border-brass/60">
                            <span class="h-2.5 w-2.5 rounded-full bg-brass"></span>
                        </span>
                        <span class="font-display text-xl font-semibold tracking-tight text-cream">
                            GolfTourney
                        </span>
                    </div>

                    <div v-if="canLogin" class="flex items-center gap-2 sm:gap-3">
                        <Link
                            :href="route('login')"
                            class="rounded-full px-4 py-2 text-sm font-medium text-cream/80 transition hover:text-cream"
                        >
                            Log in
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="rounded-full bg-brass px-4 py-2 text-sm font-medium text-pine-deep transition hover:bg-brass-light"
                        >
                            Register
                        </Link>
                    </div>
                </nav>
            </header>

            <!-- Headline -->
            <div class="mx-auto max-w-7xl px-6 pb-28 pt-20 sm:pt-28 lg:px-8 lg:pb-40">
                <p class="text-xs uppercase tracking-[0.35em] text-brass-light">
                    Multi-league handicap tracking
                </p>
                <h1 class="mt-5 max-w-3xl font-display text-5xl font-semibold leading-[1.05] sm:text-6xl lg:text-7xl">
                    Where every league keeps score.
                </h1>
                <p class="mt-6 max-w-xl text-lg leading-relaxed text-cream/70">
                    Create a league, track handicaps, log rounds, and keep an
                    honest roster — all in one place.
                </p>

                <div v-if="canLogin" class="mt-10 flex flex-wrap items-center gap-4">
                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="inline-flex items-center gap-2 rounded-full bg-brass px-7 py-3.5 text-sm font-semibold text-pine-deep shadow-lg shadow-pine-deep/40 transition hover:bg-brass-light"
                    >
                        Join a league
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
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
            <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">
                <div class="grid gap-px overflow-hidden rounded-2xl border border-parchment-dark bg-parchment-dark sm:grid-cols-3">
                    <div
                        v-for="feature in features"
                        :key="feature.title"
                        class="bg-cream p-8"
                    >
                        <span class="flex h-11 w-11 items-center justify-center rounded-full bg-pine/5 text-pine">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="feature.icon" />
                            </svg>
                        </span>
                        <h2 class="mt-5 font-display text-xl font-semibold text-pine">
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
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 px-6 py-8 text-sm text-cream/50 sm:flex-row lg:px-8">
                <p class="font-display text-cream/70">GolfTourney</p>
                <p>Golf league handicaps, made simple.</p>
            </div>
        </footer>
    </div>
</template>
