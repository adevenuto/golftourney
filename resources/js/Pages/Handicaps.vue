<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
    you: { type: Object, default: null },
    constants: {
        type: Object,
        default: () => ({ recentWindow: 20, minimumRounds: 3, standardSlope: 113 }),
    },
});

// "Your numbers" callout state.
const hasIndex = computed(() => !!props.you && props.you.index !== 'N/A');
const needsRounds = computed(() => !!props.you && props.you.index === 'N/A');

// WHS short-record table (see HANDICAP_RULES.md).
const shortRecord = [
    { rounds: '3', lowest: 1, adj: '−2.0' },
    { rounds: '4', lowest: 1, adj: '−1.0' },
    { rounds: '5', lowest: 1, adj: '0' },
    { rounds: '6', lowest: 2, adj: '−1.0' },
    { rounds: '7–8', lowest: 2, adj: '0' },
    { rounds: '9–11', lowest: 3, adj: '0' },
    { rounds: '12–14', lowest: 4, adj: '0' },
    { rounds: '15–16', lowest: 5, adj: '0' },
    { rounds: '17–18', lowest: 6, adj: '0' },
    { rounds: '19', lowest: 7, adj: '0' },
    { rounds: '20', lowest: 8, adj: '0' },
];

const faqs = [
    {
        q: 'Why did my number change?',
        a: 'The old app showed a single 9-hole figure. Now you get a portable Index plus a Course Handicap that factors in the course’s slope and par — a more accurate, travel-anywhere measure.',
    },
    {
        q: 'What does a plus handicap (+2.1) mean?',
        a: 'A player better than scratch. Their Index is below zero, so we show it with a “+” — they give strokes back rather than receive them.',
    },
    {
        q: 'Which rounds count?',
        a: 'Only the lowest few of your most recent 20 (see the table above). One blow-up round barely matters — it simply won’t be among your best.',
    },
    {
        q: 'It says N/A — why?',
        a: `You need at least ${props.constants.minimumRounds} rounds before an Index can be calculated. Keep posting scores and it’ll appear.`,
    },
];
</script>

<template>
    <Head title="How Handicaps Work" />

    <AuthenticatedLayout>
        <PageHeader eyebrow="Reference" title="How Handicaps Work" max-width="5xl">
            <template #below>
                <p class="mt-4 max-w-2xl text-sm leading-relaxed text-cream/60">
                    Your handicap is really two numbers: a portable
                    <span class="text-cream">Index</span> that travels with you, and a
                    <span class="text-cream">Course Handicap</span> for wherever you happen to play.
                    Here’s how they’re built.
                </p>
            </template>
        </PageHeader>

        <div class="mx-auto max-w-5xl space-y-12 px-4 py-10 sm:px-6 lg:px-8">
            <!-- Your numbers (live callout) -->
            <section
                class="relative overflow-hidden rounded-2xl bg-pine text-cream shadow-sm"
            >
                <div
                    aria-hidden="true"
                    class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full border border-brass/15"
                ></div>
                <div class="relative p-6 sm:p-8">
                    <p class="text-xs uppercase tracking-[0.3em] text-brass-light">Your numbers</p>

                    <div v-if="hasIndex" class="mt-4 flex flex-wrap items-end gap-x-10 gap-y-4">
                        <div>
                            <p class="text-xs uppercase tracking-widest text-cream/50">Handicap Index</p>
                            <p class="font-display text-5xl font-semibold tabular-nums text-brass-light">
                                {{ you.index }}
                            </p>
                            <p class="mt-1 text-xs text-cream/50">Travels to every course.</p>
                        </div>

                        <svg class="mb-4 hidden h-6 w-6 text-cream/30 sm:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>

                        <div>
                            <p class="text-xs uppercase tracking-widest text-cream/50">
                                Course Handicap · {{ you.league }}
                            </p>
                            <p class="font-display text-5xl font-semibold tabular-nums">
                                {{ you.course_handicap ?? '—' }}
                            </p>
                            <p class="mt-1 text-xs text-cream/50">
                                Strokes you get on this {{ you.holes }}-hole course.
                            </p>
                        </div>
                    </div>

                    <p v-else-if="needsRounds" class="mt-3 max-w-xl text-sm text-cream/70">
                        You need at least
                        <span class="text-cream">{{ constants.minimumRounds }}</span> rounds before we
                        can calculate your Index. Keep posting scores and it’ll show up here.
                    </p>

                    <p v-else class="mt-3 max-w-xl text-sm text-cream/70">
                        Join or switch to a league to see your own Index and Course Handicap.
                    </p>
                </div>
            </section>

            <!-- Two numbers -->
            <section>
                <h2 class="font-display text-2xl font-semibold text-pine">The two numbers</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border border-parchment-dark bg-cream p-6">
                        <span class="flex h-11 w-11 items-center justify-center rounded-full bg-brass/15 text-brass-dark">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 17l6-6 4 4 7-7" />
                            </svg>
                        </span>
                        <h3 class="mt-4 font-display text-xl font-semibold text-pine">Handicap Index</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-ink/70">
                            A single, portable number for how you’re playing — the same in every
                            league and at every course. Think of it as your skill.
                        </p>
                    </div>
                    <div class="rounded-2xl border border-parchment-dark bg-cream p-6">
                        <span class="flex h-11 w-11 items-center justify-center rounded-full bg-pine/10 text-pine">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 21V5a1 1 0 011-1h11l-2 4 2 4H4" />
                            </svg>
                        </span>
                        <h3 class="mt-4 font-display text-xl font-semibold text-pine">Course Handicap</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-ink/70">
                            Your Index translated into actual strokes at one specific course,
                            adjusted for how hard it plays. It changes course to course.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Step 1: Differential -->
            <section>
                <div class="flex items-center gap-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brass font-display text-sm font-semibold text-pine-deep">1</span>
                    <h2 class="font-display text-2xl font-semibold text-pine">Each round becomes a differential</h2>
                </div>
                <p class="mt-3 max-w-2xl text-sm leading-relaxed text-ink/70">
                    Every score is turned into a <strong class="text-pine">Score Differential</strong> —
                    how good the round was, adjusted for the course’s difficulty so rounds at easy
                    and hard courses can be compared fairly.
                </p>
                <div class="mt-4 rounded-2xl border border-parchment-dark bg-cream p-6">
                    <p class="text-center font-display text-lg text-pine sm:text-xl">
                        <span class="text-brass-dark">Differential</span>
                        = (Score − Course Rating) ×
                        <span class="text-brass-dark">{{ constants.standardSlope }}</span> ÷ Slope
                    </p>
                    <p class="mt-3 text-center text-xs text-ink/50">
                        The {{ constants.standardSlope }} is the standard slope — dividing by the
                        course’s own slope levels the playing field between courses.
                    </p>
                </div>
            </section>

            <!-- Step 2: Index -->
            <section>
                <div class="flex items-center gap-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brass font-display text-sm font-semibold text-pine-deep">2</span>
                    <h2 class="font-display text-2xl font-semibold text-pine">Your best differentials make your Index</h2>
                </div>
                <p class="mt-3 max-w-2xl text-sm leading-relaxed text-ink/70">
                    We take your most recent
                    <strong class="text-pine">{{ constants.recentWindow }}</strong> rounds and average
                    your <strong class="text-pine">lowest</strong> few. Early on, with fewer rounds, we
                    use fewer of them (and a small adjustment) until your record fills out:
                </p>

                <div class="mt-4 overflow-hidden rounded-2xl border border-parchment-dark bg-cream">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-parchment/60 text-left text-xs uppercase tracking-wider text-pine">
                                <th class="px-5 py-3 font-semibold">Rounds in record</th>
                                <th class="px-5 py-3 text-center font-semibold">Lowest used</th>
                                <th class="px-5 py-3 text-center font-semibold">Adjustment</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-parchment-dark">
                            <tr v-for="row in shortRecord" :key="row.rounds" class="hover:bg-parchment/40">
                                <td class="px-5 py-2.5 tabular-nums text-ink/80">{{ row.rounds }}</td>
                                <td class="px-5 py-2.5 text-center font-display font-semibold tabular-nums text-pine">{{ row.lowest }}</td>
                                <td class="px-5 py-2.5 text-center tabular-nums text-ink/70">{{ row.adj }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="mt-3 text-xs text-ink/50">
                    Below {{ constants.minimumRounds }} rounds there’s no Index yet (it shows
                    <span class="font-medium text-ink/70">N/A</span>). Because only your lowest count, a
                    single bad round has almost no effect.
                </p>
            </section>

            <!-- 9-hole -->
            <section class="rounded-2xl border border-parchment-dark bg-parchment/50 p-6">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-pine/10 text-pine">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 21V4l11 2.5L5 9" />
                        </svg>
                    </span>
                    <h2 class="font-display text-xl font-semibold text-pine">Playing 9 holes?</h2>
                </div>
                <p class="mt-3 max-w-2xl text-sm leading-relaxed text-ink/70">
                    A 9-hole round is half a round, so its differential is
                    <strong class="text-pine">doubled</strong> to an 18-hole equivalent. That lets your
                    9-hole and 18-hole play pool into one Index on the same scale. (The Black League is a
                    9-hole course — rating 31.5, slope 104, par 33.)
                </p>
            </section>

            <!-- Step 3: Course handicap -->
            <section>
                <div class="flex items-center gap-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brass font-display text-sm font-semibold text-pine-deep">3</span>
                    <h2 class="font-display text-2xl font-semibold text-pine">Your Index becomes a Course Handicap</h2>
                </div>
                <p class="mt-3 max-w-2xl text-sm leading-relaxed text-ink/70">
                    At any given course, your Index is converted into the strokes you actually receive,
                    using that course’s slope and par:
                </p>
                <div class="mt-4 rounded-2xl border border-parchment-dark bg-cream p-6">
                    <p class="text-center font-display text-lg text-pine sm:text-xl">
                        <span class="text-brass-dark">Course Handicap</span>
                        = Index × Slope ÷ {{ constants.standardSlope }} + (Course Rating − Par)
                    </p>
                    <p class="mt-3 text-center text-xs text-ink/50">
                        On a 9-hole course, half your Index is used. A lower slope or a rating under par
                        both reduce the strokes you get.
                    </p>
                </div>
            </section>

            <!-- One index, every course -->
            <section class="overflow-hidden rounded-2xl bg-pine text-cream shadow-sm">
                <div class="p-6 sm:p-8">
                    <p class="text-xs uppercase tracking-[0.3em] text-brass-light">The big idea</p>
                    <h2 class="mt-2 font-display text-2xl font-semibold">One Index, every course</h2>
                    <p class="mt-3 max-w-2xl text-sm leading-relaxed text-cream/70">
                        Your Index is built from <span class="text-cream">all</span> your rounds — every
                        league you play and any casual rounds — not just one course. Because each round’s
                        differential already cancels out that course’s difficulty (the
                        <span class="text-cream">× 113 ÷ slope</span> step), rounds from anywhere sit on
                        the same scale and pool into a single number.
                    </p>
                    <p class="mt-3 max-w-2xl text-sm leading-relaxed text-cream/70">
                        Each league then converts that one Index into a Course Handicap for
                        <span class="text-cream">its</span> course. So a strong round anywhere lowers your
                        handicap <span class="text-cream">everywhere</span>; a harder course simply turns
                        your Index into more strokes. Rounds aren’t locked to the course you’re viewing —
                        they all count toward the same portable Index.
                    </p>
                </div>
            </section>

            <!-- Worked example -->
            <section>
                <h2 class="font-display text-2xl font-semibold text-pine">A worked example</h2>
                <p class="mt-2 text-sm text-ink/60">A Black League golfer (9 holes · rating 31.5 · slope 104 · par 33).</p>

                <ol class="mt-4 space-y-3">
                    <li class="flex items-start gap-4 rounded-2xl border border-parchment-dark bg-cream p-5">
                        <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-pine/10 font-display text-sm font-semibold text-pine">1</span>
                        <p class="text-sm text-ink/80">
                            Their best 8 nine-hole differentials average about
                            <span class="font-display font-semibold text-pine">8.4</span>.
                        </p>
                    </li>
                    <li class="flex items-start gap-4 rounded-2xl border border-parchment-dark bg-cream p-5">
                        <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-pine/10 font-display text-sm font-semibold text-pine">2</span>
                        <p class="text-sm text-ink/80">
                            Doubled to the 18-hole scale → a Handicap
                            <span class="font-display font-semibold text-pine">Index of 16.8</span>.
                        </p>
                    </li>
                    <li class="flex items-start gap-4 rounded-2xl border border-parchment-dark bg-cream p-5">
                        <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-pine/10 font-display text-sm font-semibold text-pine">3</span>
                        <p class="text-sm text-ink/80">
                            Course Handicap = round( (16.8 ÷ 2) × 104 ÷ 113 + (31.5 − 33) )
                            = round( 7.73 − 1.5 ) =
                            <span class="font-display font-semibold text-pine">6</span>.
                        </p>
                    </li>
                </ol>
                <p class="mt-3 text-xs text-ink/50">
                    So the headline 6 is lower than the old 8.4: the slope (104, below 113) and the
                    rating sitting under par both trim the strokes — that’s the course doing the
                    adjusting, not a change in skill.
                </p>
            </section>

            <!-- FAQ -->
            <section>
                <h2 class="font-display text-2xl font-semibold text-pine">Good to know</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div
                        v-for="faq in faqs"
                        :key="faq.q"
                        class="rounded-2xl border border-parchment-dark bg-cream p-5"
                    >
                        <h3 class="font-semibold text-pine">{{ faq.q }}</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-ink/70">{{ faq.a }}</p>
                    </div>
                </div>
            </section>

            <!-- Does play elsewhere count here? (the flow) -->
            <section class="rounded-2xl border border-parchment-dark bg-parchment/50 p-6 sm:p-8">
                <p class="text-xs uppercase tracking-[0.3em] text-brass-dark">The honest answer</p>
                <h2 class="mt-2 font-display text-2xl font-semibold text-pine">
                    “Do rounds from other courses count here?”
                </h2>
                <p class="mt-3 max-w-2xl text-sm leading-relaxed text-ink/70">
                    Yes — and that’s on purpose. Your handicap here isn’t built from only this
                    course’s rounds. Every round you post follows one path:
                </p>

                <!-- Flow: sources -> differentials -> one index -> this course -->
                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-stretch">
                    <!-- 1. Every round, anywhere -->
                    <div class="flex-1 rounded-xl border border-parchment-dark bg-cream p-4">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-pine/50">Every round</p>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            <span class="rounded-full bg-pine/10 px-2 py-0.5 text-xs text-pine">This league</span>
                            <span class="rounded-full bg-pine/10 px-2 py-0.5 text-xs text-pine">Other leagues</span>
                            <span class="rounded-full bg-pine/10 px-2 py-0.5 text-xs text-pine">Casual</span>
                        </div>
                        <p class="mt-2 text-xs text-ink/60">
                            Each becomes a differential, with its own course’s difficulty
                            <span class="text-pine">cancelled out</span> (× 113 ÷ slope).
                        </p>
                    </div>

                    <div class="flex items-center justify-center text-brass" aria-hidden="true">
                        <svg class="h-5 w-5 rotate-90 sm:rotate-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>

                    <!-- 2. One Index -->
                    <div class="flex-1 rounded-xl border border-brass/40 bg-pine p-4 text-cream">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-brass-light">Pooled into</p>
                        <p class="mt-1 font-display text-xl font-semibold text-cream">One Handicap Index</p>
                        <p class="mt-2 text-xs text-cream/70">
                            The lowest of your most recent 20 — the same number in every league.
                        </p>
                    </div>

                    <div class="flex items-center justify-center text-brass" aria-hidden="true">
                        <svg class="h-5 w-5 rotate-90 sm:rotate-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>

                    <!-- 3. This course's handicap -->
                    <div class="flex-1 rounded-xl border border-parchment-dark bg-cream p-4">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-pine/50">Localized here</p>
                        <p class="mt-1 font-display text-xl font-semibold text-pine">Course Handicap</p>
                        <p class="mt-2 text-xs text-ink/60">
                            Only now does <span class="text-pine">this</span> course re-enter — converting your
                            Index with its slope &amp; par.
                        </p>
                    </div>
                </div>

                <p class="mt-5 text-sm leading-relaxed text-ink/70">
                    So a great round <span class="font-medium text-pine">anywhere</span> lowers your handicap
                    <span class="font-medium text-pine">everywhere</span> — and a casual round at another course
                    will nudge your number here too. This course only decides how many strokes that one Index is
                    worth; it never restricts which rounds count.
                </p>
            </section>

            <p class="pt-2 text-center text-sm text-ink/50">
                Back to your
                <Link :href="route('golfers.index')" class="font-medium text-pine underline-offset-2 hover:underline">roster</Link>.
            </p>
        </div>
    </AuthenticatedLayout>
</template>
