<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import InputError from '@/Components/InputError.vue';
import StartGameModal from '@/Components/Games/StartGameModal.vue';
import GamesList from '@/Components/Games/GamesList.vue';

defineProps({
    games: { type: Array, default: () => [] },
    ongoing: { type: Object, default: null },
});

const showStart = ref(false);
const joinForm = useForm({ join_code: '' });
const join = () => joinForm.post(route('games.join'));
</script>

<template>
    <Head title="Games" />

    <AuthenticatedLayout>
        <PageHeader eyebrow="Play" title="Games" max-width="5xl">
            <template #below>
                <p class="mt-2 text-sm text-cream/60">
                    Start a live scorecard and play a round together — scores sync in real time, and each
                    player's round posts to their handicap when you finish.
                </p>
            </template>
        </PageHeader>

        <div class="max-w-5xl px-4 py-8 mx-auto sm:px-6 lg:px-8">
            <!-- A game already in progress: resume it (one game at a time). -->
            <div v-if="ongoing" class="flex flex-col gap-4 p-6 border rounded-2xl border-brass/40 bg-brass/5 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-semibold tracking-wider uppercase text-brass-dark">
                        {{ ongoing.status === 'active' ? 'Game in progress' : 'Waiting to start' }}
                    </p>
                    <h2 class="mt-1 text-lg font-semibold capitalize truncate font-display text-pine">{{ ongoing.course_name }}</h2>
                    <p class="mt-1 text-sm text-ink/60">Finish or cancel it before starting or joining another.</p>
                </div>
                <Link
                    :href="route('games.show', ongoing.id)"
                    class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-medium transition rounded-full shrink-0 bg-pine text-cream hover:bg-pine-light"
                >
                    Resume game
                </Link>
            </div>

            <!-- Start / Join -->
            <div v-else class="grid gap-4 sm:grid-cols-2">
                <div class="flex flex-col p-6 border rounded-2xl border-parchment-dark bg-cream">
                    <h2 class="text-lg font-semibold font-display text-pine">Start a game</h2>
                    <p class="flex-1 mt-1 text-sm text-ink/60">Pick a course and get a code to invite your group.</p>
                    <button
                        type="button"
                        @click="showStart = true"
                        class="mt-4 inline-flex items-center justify-center gap-1.5 rounded-full bg-pine px-5 py-2.5 text-sm font-medium text-cream transition hover:bg-pine-light"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                        New game
                    </button>
                </div>

                <div class="flex flex-col p-6 border rounded-2xl border-parchment-dark bg-cream">
                    <h2 class="text-lg font-semibold font-display text-pine">Join a game</h2>
                    <p class="mt-1 text-sm text-ink/60">Enter the code your host shared.</p>
                    <form @submit.prevent="join" class="flex gap-2 mt-4">
                        <input
                            v-model="joinForm.join_code"
                            type="text"
                            placeholder="Code"
                            maxlength="8"
                            class="w-full font-mono text-sm tracking-widest uppercase rounded-lg shadow-sm border-pine/20 bg-cream text-ink placeholder:text-pine/30 placeholder:tracking-normal focus:border-brass focus:ring-brass"
                        />
                        <button
                            type="submit"
                            :disabled="joinForm.processing || !joinForm.join_code"
                            class="rounded-full border border-pine/20 px-5 py-2.5 text-sm font-medium text-pine transition hover:border-brass disabled:opacity-50 shrink-0"
                        >
                            Join
                        </button>
                    </form>
                    <InputError :message="joinForm.errors.join_code" class="mt-1" />
                </div>
            </div>

            <!-- Your games -->
            <div class="mt-8">
                <h2 class="text-xs font-semibold tracking-wider uppercase text-pine/70">Your games</h2>
                <div class="mt-3 p-4 border rounded-2xl border-parchment-dark bg-cream">
                    <GamesList v-if="games.length" :games="games" />
                    <p v-else class="py-6 text-sm text-center text-ink/50">No games yet — start one above.</p>
                </div>
            </div>
        </div>

        <StartGameModal :show="showStart" @close="showStart = false" />
    </AuthenticatedLayout>
</template>
