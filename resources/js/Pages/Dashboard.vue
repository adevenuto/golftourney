<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import CourseSearch from '@/Components/CourseSearch.vue';
import PageHeader from '@/Components/PageHeader.vue';

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

// Clicking a league card opens it: switch to it if needed, then land on the roster.
function openLeague(league) {
    if (league.is_current) {
        router.visit(route('golfers.index'));
    } else {
        router.post(route('leagues.switch', league.id), { enter: true });
    }
}

/* ---------- rename ---------- */
const showRename = ref(false);
const renaming = ref(null);
const renameForm = useForm({ name: '' });
function openRename(league) {
    renaming.value = league;
    renameForm.clearErrors();
    renameForm.name = league.name;
    showRename.value = true;
}
function submitRename() {
    renameForm.patch(route('leagues.update', renaming.value.id), {
        preserveScroll: true,
        onSuccess: () => (showRename.value = false),
    });
}

/* ---------- delete ---------- */
const showDelete = ref(false);
const deleting = ref(null);
const deleteForm = useForm({});
function openDelete(league) {
    deleting.value = league;
    showDelete.value = true;
}
function submitDelete() {
    deleteForm.delete(route('leagues.destroy', deleting.value.id), {
        preserveScroll: true,
        onSuccess: () => (showDelete.value = false),
    });
}
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <PageHeader eyebrow="Dashboard" title="Your leagues" max-width="5xl" />

        <div class="max-w-5xl px-4 py-8 mx-auto space-y-10 sm:px-6 lg:px-8">
            <!-- Leagues -->
            <section>
                <h2 class="mb-4 text-2xl font-semibold font-display text-pine">Leagues</h2>

                <div v-if="leagues.length" class="grid gap-3 sm:grid-cols-2">
                    <div
                        v-for="league in leagues"
                        :key="league.id"
                        role="button"
                        tabindex="0"
                        @click="openLeague(league)"
                        @keydown.enter="openLeague(league)"
                        @keydown.space.prevent="openLeague(league)"
                        :aria-label="`Open ${league.name}`"
                        class="flex flex-col gap-3 p-5 transition border shadow-sm cursor-pointer group rounded-2xl border-parchment-dark bg-cream hover:border-brass hover:shadow-md focus:outline-none focus:ring-2 focus:ring-brass focus:ring-offset-2 focus:ring-offset-parchment lg:flex-row lg:items-center lg:justify-between"
                    >
                        <div class="min-w-0">
                            <p class="text-lg font-semibold truncate transition font-display text-pine group-hover:text-brass-dark">
                                {{ league.name }}
                            </p>
                            <p v-if="league.club_name" class="mt-0.5 text-sm truncate text-ink/70">
                                {{ league.club_name }}
                            </p>
                            <p
                                v-if="league.course_name && league.course_name !== league.club_name"
                                class="text-xs truncate text-ink/50"
                            >
                                {{ league.course_name }}
                            </p>
                            <p class="mt-0.5 text-xs text-ink/50">
                                {{ league.golfers_count }} golfers
                                · rating {{ league.course_rating }} / slope {{ league.slope_rating }}
                            </p>
                        </div>
                        <div class="flex items-center justify-start gap-2 shrink-0">
                            <button
                                v-if="league.role === 'admin'"
                                type="button"
                                @click.stop="openRename(league)"
                                :aria-label="`Rename ${league.name}`"
                                class="rounded-full p-1.5 text-pine/60 transition hover:bg-pine/10 hover:text-pine"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.12 2.12 0 013 3L7 19l-4 1 1-4 12.5-12.5z" />
                                </svg>
                            </button>
                            <button
                                v-if="league.role === 'admin'"
                                type="button"
                                @click.stop="openDelete(league)"
                                :aria-label="`Delete ${league.name}`"
                                class="rounded-full p-1.5 text-red-700/70 transition hover:bg-red-700/10 hover:text-red-700"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6M14 11v6M5 7l1 13a2 2 0 002 2h8a2 2 0 002-2l1-13M9 7V4h6v3" />
                                </svg>
                            </button>
                            <span
                                v-if="league.is_current"
                                class="px-3 py-1 text-xs font-medium rounded-full bg-pine text-cream"
                            >
                                Current
                            </span>
                            <svg
                                class="h-5 w-5 text-pine/40 transition group-hover:translate-x-0.5 group-hover:text-brass"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                                aria-hidden="true"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </div>
                <p v-else class="px-5 py-8 text-sm text-center border rounded-2xl border-parchment-dark bg-cream text-ink/50">
                    You're not in any leagues yet — create one below.
                </p>
            </section>

            <!-- Create a league -->
            <section>
                <h2 class="mb-4 text-2xl font-semibold font-display text-pine">Create a league</h2>

                <form
                    @submit.prevent="submit"
                    class="p-6 space-y-5 border shadow-sm rounded-2xl border-parchment-dark bg-cream sm:p-8"
                >
                    <div>
                        <InputLabel for="l_name" value="League name" />
                        <TextInput id="l_name" v-model="form.name" type="text" class="block w-full mt-1" required />
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
                                class="block w-full mt-1 text-sm rounded-lg shadow-sm border-pine/20 bg-cream text-ink focus:border-brass focus:ring-brass"
                            >
                                <option v-if="!selectedCourse.teeboxes.length" :value="null">No tee data — enter manually</option>
                                <option v-for="tee in selectedCourse.teeboxes" :key="tee.name" :value="tee.name">
                                    {{ tee.name }}<template v-if="tee.rating"> — {{ tee.rating }}/{{ tee.slope }}</template>
                                </option>
                            </select>
                        </div>
                        <div>
                            <InputLabel value="Holes" />
                            <div class="flex gap-2 mt-1">
                                <button
                                    v-for="n in [9, 18]"
                                    :key="n"
                                    type="button"
                                    @click="holes = n"
                                    class="flex-1 px-3 py-2 text-sm font-medium transition border rounded-lg"
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
                            <TextInput id="l_recent" v-model="form.recent_rounds" type="number" min="1" max="100" class="block w-full mt-1 tabular-nums" required />
                            <InputError :message="form.errors.recent_rounds" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel for="l_counting" value="Counting" />
                            <TextInput id="l_counting" v-model="form.counting_rounds" type="number" min="1" class="block w-full mt-1 tabular-nums" required />
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

        <!-- Rename league modal -->
        <Modal :show="showRename" @close="showRename = false" max-width="md">
            <form @submit.prevent="submitRename" class="p-6">
                <h2 class="text-2xl font-semibold font-display text-pine">Rename league</h2>
                <div class="mt-4">
                    <InputLabel for="rename" value="League name" />
                    <TextInput id="rename" v-model="renameForm.name" type="text" class="block w-full mt-1" required autofocus />
                    <InputError :message="renameForm.errors.name" class="mt-1" />
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="showRename = false" class="px-4 py-2 text-sm font-medium transition rounded-full text-ink/60 hover:text-ink">
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="renameForm.processing"
                        class="px-5 py-2 text-sm font-medium transition rounded-full bg-pine text-cream hover:bg-pine-light disabled:opacity-50"
                    >
                        Save
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Delete league modal -->
        <Modal :show="showDelete" @close="showDelete = false" max-width="md">
            <div class="p-6">
                <h2 class="text-2xl font-semibold font-display text-pine">Delete league</h2>
                <p class="mt-2 text-sm text-ink/70">
                    Delete
                    <span class="font-semibold text-ink">“{{ deleting?.name }}”</span>
                    and all of its rounds? Golfers in this league are removed too —
                    unless they also belong to one of your other leagues. This can't be undone.
                </p>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="showDelete = false" class="px-4 py-2 text-sm font-medium transition rounded-full text-ink/60 hover:text-ink">
                        Cancel
                    </button>
                    <button
                        type="button"
                        :disabled="deleteForm.processing"
                        @click="submitDelete"
                        class="px-5 py-2 text-sm font-medium text-white transition bg-red-700 rounded-full hover:bg-red-800 disabled:opacity-50"
                    >
                        Yes, delete
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
