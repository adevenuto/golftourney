<script setup>
import { computed, ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import CourseSearch from '@/Components/CourseSearch.vue';
import PerPageSelect from '@/Components/Table/PerPageSelect.vue';
import TableFooter from '@/Components/Table/TableFooter.vue';
import { useDataTable } from '@/composables/useDataTable';
import {
    formatDate as displayDate,
    toDateInput as toInputDate,
    todayDate as today,
} from '@/utils/date';

const props = defineProps({
    rounds: { type: Array, default: () => [] },
    usedRoundIds: { type: Array, default: () => [] },
    // Whose rounds these are (target of rounds.store).
    userId: { type: Number, required: true },
    // Show the add/edit/delete controls at all.
    canManage: { type: Boolean, default: false },
    // Offer the "this league" option (admins); players log casual rounds only.
    allowLeagueRound: { type: Boolean, default: false },
    leagueName: { type: String, default: '' },
    // Optional subtitle on the create modal, e.g. "For john milne".
    forLabel: { type: String, default: '' },
});

const page = usePage();

const countingSet = computed(() => new Set(props.usedRoundIds));
const counts = (id) => countingSet.value.has(id);
const canManageRound = (round) =>
    props.canManage && (props.allowLeagueRound || round.is_casual);

/* ---------- sort toggles + pagination ---------- */
const sortBy = ref('date'); // 'date' | 'score'
const sortDir = ref('desc');
const countsFirst = ref(false);

const byDate = (a, b) =>
    new Date(a.created_at).getTime() - new Date(b.created_at).getTime();

const orderedRounds = computed(() => {
    const factor = sortDir.value === 'asc' ? 1 : -1;
    return [...props.rounds].sort((a, b) => {
        if (countsFirst.value) {
            const ac = countingSet.value.has(a.id) ? 1 : 0;
            const bc = countingSet.value.has(b.id) ? 1 : 0;
            if (ac !== bc) return bc - ac; // counting rounds first
        }
        if (sortBy.value === 'score') {
            if (a.score !== b.score) return (a.score - b.score) * factor;
            return -byDate(a, b); // tie-break: newest first
        }
        return byDate(a, b) * factor;
    });
});

const { perPage, perPageOptions, page: currentPage, pageCount, paginated, total, range, setPage } =
    useDataTable(() => orderedRounds.value, { initialPerPage: 25 });

function sortByField(field) {
    if (sortBy.value === field) {
        sortDir.value = sortDir.value === 'desc' ? 'asc' : 'desc';
    } else {
        sortBy.value = field;
        sortDir.value = 'desc';
    }
    setPage(1);
}
function toggleCounts() {
    countsFirst.value = !countsFirst.value;
    setPage(1);
}
function primarySortClass(field) {
    const base =
        'inline-flex items-center gap-1 rounded-full border px-3 py-1.5 font-medium transition';
    return sortBy.value === field
        ? `${base} border-pine bg-parchment-dark text-pine`
        : `${base} border-pine/20 text-pine hover:border-brass`;
}

/* ---------- flash toast ---------- */
const toast = ref('');
let toastTimer;
watch(
    () => page.props.flash?.success,
    (msg) => {
        if (!msg) return;
        toast.value = msg;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => (toast.value = ''), 3200);
    },
);

/* ---------- create ---------- */
const showCreate = ref(false);
const createForm = useForm({ score: '', created_at: today(), course_id: null, teebox: '' });
const where = ref('league'); // 'league' | 'casual'
const casualCourse = ref(null);
const casualTeeboxes = computed(() => casualCourse.value?.teeboxes ?? []);

function openCreate() {
    createForm.reset();
    createForm.created_at = today();
    createForm.clearErrors();
    where.value = props.allowLeagueRound ? 'league' : 'casual';
    casualCourse.value = null;
    showCreate.value = true;
}
function onCasualCourse(course) {
    casualCourse.value = course;
    createForm.course_id = course?.id ?? null;
    createForm.teebox = course?.teeboxes?.[0]?.name ?? '';
}
function submitCreate() {
    createForm
        .transform((data) =>
            where.value === 'casual'
                ? data
                : { score: data.score, created_at: data.created_at },
        )
        .post(route('rounds.store', props.userId), {
            preserveScroll: true,
            onSuccess: () => (showCreate.value = false),
        });
}

/* ---------- edit ---------- */
const showEdit = ref(false);
const editing = ref(null);
const editForm = useForm({ score: '', created_at: '' });
function openEdit(round) {
    editing.value = round;
    editForm.clearErrors();
    editForm.score = round.score;
    editForm.created_at = toInputDate(round.created_at);
    showEdit.value = true;
}
function submitEdit() {
    editForm.put(route('rounds.update', editing.value.id), {
        preserveScroll: true,
        onSuccess: () => (showEdit.value = false),
    });
}

/* ---------- delete ---------- */
const showDelete = ref(false);
const deleting = ref(null);
const deleteForm = useForm({});
function openDelete(round) {
    deleting.value = round;
    showDelete.value = true;
}
function submitDelete() {
    deleteForm.delete(route('rounds.destroy', deleting.value.id), {
        preserveScroll: true,
        onSuccess: () => (showDelete.value = false),
    });
}

defineExpose({ openCreate });
</script>

<template>
    <div>
        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-display text-2xl font-semibold text-pine">Round history</h2>
            <button
                v-if="canManage"
                type="button"
                @click="openCreate"
                class="inline-flex items-center gap-2 rounded-full bg-pine px-5 py-2.5 text-sm font-medium text-cream shadow-sm transition hover:bg-pine-light focus:outline-none focus:ring-2 focus:ring-brass focus:ring-offset-2 focus:ring-offset-parchment"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                </svg>
                Enter a round
            </button>
        </div>

        <div v-if="total > 0" class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <PerPageSelect v-model="perPage" :options="perPageOptions" />

            <div class="flex flex-wrap items-center gap-2 text-sm">
                <span class="text-ink/50">Sort</span>
                <button type="button" @click="sortByField('date')" :class="primarySortClass('date')">
                    Date
                    <span v-if="sortBy === 'date'" class="text-brass-dark">{{ sortDir === 'desc' ? '↓' : '↑' }}</span>
                </button>
                <button type="button" @click="sortByField('score')" :class="primarySortClass('score')">
                    Score
                    <span v-if="sortBy === 'score'" class="text-brass-dark">{{ sortDir === 'desc' ? '↓' : '↑' }}</span>
                </button>
                <button
                    type="button"
                    @click="toggleCounts"
                    :aria-pressed="countsFirst"
                    class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 font-medium transition"
                    :class="countsFirst ? 'border-pine bg-pine text-cream' : 'border-pine/20 text-pine hover:border-brass'"
                >
                    <span :class="countsFirst ? 'text-brass-light' : 'text-brass'">●</span>
                    Counts first
                </button>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-parchment-dark bg-cream shadow-sm">
            <ul role="list" class="divide-y divide-parchment-dark">
                <li
                    v-for="round in paginated"
                    :key="round.id"
                    class="group flex items-center justify-between gap-4 px-5 py-4 transition hover:bg-parchment/50"
                >
                    <div class="flex items-center gap-4">
                        <span
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full font-display text-lg font-semibold tabular-nums"
                            :class="counts(round.id) ? 'bg-pine text-cream' : 'bg-parchment-dark text-pine/70'"
                        >
                            {{ round.score }}
                        </span>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-medium text-ink">{{ displayDate(round.created_at) }}</p>
                                <span
                                    v-if="round.origin"
                                    class="rounded-full bg-parchment-dark px-2 py-0.5 text-[11px] font-medium text-pine/70"
                                >
                                    {{ round.origin }}
                                </span>
                            </div>
                            <p
                                v-if="counts(round.id)"
                                class="mt-0.5 text-xs font-medium uppercase tracking-wider text-brass-dark"
                            >
                                ● Counts toward handicap
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="canManageRound(round)"
                        class="flex items-center gap-1 opacity-60 transition group-hover:opacity-100"
                    >
                        <button
                            type="button"
                            @click="openEdit(round)"
                            class="rounded-full p-1.5 text-pine transition hover:bg-pine/10"
                            :aria-label="`Edit round of ${round.score}`"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.12 2.12 0 013 3L7 19l-4 1 1-4 12.5-12.5z" />
                            </svg>
                        </button>
                        <button
                            type="button"
                            @click="openDelete(round)"
                            class="rounded-full p-1.5 text-red-700 transition hover:bg-red-700/10"
                            :aria-label="`Delete round of ${round.score}`"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6M14 11v6M5 7l1 13a2 2 0 002 2h8a2 2 0 002-2l1-13M9 7V4h6v3" />
                            </svg>
                        </button>
                    </div>
                </li>

                <li v-if="total === 0" class="px-5 py-16 text-center">
                    <p class="font-display text-xl text-pine/70">No rounds yet</p>
                    <p class="mt-1 text-sm text-ink/50">Scores entered here set the handicap.</p>
                </li>
            </ul>
        </div>

        <TableFooter
            v-if="total > 0"
            :range="range"
            :page="currentPage"
            :page-count="pageCount"
            @update:page="setPage"
        />

        <!-- Toast -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="translate-y-2 opacity-0"
            leave-active-class="transition duration-200 ease-in"
            leave-to-class="translate-y-2 opacity-0"
        >
            <div
                v-if="toast"
                class="fixed bottom-6 left-1/2 z-50 -translate-x-1/2 rounded-full bg-pine px-5 py-2.5 text-sm font-medium text-cream shadow-lg"
            >
                {{ toast }}
            </div>
        </Transition>

        <!-- Create modal -->
        <Modal :show="showCreate" @close="showCreate = false" max-width="md">
            <form @submit.prevent="submitCreate" class="p-6">
                <h2 class="font-display text-2xl font-semibold capitalize text-pine">Enter a round</h2>
                <p v-if="forLabel" class="mt-1 text-sm text-ink/60">{{ forLabel }}.</p>

                <div class="mt-5 space-y-4">
                    <!-- Where: this league vs another (casual) course (admins only) -->
                    <div v-if="allowLeagueRound">
                        <InputLabel value="Where" />
                        <div class="mt-1 grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                @click="where = 'league'"
                                class="rounded-lg border px-3 py-2 text-sm font-medium transition"
                                :class="where === 'league' ? 'border-pine bg-pine text-cream' : 'border-pine/20 text-pine hover:border-brass'"
                            >
                                {{ leagueName }}
                            </button>
                            <button
                                type="button"
                                @click="where = 'casual'"
                                class="rounded-lg border px-3 py-2 text-sm font-medium transition"
                                :class="where === 'casual' ? 'border-pine bg-pine text-cream' : 'border-pine/20 text-pine hover:border-brass'"
                            >
                                Another course
                            </button>
                        </div>
                    </div>

                    <!-- Casual: pick a course + teebox -->
                    <template v-if="where === 'casual'">
                        <div>
                            <InputLabel value="Course" />
                            <CourseSearch class="mt-1" @select="onCasualCourse" />
                            <InputError :message="createForm.errors.course_id" class="mt-1" />
                        </div>
                        <div v-if="casualCourse">
                            <InputLabel for="rh_teebox" value="Teebox" />
                            <select
                                id="rh_teebox"
                                v-model="createForm.teebox"
                                class="mt-1 block w-full rounded-lg border-pine/20 bg-cream text-sm text-ink shadow-sm focus:border-brass focus:ring-brass"
                            >
                                <option v-if="!casualTeeboxes.length" value="">No tee data</option>
                                <option v-for="tee in casualTeeboxes" :key="tee.name" :value="tee.name">
                                    {{ tee.name }}<template v-if="tee.rating"> — {{ tee.rating }}/{{ tee.slope }}</template>
                                </option>
                            </select>
                        </div>
                    </template>

                    <div>
                        <InputLabel for="rh_score" value="Score" />
                        <TextInput id="rh_score" v-model="createForm.score" type="number" min="1" max="150" class="mt-1 block w-full tabular-nums" required autofocus />
                        <InputError :message="createForm.errors.score" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Date" />
                        <VueDatePicker
                            v-model="createForm.created_at"
                            model-type="yyyy-MM-dd"
                            :enable-time-picker="false"
                            :auto-apply="true"
                            :max-date="new Date()"
                            format="yyyy-MM-dd"
                            class="mt-1"
                        />
                        <InputError :message="createForm.errors.created_at" class="mt-1" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="showCreate = false" class="rounded-full px-4 py-2 text-sm font-medium text-ink/60 transition hover:text-ink">Cancel</button>
                    <button type="submit" :disabled="createForm.processing" class="rounded-full bg-pine px-5 py-2 text-sm font-medium text-cream transition hover:bg-pine-light disabled:opacity-50">Add round</button>
                </div>
            </form>
        </Modal>

        <!-- Edit modal -->
        <Modal :show="showEdit" @close="showEdit = false" max-width="md">
            <form @submit.prevent="submitEdit" class="p-6">
                <h2 class="font-display text-2xl font-semibold text-pine">Edit round</h2>
                <p class="mt-1 text-sm text-ink/60">Editing recalculates the handicap.</p>

                <div class="mt-5 space-y-4">
                    <div>
                        <InputLabel for="rh_e_score" value="Score" />
                        <TextInput id="rh_e_score" v-model="editForm.score" type="number" min="1" max="150" class="mt-1 block w-full tabular-nums" required />
                        <InputError :message="editForm.errors.score" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Date" />
                        <VueDatePicker
                            v-model="editForm.created_at"
                            model-type="yyyy-MM-dd"
                            :enable-time-picker="false"
                            :auto-apply="true"
                            :max-date="new Date()"
                            format="yyyy-MM-dd"
                            class="mt-1"
                        />
                        <InputError :message="editForm.errors.created_at" class="mt-1" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="showEdit = false" class="rounded-full px-4 py-2 text-sm font-medium text-ink/60 transition hover:text-ink">Cancel</button>
                    <button type="submit" :disabled="editForm.processing" class="rounded-full bg-pine px-5 py-2 text-sm font-medium text-cream transition hover:bg-pine-light disabled:opacity-50">Save changes</button>
                </div>
            </form>
        </Modal>

        <!-- Delete modal -->
        <Modal :show="showDelete" @close="showDelete = false" max-width="md">
            <div class="p-6">
                <h2 class="font-display text-2xl font-semibold text-pine">Remove round</h2>
                <p class="mt-2 text-sm text-ink/70">
                    Remove the round of
                    <span class="font-semibold text-ink">{{ deleting?.score }}</span>
                    from {{ deleting ? displayDate(deleting.created_at) : '' }}? This recalculates the handicap.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="showDelete = false" class="rounded-full px-4 py-2 text-sm font-medium text-ink/60 transition hover:text-ink">Cancel</button>
                    <button type="button" :disabled="deleteForm.processing" @click="submitDelete" class="rounded-full bg-red-700 px-5 py-2 text-sm font-medium text-white transition hover:bg-red-800 disabled:opacity-50">Yes, remove</button>
                </div>
            </div>
        </Modal>
    </div>
</template>
