<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
    game: { type: Object, required: true },
});

const form = useForm({ join_code: props.game.join_code });
const join = () => form.post(route('games.join'));
</script>

<template>
    <Head title="Join game" />

    <AuthenticatedLayout>
        <PageHeader eyebrow="Live game" title="You're invited" max-width="3xl" />

        <div class="max-w-3xl px-4 py-10 mx-auto sm:px-6">
            <div class="p-8 text-center border rounded-2xl border-parchment-dark bg-cream">
                <p class="text-sm text-ink/60">
                    <span class="font-medium capitalize text-ink">{{ game.host }}</span> invited you to a game at
                </p>
                <h2 class="mt-1 text-2xl font-semibold capitalize font-display text-pine">{{ game.course_name }}</h2>
                <p class="mt-1 text-sm text-ink/50">
                    {{ game.teebox ? game.teebox + ' tee · ' : '' }}{{ game.holes }} holes ·
                    {{ game.players_count }} player{{ game.players_count === 1 ? '' : 's' }} so far
                </p>

                <div class="flex flex-col justify-center gap-3 mt-8 sm:flex-row">
                    <button
                        type="button"
                        :disabled="form.processing"
                        @click="join"
                        class="px-6 py-2.5 text-sm font-medium transition rounded-full bg-pine text-cream hover:bg-pine-light disabled:opacity-50"
                    >
                        Join game
                    </button>
                    <Link :href="route('games.index')" class="px-6 py-2.5 text-sm font-medium transition rounded-full text-ink/60 hover:text-ink">
                        Not now
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
