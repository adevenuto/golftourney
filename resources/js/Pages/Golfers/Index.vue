<script setup>
import { computed, ref, watch } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    golfers: { type: Array, default: () => [] },
});

const page = usePage();
const isAdmin = computed(() => page.props.auth.user?.role === 'admin');

/* ---------- search + sort ---------- */
const search = ref('');
const sortKey = ref('last_name');
const sortDir = ref('asc');

const sortable = [
    { key: 'last_name', label: 'Golfer' },
    { key: 'handicap', label: 'Handicap' },
    { key: 'number_of_rounds', label: 'Rounds' },
];

function toggleSort(key) {
    if (sortKey.value === key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortDir.value = 'asc';
    }
}

const numericKeys = ['handicap', 'number_of_rounds'];

const filteredGolfers = computed(() => {
    const term = search.value.trim().toLowerCase();

    const rows = props.golfers.filter((g) => {
        if (!term) return true;
        return [g.first_name, g.last_name, g.email, g.phone]
            .filter(Boolean)
            .some((v) => String(v).toLowerCase().includes(term));
    });

    const dir = sortDir.value === 'asc' ? 1 : -1;
    const key = sortKey.value;

    return [...rows].sort((a, b) => {
        if (numericKeys.includes(key)) {
            return (Number(a[key]) - Number(b[key])) * dir;
        }
        const av = `${a.last_name} ${a.first_name}`.toLowerCase();
        const bv = `${b.last_name} ${b.first_name}`.toLowerCase();
        return av.localeCompare(bv) * dir;
    });
});

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
const createForm = useForm({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
});
function submitCreate() {
    createForm.post(route('golfers.store'), {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
            showCreate.value = false;
        },
    });
}

/* ---------- edit ---------- */
const showEdit = ref(false);
const editing = ref(null);
const editForm = useForm({
    first_name: '',
    last_name: '',
    handicap: '',
    email: '',
    phone: '',
});
function openEdit(golfer) {
    editing.value = golfer;
    editForm.clearErrors();
    editForm.first_name = golfer.first_name;
    editForm.last_name = golfer.last_name;
    editForm.handicap = golfer.handicap;
    editForm.email = golfer.email ?? '';
    editForm.phone = golfer.phone ?? '';
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
</script>

<template>
    <Head title="Golfers" />

    <AuthenticatedLayout>
        <!-- Hero -->
        <header class="border-b border-parchment-dark bg-pine text-cream">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <p class="text-xs uppercase tracking-[0.35em] text-brass-light">
                    The Black League
                </p>
                <div class="mt-3 flex flex-wrap items-end justify-between gap-4">
                    <h1 class="font-display text-5xl font-semibold leading-none">
                        Golfers
                    </h1>
                    <p class="font-display text-lg text-cream/70">
                        <span class="tabular-nums text-cream">{{ golfers.length }}</span>
                        on the roster
                    </p>
                </div>
            </div>
        </header>

        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <!-- Toolbar -->
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
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
                        class="w-72 max-w-full rounded-full border-pine/15 bg-cream py-2 pl-10 pr-4 text-sm text-ink shadow-sm placeholder:text-pine/40 focus:border-brass focus:ring-brass"
                    />
                </div>

                <button
                    v-if="isAdmin"
                    type="button"
                    @click="showCreate = true"
                    class="inline-flex items-center gap-2 rounded-full bg-pine px-5 py-2.5 text-sm font-medium text-cream shadow-sm transition hover:bg-pine-light focus:outline-none focus:ring-2 focus:ring-brass focus:ring-offset-2 focus:ring-offset-parchment"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                    </svg>
                    Add a golfer
                </button>
            </div>

            <!-- Table card -->
            <div class="overflow-hidden rounded-2xl border border-parchment-dark bg-cream shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-parchment-dark text-sm">
                        <thead>
                            <tr class="bg-parchment/60 text-left">
                                <th
                                    v-for="col in sortable"
                                    :key="col.key"
                                    scope="col"
                                    class="px-5 py-3 font-sans text-xs font-semibold uppercase tracking-wider text-pine"
                                >
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 transition hover:text-brass-dark"
                                        @click="toggleSort(col.key)"
                                    >
                                        {{ col.label }}
                                        <span class="text-brass">
                                            <template v-if="sortKey === col.key">{{ sortDir === 'asc' ? '▲' : '▼' }}</template>
                                            <template v-else>↕</template>
                                        </span>
                                    </button>
                                </th>
                                <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-pine">Email</th>
                                <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-pine">Phone</th>
                                <th v-if="isAdmin" scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-pine">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-parchment-dark">
                            <tr
                                v-for="g in filteredGolfers"
                                :key="g.id"
                                class="group transition hover:bg-parchment/50"
                            >
                                <td class="px-5 py-3.5">
                                    <span class="font-medium capitalize text-ink">{{ fullName(g) }}</span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <a
                                        :href="`/rounds/${g.id}`"
                                        class="inline-flex items-center gap-1.5 rounded-full border border-brass/40 bg-brass/10 px-3 py-1 font-display text-sm font-semibold tabular-nums text-pine transition hover:border-brass hover:bg-brass/20"
                                        title="View rounds"
                                    >
                                        {{ g.handicap }}
                                        <svg class="h-3.5 w-3.5 text-brass-dark" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </td>
                                <td class="px-5 py-3.5 tabular-nums text-ink/80">{{ g.number_of_rounds }}</td>
                                <td class="px-5 py-3.5 text-ink/70">{{ g.email || '—' }}</td>
                                <td class="px-5 py-3.5 tabular-nums text-ink/70">{{ g.phone || '—' }}</td>
                                <td v-if="isAdmin" class="px-5 py-3.5">
                                    <div class="flex items-center justify-end gap-1 opacity-60 transition group-hover:opacity-100">
                                        <button
                                            type="button"
                                            @click="openEdit(g)"
                                            class="rounded-full p-1.5 text-pine transition hover:bg-pine/10"
                                            :aria-label="`Edit ${fullName(g)}`"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.12 2.12 0 013 3L7 19l-4 1 1-4 12.5-12.5z" />
                                            </svg>
                                        </button>
                                        <button
                                            type="button"
                                            @click="openDelete(g)"
                                            class="rounded-full p-1.5 text-red-700 transition hover:bg-red-700/10"
                                            :aria-label="`Delete ${fullName(g)}`"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6M14 11v6M5 7l1 13a2 2 0 002 2h8a2 2 0 002-2l1-13M9 7V4h6v3" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="filteredGolfers.length === 0">
                                <td :colspan="isAdmin ? 6 : 5" class="px-5 py-16 text-center">
                                    <p class="font-display text-xl text-pine/70">No golfers found</p>
                                    <p class="mt-1 text-sm text-ink/50">
                                        {{ search ? 'Try a different search.' : 'The roster is empty.' }}
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
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
        <Modal :show="showCreate" @close="showCreate = false">
            <form @submit.prevent="submitCreate" class="p-6">
                <h2 class="font-display text-2xl font-semibold text-pine">Add a golfer</h2>
                <p class="mt-1 text-sm text-ink/60">Add a new member to the roster.</p>

                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="c_first" value="First name" />
                        <TextInput id="c_first" v-model="createForm.first_name" type="text" class="mt-1 block w-full capitalize" required autofocus />
                        <InputError :message="createForm.errors.first_name" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="c_last" value="Last name" />
                        <TextInput id="c_last" v-model="createForm.last_name" type="text" class="mt-1 block w-full capitalize" required />
                        <InputError :message="createForm.errors.last_name" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="c_email" value="Email" />
                        <TextInput id="c_email" v-model="createForm.email" type="email" class="mt-1 block w-full" />
                        <InputError :message="createForm.errors.email" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="c_phone" value="Phone" />
                        <TextInput id="c_phone" v-model="createForm.phone" type="text" class="mt-1 block w-full" />
                        <InputError :message="createForm.errors.phone" class="mt-1" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="showCreate = false" class="rounded-full px-4 py-2 text-sm font-medium text-ink/60 transition hover:text-ink">
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="createForm.processing"
                        class="rounded-full bg-pine px-5 py-2 text-sm font-medium text-cream transition hover:bg-pine-light disabled:opacity-50"
                    >
                        Save golfer
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Edit modal -->
        <Modal :show="showEdit" @close="showEdit = false">
            <form @submit.prevent="submitEdit" class="p-6">
                <h2 class="font-display text-2xl font-semibold capitalize text-pine">
                    {{ editing ? fullName(editing) : 'Edit golfer' }}
                </h2>
                <p class="mt-1 text-sm text-ink/60">Update this golfer's details.</p>

                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="e_first" value="First name" />
                        <TextInput id="e_first" v-model="editForm.first_name" type="text" class="mt-1 block w-full capitalize" required />
                        <InputError :message="editForm.errors.first_name" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="e_last" value="Last name" />
                        <TextInput id="e_last" v-model="editForm.last_name" type="text" class="mt-1 block w-full capitalize" required />
                        <InputError :message="editForm.errors.last_name" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="e_handicap" value="Handicap" />
                        <TextInput id="e_handicap" v-model="editForm.handicap" type="number" step="0.01" min="0" class="mt-1 block w-full tabular-nums" required />
                        <InputError :message="editForm.errors.handicap" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="e_phone" value="Phone" />
                        <TextInput id="e_phone" v-model="editForm.phone" type="text" class="mt-1 block w-full" />
                        <InputError :message="editForm.errors.phone" class="mt-1" />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel for="e_email" value="Email" />
                        <TextInput id="e_email" v-model="editForm.email" type="email" class="mt-1 block w-full" />
                        <InputError :message="editForm.errors.email" class="mt-1" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="showEdit = false" class="rounded-full px-4 py-2 text-sm font-medium text-ink/60 transition hover:text-ink">
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="editForm.processing"
                        class="rounded-full bg-pine px-5 py-2 text-sm font-medium text-cream transition hover:bg-pine-light disabled:opacity-50"
                    >
                        Save changes
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Delete modal -->
        <Modal :show="showDelete" @close="showDelete = false" max-width="md">
            <div class="p-6">
                <h2 class="font-display text-2xl font-semibold text-pine">Remove golfer</h2>
                <p class="mt-2 text-sm text-ink/70">
                    Remove
                    <span class="font-semibold capitalize text-ink">{{ deleting ? fullName(deleting) : '' }}</span>
                    and all of their rounds? This can't be undone.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="showDelete = false" class="rounded-full px-4 py-2 text-sm font-medium text-ink/60 transition hover:text-ink">
                        Cancel
                    </button>
                    <button
                        type="button"
                        :disabled="deleteForm.processing"
                        @click="submitDelete"
                        class="rounded-full bg-red-700 px-5 py-2 text-sm font-medium text-white transition hover:bg-red-800 disabled:opacity-50"
                    >
                        Yes, remove
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
