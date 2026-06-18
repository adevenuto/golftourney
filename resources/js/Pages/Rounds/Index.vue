<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PerPageSelect from '@/Components/Table/PerPageSelect.vue';
import PageInfo from '@/Components/Table/PageInfo.vue';
import TablePager from '@/Components/Table/TablePager.vue';
import { useDataTable } from '@/composables/useDataTable';

const props = defineProps({
    golfer: { type: Object, required: true },
    rounds: { type: Array, default: () => [] },
    countingRoundIds: { type: Array, default: () => [] },
});

const page = usePage();
const isAdmin = computed(() => page.props.auth.user?.role === 'admin');

const fullName = computed(
    () => `${props.golfer.first_name} ${props.golfer.last_name}`,
);
const countingSet = computed(() => new Set(props.countingRoundIds));
const counts = (id) => countingSet.value.has(id);

/* ---------- sort toggles + pagination ---------- */
const dateDir = ref('desc');
const countsFirst = ref(false);

const orderedRounds = computed(() => {
    const dir = dateDir.value === 'asc' ? 1 : -1;
    return [...props.rounds].sort((a, b) => {
        if (countsFirst.value) {
            const ac = countingSet.value.has(a.id) ? 1 : 0;
            const bc = countingSet.value.has(b.id) ? 1 : 0;
            if (ac !== bc) return bc - ac; // counting rounds first
        }
        return (
            (new Date(a.created_at).getTime() -
                new Date(b.created_at).getTime()) *
            dir
        );
    });
});

const { perPage, perPageOptions, page: currentPage, pageCount, paginated, total, range, setPage } =
    useDataTable(() => orderedRounds.value, { initialPerPage: 25 });

function toggleDate() {
    dateDir.value = dateDir.value === 'desc' ? 'asc' : 'desc';
    setPage(1);
}
function toggleCounts() {
    countsFirst.value = !countsFirst.value;
    setPage(1);
}

// A round's date is a calendar day — handle it as a plain date string so it
// never shifts across timezones (stored UTC midnight must not display as the
// previous day for viewers behind UTC).
const today = () => {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
};
const toInputDate = (iso) => (iso ? String(iso).slice(0, 10) : '');
const displayDate = (iso) => {
    const [y, m, d] = String(iso).slice(0, 10).split('-').map(Number);
    return new Date(y, m - 1, d).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

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
const createForm = useForm({ score: '', created_at: today() });
function openCreate() {
    createForm.reset();
    createForm.created_at = today();
    createForm.clearErrors();
    showCreate.value = true;
}
function submitCreate() {
    createForm.post(route('rounds.store', props.golfer.id), {
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
</script>

<template>
    <Head :title="`${fullName} — Rounds`" />

    <AuthenticatedLayout>
        <!-- Hero -->
        <header class="border-b border-parchment-dark bg-pine text-cream">
            <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
                <Link
                    :href="route('golfers.index')"
                    class="inline-flex items-center gap-1.5 text-sm text-cream/70 transition hover:text-brass-light"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                    All golfers
                </Link>

                <div class="mt-4 flex flex-wrap items-end justify-between gap-6">
                    <div>
                        <p class="text-xs uppercase tracking-[0.35em] text-brass-light">Member</p>
                        <h1 class="mt-2 font-display text-4xl font-semibold capitalize leading-none sm:text-5xl">
                            {{ fullName }}
                        </h1>
                    </div>

                    <dl class="flex items-end gap-8">
                        <div>
                            <dt class="text-xs uppercase tracking-widest text-cream/50">Handicap</dt>
                            <dd class="font-display text-4xl font-semibold tabular-nums text-brass-light">
                                {{ golfer.handicap }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-widest text-cream/50">Rounds</dt>
                            <dd class="font-display text-4xl font-semibold tabular-nums">{{ rounds.length }}</dd>
                        </div>
                    </dl>
                </div>

                <p class="mt-4 text-sm text-cream/60">
                    Handicap is the average of the best
                    <span class="text-cream">8</span> of the last
                    <span class="text-cream">20</span> rounds — those rounds are marked
                    <span class="text-brass-light">●</span> below.
                </p>
            </div>
        </header>

        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-display text-2xl font-semibold text-pine">Round history</h2>
                <button
                    v-if="isAdmin"
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

            <div
                v-if="total > 0"
                class="mb-4 flex flex-wrap items-center justify-between gap-3"
            >
                <PerPageSelect v-model="perPage" :options="perPageOptions" />

                <div class="flex items-center gap-2 text-sm">
                    <span class="text-ink/50">Sort</span>
                    <button
                        type="button"
                        @click="toggleDate"
                        class="inline-flex items-center gap-1 rounded-full border border-pine/20 px-3 py-1.5 font-medium text-pine transition hover:border-brass"
                    >
                        Date
                        <span class="text-brass-dark">{{ dateDir === 'desc' ? '↓' : '↑' }}</span>
                    </button>
                    <button
                        type="button"
                        @click="toggleCounts"
                        :aria-pressed="countsFirst"
                        class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 font-medium transition"
                        :class="
                            countsFirst
                                ? 'border-pine bg-pine text-cream'
                                : 'border-pine/20 text-pine hover:border-brass'
                        "
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
                                :class="
                                    counts(round.id)
                                        ? 'bg-pine text-cream'
                                        : 'bg-parchment-dark text-pine/70'
                                "
                            >
                                {{ round.score }}
                            </span>
                            <div>
                                <p class="font-medium text-ink">{{ displayDate(round.created_at) }}</p>
                                <p
                                    v-if="counts(round.id)"
                                    class="mt-0.5 text-xs font-medium uppercase tracking-wider text-brass-dark"
                                >
                                    ● Counts toward handicap
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="isAdmin"
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
                        <p class="mt-1 text-sm text-ink/50">Scores entered here will set this golfer's handicap.</p>
                    </li>
                </ul>
            </div>

            <!-- Footer: page info (left) + pager (right) -->
            <div
                v-if="total > 0"
                class="mt-4 flex flex-wrap items-center justify-between gap-3"
            >
                <PageInfo :from="range.from" :to="range.to" :total="range.total" />
                <TablePager :page="currentPage" :page-count="pageCount" @update:page="setPage" />
            </div>
        </div>

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
                <h2 class="font-display text-2xl font-semibold capitalize text-pine">
                    Enter a round
                </h2>
                <p class="mt-1 text-sm text-ink/60">For {{ fullName }}.</p>

                <div class="mt-5 space-y-4">
                    <div>
                        <InputLabel for="c_score" value="Score" />
                        <TextInput id="c_score" v-model="createForm.score" type="number" min="1" max="150" class="mt-1 block w-full tabular-nums" required autofocus />
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
                <p class="mt-1 text-sm text-ink/60">Editing may change this golfer's handicap.</p>

                <div class="mt-5 space-y-4">
                    <div>
                        <InputLabel for="e_score" value="Score" />
                        <TextInput id="e_score" v-model="editForm.score" type="number" min="1" max="150" class="mt-1 block w-full tabular-nums" required />
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
                    from {{ deleting ? displayDate(deleting.created_at) : '' }}? This may change the handicap.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="showDelete = false" class="rounded-full px-4 py-2 text-sm font-medium text-ink/60 transition hover:text-ink">Cancel</button>
                    <button type="button" :disabled="deleteForm.processing" @click="submitDelete" class="rounded-full bg-red-700 px-5 py-2 text-sm font-medium text-white transition hover:bg-red-800 disabled:opacity-50">Yes, remove</button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
