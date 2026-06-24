<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import AddGolfersModal from '@/Components/Golfers/AddGolfersModal.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PerPageSelect from '@/Components/Table/PerPageSelect.vue';
import TableFooter from '@/Components/Table/TableFooter.vue';
import { useDataTable } from '@/composables/useDataTable';

const props = defineProps({
    golfers: { type: Array, default: () => [] },
});

const page = usePage();
const isAdmin = computed(() => page.props.auth.user?.role === 'admin');

/* ---------- search + sort + pagination ---------- */
const sortable = [
    { key: 'last_name', label: 'Golfer' },
    { key: 'index_value', label: 'Index' },
    { key: 'course_handicap', label: 'Course Hcp' },
    { key: 'number_of_rounds', label: 'Rounds' },
];

const {
    search,
    sortKey,
    sortDir,
    perPage,
    perPageOptions,
    page: currentPage,
    pageCount,
    paginated,
    total,
    range,
    toggleSort,
    setPage,
} = useDataTable(() => props.golfers, {
    searchFields: ['first_name', 'last_name', 'email', 'phone'],
    sortAccessors: {
        last_name: (g) => `${g.last_name} ${g.first_name}`.toLowerCase(),
        index_value: (g) => (g.index_value ?? -Infinity),
        course_handicap: (g) => (g.course_handicap ?? -Infinity),
        number_of_rounds: (g) => Number(g.number_of_rounds),
    },
    initialSort: { key: 'number_of_rounds', dir: 'desc' },
});

// Export honours the current sort + search.
const exportUrl = computed(() =>
    route('golfers.export', {
        sort: sortKey.value,
        dir: sortDir.value,
        search: search.value || undefined,
    }),
);

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

/* ---------- edit ---------- */
const showEdit = ref(false);
const editing = ref(null);
const editForm = useForm({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    manual_handicap_index: '',
});
function openEdit(golfer) {
    editing.value = golfer;
    editForm.clearErrors();
    editForm.first_name = golfer.first_name;
    editForm.last_name = golfer.last_name;
    editForm.email = golfer.email ?? '';
    editForm.phone = golfer.phone ?? '';
    editForm.manual_handicap_index = golfer.manual_handicap_index ?? '';
    showEdit.value = true;
}
function submitEdit() {
    editForm.put(route('golfers.update', editing.value.id), {
        preserveScroll: true,
        onSuccess: () => (showEdit.value = false),
    });
}

/* ---------- delete ---------- */
const showDelete = ref(false);
const deleting = ref(null);
const deleteForm = useForm({});
function openDelete(golfer) {
    deleting.value = golfer;
    showDelete.value = true;
}
function submitDelete() {
    deleteForm.delete(route('golfers.destroy', deleting.value.id), {
        preserveScroll: true,
        onSuccess: () => (showDelete.value = false),
    });
}

const fullName = (g) => `${g.first_name} ${g.last_name}`;

/* ---------- mobile expandable rows ---------- */
const expanded = ref(new Set());
const isExpanded = (id) => expanded.value.has(id);
function toggleExpand(id) {
    const next = new Set(expanded.value);
    next.has(id) ? next.delete(id) : next.add(id);
    expanded.value = next;
}
</script>

<template>
    <Head title="Golfers" />

    <AuthenticatedLayout>
        <PageHeader
            :eyebrow="$page.props.auth.user?.current_league?.name ?? 'GolfTourney'"
            title="Golfers"
            max-width="5xl"
        >
            <template #actions>
                <p class="text-lg font-display text-cream/70">
                    <span class="tabular-nums text-cream">{{ golfers.length }}</span>
                    on the roster
                </p>
            </template>
        </PageHeader>

        <div class="max-w-5xl px-4 py-8 mx-auto sm:px-6 lg:px-8">
            <!-- Toolbar -->
            <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
                <PerPageSelect v-model="perPage" :options="perPageOptions" />

                <div class="flex flex-wrap items-center gap-3">
                    <div class="relative">
                        <svg
                            class="pointer-events-none absolute left-3 top-2.5 h-5 w-5 text-pine/40"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.3-4.3m1.8-5.2a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <label for="golfer-search" class="sr-only">Search golfers</label>
                        <input
                            id="golfer-search"
                            v-model="search"
                            type="search"
                            placeholder="Search name, email, phone…"
                            class="w-64 max-w-full py-2 pl-10 pr-4 text-sm rounded-full shadow-sm border-pine/15 bg-cream text-ink placeholder:text-pine/40 focus:border-brass focus:ring-brass"
                        />
                    </div>

                    <a
                        :href="exportUrl"
                        class="inline-flex items-center gap-2 rounded-full border border-pine/20 bg-cream px-4 py-2.5 text-sm font-medium text-pine transition hover:border-brass hover:text-brass-dark"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2" />
                        </svg>
                        Export PDF
                    </a>

                    <button
                        v-if="isAdmin"
                        type="button"
                        @click="showCreate = true"
                        class="inline-flex items-center gap-2 rounded-full bg-pine px-5 py-2.5 text-sm font-medium text-cream shadow-sm transition hover:bg-pine-light focus:outline-none focus:ring-2 focus:ring-brass focus:ring-offset-2 focus:ring-offset-parchment"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                        </svg>
                        Add golfers
                    </button>
                </div>
            </div>

            <!-- Table card (desktop / tablet) -->
            <div class="hidden overflow-hidden border shadow-sm rounded-2xl border-parchment-dark bg-cream sm:block">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm divide-y divide-parchment-dark">
                        <thead>
                            <tr class="text-left bg-parchment/60">
                                <th
                                    v-for="col in sortable"
                                    :key="col.key"
                                    scope="col"
                                    class="px-5 py-3 font-sans text-xs font-semibold tracking-wider uppercase text-pine"
                                >
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1.5 transition hover:text-brass-dark"
                                        @click="toggleSort(col.key)"
                                    >
                                        {{ col.label }}
                                        <!-- active ascending -->
                                        <svg
                                            v-if="sortKey === col.key && sortDir === 'asc'"
                                            class="h-3.5 w-3.5 text-brass"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="3"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 15l6-6 6 6" />
                                        </svg>
                                        <!-- active descending -->
                                        <svg
                                            v-else-if="sortKey === col.key"
                                            class="h-3.5 w-3.5 text-brass"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="3"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6" />
                                        </svg>
                                        <!-- unsorted -->
                                        <svg
                                            v-else
                                            class="h-3.5 w-3.5 text-brass/45"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 10l4-4 4 4M8 14l4 4 4-4" />
                                        </svg>
                                    </button>
                                </th>
                                <th scope="col" class="px-5 py-3 text-xs font-semibold tracking-wider uppercase text-pine">Email</th>
                                <th scope="col" class="px-5 py-3 text-xs font-semibold tracking-wider uppercase text-pine">Phone</th>
                                <th v-if="isAdmin" scope="col" class="px-5 py-3 text-xs font-semibold tracking-wider text-right uppercase text-pine">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-parchment-dark">
                            <tr
                                v-for="g in paginated"
                                :key="g.id"
                                class="transition group hover:bg-parchment/50"
                            >
                                <td class="px-5 py-3.5">
                                    <span class="font-medium capitalize text-ink">{{ fullName(g) }}</span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <Link
                                        :href="route('golfers.rounds', g.id)"
                                        class="inline-flex items-center gap-1.5 rounded-full border border-brass/40 bg-brass/10 px-3 py-1 font-display text-sm font-semibold tabular-nums text-pine transition hover:border-brass hover:bg-brass/20"
                                        title="View rounds"
                                    >
                                        {{ g.index }}
                                        <svg class="h-3.5 w-3.5 text-brass-dark" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </Link>
                                </td>
                                <td class="px-5 py-3.5 font-display tabular-nums text-ink/80">{{ g.course_handicap ?? '—' }}</td>
                                <td class="px-5 py-3.5 tabular-nums text-ink/80">{{ g.number_of_rounds }}</td>
                                <td class="px-5 py-3.5 text-ink/70">{{ g.email || '—' }}</td>
                                <td class="px-5 py-3.5 tabular-nums text-ink/70">{{ g.phone || '—' }}</td>
                                <td v-if="isAdmin" class="px-5 py-3.5">
                                    <div class="flex items-center justify-end gap-1 transition opacity-60 group-hover:opacity-100">
                                        <button
                                            type="button"
                                            @click="openEdit(g)"
                                            class="rounded-full p-1.5 text-pine transition hover:bg-pine/10"
                                            :aria-label="`Edit ${fullName(g)}`"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.12 2.12 0 013 3L7 19l-4 1 1-4 12.5-12.5z" />
                                            </svg>
                                        </button>
                                        <button
                                            type="button"
                                            @click="openDelete(g)"
                                            class="rounded-full p-1.5 text-red-700 transition hover:bg-red-700/10"
                                            :aria-label="`Delete ${fullName(g)}`"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6M14 11v6M5 7l1 13a2 2 0 002 2h8a2 2 0 002-2l1-13M9 7V4h6v3" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="total === 0">
                                <td :colspan="isAdmin ? 7 : 6" class="px-5 py-16 text-center">
                                    <p class="text-xl font-display text-pine/70">No golfers found</p>
                                    <p class="mt-1 text-sm text-ink/50">
                                        {{ search ? 'Try a different search.' : 'The roster is empty.' }}
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Stacked, expandable rows (mobile) -->
            <div class="space-y-3 sm:hidden">
                <div
                    v-for="g in paginated"
                    :key="g.id"
                    class="overflow-hidden border shadow-sm rounded-2xl border-parchment-dark bg-cream"
                >
                    <div class="flex items-center gap-2 px-4 py-3">
                        <button
                            type="button"
                            @click="toggleExpand(g.id)"
                            :aria-expanded="isExpanded(g.id)"
                            class="flex items-center flex-1 min-w-0 gap-2 text-left"
                        >
                            <svg
                                class="w-5 h-5 transition-transform shrink-0 text-pine/50"
                                :class="isExpanded(g.id) ? 'rotate-180' : ''"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                            <span class="min-w-0">
                                <span class="block font-medium capitalize truncate text-ink">{{ fullName(g) }}</span>
                                <span class="text-xs text-ink/50">{{ g.number_of_rounds }} rounds</span>
                            </span>
                        </button>

                        <Link
                            :href="route('golfers.rounds', g.id)"
                            class="inline-flex items-center gap-1 px-3 py-1 text-sm font-semibold border rounded-full shrink-0 border-brass/40 bg-brass/10 font-display tabular-nums text-pine"
                            title="Handicap Index"
                        >
                            {{ g.index }}
                            <svg class="h-3.5 w-3.5 text-brass-dark" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>
                    </div>

                    <div
                        v-if="isExpanded(g.id)"
                        class="px-4 py-3 text-sm border-t border-parchment-dark"
                    >
                        <dl class="space-y-2">
                            <div class="flex justify-between gap-3">
                                <dt class="text-ink/50">Course Handicap</dt>
                                <dd class="font-display tabular-nums text-ink/80">{{ g.course_handicap ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-ink/50">Email</dt>
                                <dd class="min-w-0 truncate text-ink/80">{{ g.email || '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-ink/50">Phone</dt>
                                <dd class="tabular-nums text-ink/80">{{ g.phone || '—' }}</dd>
                            </div>
                        </dl>

                        <div v-if="isAdmin" class="flex gap-2 mt-4">
                            <button
                                type="button"
                                @click="openEdit(g)"
                                class="inline-flex items-center gap-1.5 rounded-full border border-pine/20 px-3 py-1.5 text-sm font-medium text-pine transition hover:border-brass"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.12 2.12 0 013 3L7 19l-4 1 1-4 12.5-12.5z" />
                                </svg>
                                Edit
                            </button>
                            <button
                                type="button"
                                @click="openDelete(g)"
                                class="inline-flex items-center gap-1.5 rounded-full border border-red-700/30 px-3 py-1.5 text-sm font-medium text-red-700 transition hover:border-red-700"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6M14 11v6M5 7l1 13a2 2 0 002 2h8a2 2 0 002-2l1-13M9 7V4h6v3" />
                                </svg>
                                Delete
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    v-if="total === 0"
                    class="px-4 py-12 text-center border rounded-2xl border-parchment-dark bg-cream"
                >
                    <p class="text-lg font-display text-pine/70">No golfers found</p>
                    <p class="mt-1 text-sm text-ink/50">
                        {{ search ? 'Try a different search.' : 'The roster is empty.' }}
                    </p>
                </div>
            </div>

            <!-- Sticky footer: page info (left) + pager (right) -->
            <TableFooter
                v-if="total > 0"
                :range="range"
                :page="currentPage"
                :page-count="pageCount"
                @update:page="setPage"
            />
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

        <!-- Add golfers modal -->
        <AddGolfersModal :show="showCreate" @close="showCreate = false" />

        <!-- Edit modal -->
        <Modal :show="showEdit" @close="showEdit = false">
            <form @submit.prevent="submitEdit" class="p-6">
                <h2 class="text-2xl font-semibold capitalize font-display text-pine">
                    {{ editing ? fullName(editing) : 'Edit golfer' }}
                </h2>
                <p class="mt-1 text-sm text-ink/60">Update this golfer's details.</p>

                <div class="grid grid-cols-1 gap-4 mt-5 sm:grid-cols-2">
                    <div>
                        <InputLabel for="e_first" value="First name" />
                        <TextInput id="e_first" v-model="editForm.first_name" type="text" class="block w-full mt-1 capitalize" required />
                        <InputError :message="editForm.errors.first_name" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="e_last" value="Last name" />
                        <TextInput id="e_last" v-model="editForm.last_name" type="text" class="block w-full mt-1 capitalize" required />
                        <InputError :message="editForm.errors.last_name" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="e_phone" value="Phone" />
                        <TextInput id="e_phone" v-model="editForm.phone" type="text" class="block w-full mt-1" />
                        <InputError :message="editForm.errors.phone" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="e_email" value="Email" />
                        <TextInput id="e_email" v-model="editForm.email" type="email" class="block w-full mt-1" />
                        <InputError :message="editForm.errors.email" class="mt-1" />
                    </div>
                </div>

                <div class="mt-4">
                    <InputLabel for="e_index" value="Established Index (USGA)" />
                    <TextInput
                        id="e_index"
                        v-model="editForm.manual_handicap_index"
                        type="number"
                        step="0.1"
                        min="-9.9"
                        max="54.0"
                        class="block w-full mt-1 tabular-nums"
                        placeholder="e.g. 12.3"
                    />
                    <p class="mt-1 text-xs text-ink/50">
                        Overrides the computed index. Leave blank to use the index calculated from rounds.
                    </p>
                    <InputError :message="editForm.errors.manual_handicap_index" class="mt-1" />
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="showEdit = false" class="px-4 py-2 text-sm font-medium transition rounded-full text-ink/60 hover:text-ink">
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="editForm.processing"
                        class="px-5 py-2 text-sm font-medium transition rounded-full bg-pine text-cream hover:bg-pine-light disabled:opacity-50"
                    >
                        Save changes
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Delete modal -->
        <Modal :show="showDelete" @close="showDelete = false" max-width="md">
            <div class="p-6">
                <h2 class="text-2xl font-semibold font-display text-pine">Remove golfer</h2>
                <p class="mt-2 text-sm text-ink/70">
                    Remove
                    <span class="font-semibold capitalize text-ink">{{ deleting ? fullName(deleting) : '' }}</span>
                    and all of their rounds? This can't be undone.
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
                        Yes, remove
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
