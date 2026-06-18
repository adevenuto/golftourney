<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import CourseSearch from '@/Components/CourseSearch.vue';

defineProps({
    leagues: { type: Array, default: () => [] },
});

const selectedCourse = ref(null);
const holes = ref(18);

const form = useForm({
    name: '',
    course_id: null,
    teebox: null,
    course_rating: '',
    slope_rating: '',
    recent_rounds: 20,
    counting_rounds: 8,
});

const currentTee = computed(
    () =>
        selectedCourse.value?.teeboxes?.find((t) => t.name === form.teebox) ??
        null,
);
const courseHoles = computed(() => selectedCourse.value?.holes ?? null);

// Rating/slope are driven by the chosen teebox when a catalog course is
// selected; they're only hand-editable for manual entry.
const locked = computed(() => !!selectedCourse.value);

const round1 = (n) => Math.round(n * 10) / 10;

function applyTee() {
    const tee = currentTee.value;
    if (!tee) return;
    form.slope_rating = tee.slope ?? '';

    if (tee.rating == null) {
        form.course_rating = '';
        return;
    }

    // The teebox rating is for the course's own hole count; only convert when
    // the league plays a different number of holes than the course.
    let rating = tee.rating;
    if (courseHoles.value && courseHoles.value !== holes.value) {
        rating = holes.value === 9 ? rating / 2 : rating * 2;
    }
    form.course_rating = round1(rating);
}
watch(holes, applyTee);

function onCourseSelect(course) {
    selectedCourse.value = course;
    form.course_id = course?.id ?? null;
    form.teebox = course?.teeboxes?.[0]?.name ?? null;

    // Auto-select 9/18 from the course's layout data.
    if (course?.holes === 9 || course?.holes === 18) {
        holes.value = course.holes;
    }

    if (form.teebox) {
        applyTee();
    } else {
        form.course_rating = '';
        form.slope_rating = '';
    }
}

function submit() {
    form.post(route('leagues.store'), { preserveScroll: true });
}

function switchTo(id) {
    router.post(route('leagues.switch', id), {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <header class="border-b border-parchment-dark bg-pine text-cream">
            <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
                <p class="text-xs uppercase tracking-[0.35em] text-brass-light">Dashboard</p>
                <h1 class="mt-3 font-display text-4xl font-semibold leading-none sm:text-5xl">
                    Your leagues
                </h1>
            </div>
        </header>

        <div class="mx-auto max-w-5xl space-y-10 px-4 py-8 sm:px-6 lg:px-8">
            <!-- Leagues -->
            <section>
                <h2 class="mb-4 font-display text-2xl font-semibold text-pine">Leagues</h2>

                <div v-if="leagues.length" class="grid gap-3 sm:grid-cols-2">
                    <div
                        v-for="league in leagues"
                        :key="league.id"
                        class="flex items-center justify-between gap-3 rounded-2xl border border-parchment-dark bg-cream p-5 shadow-sm"
                    >
                        <div class="min-w-0">
                            <p class="truncate font-display text-lg font-semibold text-pine">
                                {{ league.name }}
                            </p>
                            <p class="mt-0.5 text-xs text-ink/50">
                                <span class="uppercase tracking-wider text-brass-dark">{{ league.role }}</span>
                                · {{ league.golfers_count }} golfers
                                · rating {{ league.course_rating }} / slope {{ league.slope_rating }}
                            </p>
                        </div>
                        <span
                            v-if="league.is_current"
                            class="shrink-0 rounded-full bg-pine px-3 py-1 text-xs font-medium text-cream"
                        >
                            Current
                        </span>
                        <button
                            v-else
                            type="button"
                            @click="switchTo(league.id)"
                            class="shrink-0 rounded-full border border-pine/20 px-4 py-1.5 text-sm font-medium text-pine transition hover:border-brass hover:text-brass-dark"
                        >
                            Switch
                        </button>
                    </div>
                </div>
                <p v-else class="rounded-2xl border border-parchment-dark bg-cream px-5 py-8 text-center text-sm text-ink/50">
                    You're not in any leagues yet — create one below.
                </p>
            </section>

            <!-- Create a league -->
            <section>
                <h2 class="mb-4 font-display text-2xl font-semibold text-pine">Create a league</h2>

                <form
                    @submit.prevent="submit"
                    class="space-y-5 rounded-2xl border border-parchment-dark bg-cream p-6 shadow-sm sm:p-8"
                >
                    <div>
                        <InputLabel for="l_name" value="League name" />
                        <TextInput id="l_name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.name" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel value="Course" />
                        <CourseSearch class="mt-1" @select="onCourseSelect" />
                        <p class="mt-1 text-xs text-ink/50">
                            Search the catalog, or leave blank and enter the rating/slope manually.
                        </p>
                    </div>

                    <div v-if="selectedCourse" class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <InputLabel for="l_teebox" value="Teebox" />
                            <select
                                id="l_teebox"
                                v-model="form.teebox"
                                @change="applyTee"
                                class="mt-1 block w-full rounded-lg border-pine/20 bg-cream text-sm text-ink shadow-sm focus:border-brass focus:ring-brass"
                            >
                                <option v-if="!selectedCourse.teeboxes.length" :value="null">No tee data — enter manually</option>
                                <option v-for="tee in selectedCourse.teeboxes" :key="tee.name" :value="tee.name">
                                    {{ tee.name }}<template v-if="tee.rating"> — {{ tee.rating }}/{{ tee.slope }}</template>
                                </option>
                            </select>
                        </div>
                        <div>
                            <InputLabel value="Holes" />
                            <div class="mt-1 flex gap-2">
                                <button
                                    v-for="n in [9, 18]"
                                    :key="n"
                                    type="button"
                                    @click="holes = n"
                                    class="flex-1 rounded-lg border px-3 py-2 text-sm font-medium transition"
                                    :class="holes === n ? 'border-pine bg-pine text-cream' : 'border-pine/20 text-pine hover:border-brass'"
                                >
                                    {{ n }} holes
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-5 sm:grid-cols-4">
                        <div>
                            <InputLabel for="l_rating" value="Course rating" />
                            <TextInput id="l_rating" v-model="form.course_rating" type="number" step="0.1" min="0" :readonly="locked" :class="['mt-1 block w-full tabular-nums', locked ? 'cursor-not-allowed bg-parchment text-ink/60 focus:border-pine/20 focus:ring-0' : '']" required />
                            <InputError :message="form.errors.course_rating" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel for="l_slope" value="Slope" />
                            <TextInput id="l_slope" v-model="form.slope_rating" type="number" min="55" max="155" :readonly="locked" :class="['mt-1 block w-full tabular-nums', locked ? 'cursor-not-allowed bg-parchment text-ink/60 focus:border-pine/20 focus:ring-0' : '']" required />
                            <InputError :message="form.errors.slope_rating" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel for="l_recent" value="Recent rounds" />
                            <TextInput id="l_recent" v-model="form.recent_rounds" type="number" min="1" max="100" class="mt-1 block w-full tabular-nums" required />
                            <InputError :message="form.errors.recent_rounds" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel for="l_counting" value="Counting" />
                            <TextInput id="l_counting" v-model="form.counting_rounds" type="number" min="1" class="mt-1 block w-full tabular-nums" required />
                            <InputError :message="form.errors.counting_rounds" class="mt-1" />
                        </div>
                    </div>

                    <p v-if="locked" class="-mt-2 text-xs text-ink/50">
                        Rating &amp; slope come from the selected teebox. Clear the course to enter them manually.
                    </p>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-full bg-pine px-6 py-2.5 text-sm font-medium text-cream transition hover:bg-pine-light disabled:opacity-50"
                        >
                            Create league
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
