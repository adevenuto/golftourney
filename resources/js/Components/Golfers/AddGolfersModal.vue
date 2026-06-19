<script setup>
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import GolferSearch from '@/Components/GolferSearch.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
});
const emit = defineEmits(['close']);

const rows = ref([]);
let keySeq = 0;

const showPaste = ref(false);
const pasteText = ref('');

const form = useForm({ golfers: [] });

const canSubmit = computed(() => rows.value.length > 0 && !form.processing);

/* ---------- helpers ---------- */
function splitName(text) {
    const parts = text.trim().split(/\s+/).filter(Boolean);
    return { first_name: parts.shift() ?? '', last_name: parts.join(' ') };
}

function addExisting(golfer) {
    if (rows.value.some((r) => r.golfer_id === golfer.id)) return;
    rows.value.push({
        key: keySeq++,
        type: 'existing',
        golfer_id: golfer.id,
        first_name: golfer.first_name,
        last_name: golfer.last_name,
        via: golfer.via,
    });
}

function addNew(text) {
    const { first_name, last_name } = splitName(text ?? '');
    rows.value.push({
        key: keySeq++,
        type: 'new',
        golfer_id: null,
        first_name,
        last_name,
        email: '',
        phone: '',
    });
}

function applyPaste() {
    pasteText.value
        .split('\n')
        .map((l) => l.trim())
        .filter(Boolean)
        .forEach((line) => addNew(line));
    pasteText.value = '';
    showPaste.value = false;
}

function removeRow(i) {
    rows.value.splice(i, 1);
}

function errorFor(i, field) {
    return form.errors[`golfers.${i}.${field}`];
}

function fullName(r) {
    return `${r.first_name} ${r.last_name}`;
}

/* ---------- submit ---------- */
function submit() {
    form.golfers = rows.value.map((r) =>
        r.type === 'existing'
            ? { golfer_id: r.golfer_id }
            : {
                  first_name: r.first_name,
                  last_name: r.last_name,
                  email: r.email || null,
                  phone: r.phone || null,
              },
    );

    form.post(route('golfers.store'), {
        preserveScroll: true,
        onSuccess: () => close(),
    });
}

function close() {
    rows.value = [];
    showPaste.value = false;
    pasteText.value = '';
    form.clearErrors();
    form.reset();
    emit('close');
}

// Clear stale state whenever the modal is reopened.
watch(
    () => props.show,
    (open) => {
        if (open) {
            rows.value = [];
            showPaste.value = false;
            pasteText.value = '';
            form.clearErrors();
        }
    },
);
</script>

<template>
    <Modal :show="show" @close="close" max-width="2xl">
        <div class="p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="font-display text-2xl font-semibold text-pine">Add golfers</h2>
                    <p class="mt-1 text-sm text-ink/60">
                        Search to reuse someone from your other leagues, or type a new name. Add as many as you like, then save them all at once.
                    </p>
                </div>
                <button
                    type="button"
                    @click="showPaste = !showPaste"
                    class="shrink-0 rounded-full border border-pine/20 px-3 py-1.5 text-xs font-medium text-pine transition hover:border-brass hover:text-brass-dark"
                >
                    {{ showPaste ? 'Hide paste' : 'Paste list' }}
                </button>
            </div>

            <!-- Search / add -->
            <div class="mt-5">
                <GolferSearch @select="addExisting" @create="addNew" />
            </div>

            <!-- Paste list -->
            <div v-if="showPaste" class="mt-3 rounded-xl border border-parchment-dark bg-parchment/40 p-3">
                <InputLabel for="paste" value="Paste names (one per line)" class="text-xs" />
                <textarea
                    id="paste"
                    v-model="pasteText"
                    rows="4"
                    placeholder="Jane Doe&#10;Mike Park&#10;Sam Lee"
                    class="mt-1 block w-full rounded-lg border-pine/20 bg-cream text-sm text-ink shadow-sm focus:border-brass focus:ring-brass"
                ></textarea>
                <div class="mt-2 flex justify-end">
                    <button
                        type="button"
                        :disabled="!pasteText.trim()"
                        @click="applyPaste"
                        class="rounded-full bg-pine px-4 py-1.5 text-xs font-medium text-cream transition hover:bg-pine-light disabled:opacity-50"
                    >
                        Add to list
                    </button>
                </div>
            </div>

            <!-- Staging list -->
            <div class="mt-5">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-pine">
                    Staged
                    <span class="text-ink/40">({{ rows.length }})</span>
                </p>

                <p v-if="!rows.length" class="rounded-xl border border-dashed border-parchment-dark px-4 py-8 text-center text-sm text-ink/40">
                    No golfers added yet. Use the search above to get started.
                </p>

                <ul v-else class="max-h-72 space-y-2 overflow-auto pr-1">
                    <li
                        v-for="(r, i) in rows"
                        :key="r.key"
                        class="rounded-xl border border-parchment-dark bg-cream p-3"
                    >
                        <!-- Existing (reused) golfer -->
                        <div v-if="r.type === 'existing'" class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate font-medium capitalize text-ink">{{ fullName(r) }}</p>
                                <p v-if="r.via" class="truncate text-xs text-ink/50">in {{ r.via }}</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <span class="rounded-full bg-brass/15 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-brass-dark">Reused</span>
                                <button type="button" @click="removeRow(i)" :aria-label="`Remove ${fullName(r)}`" class="rounded-full p-1 text-ink/40 transition hover:bg-pine/10 hover:text-ink">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- New golfer (editable) -->
                        <div v-else>
                            <div class="flex items-center justify-between gap-3">
                                <span class="rounded-full border border-pine/20 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-pine">New</span>
                                <button type="button" @click="removeRow(i)" aria-label="Remove golfer" class="rounded-full p-1 text-ink/40 transition hover:bg-pine/10 hover:text-ink">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" />
                                    </svg>
                                </button>
                            </div>
                            <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                <div>
                                    <TextInput v-model="r.first_name" type="text" placeholder="First name" class="block w-full capitalize" />
                                    <InputError :message="errorFor(i, 'first_name')" class="mt-1" />
                                </div>
                                <div>
                                    <TextInput v-model="r.last_name" type="text" placeholder="Last name" class="block w-full capitalize" />
                                    <InputError :message="errorFor(i, 'last_name')" class="mt-1" />
                                </div>
                                <div>
                                    <TextInput v-model="r.email" type="email" placeholder="Email (optional)" class="block w-full" />
                                    <InputError :message="errorFor(i, 'email')" class="mt-1" />
                                </div>
                                <div>
                                    <TextInput v-model="r.phone" type="text" placeholder="Phone (optional)" class="block w-full" />
                                    <InputError :message="errorFor(i, 'phone')" class="mt-1" />
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="close" class="rounded-full px-4 py-2 text-sm font-medium text-ink/60 transition hover:text-ink">
                    Cancel
                </button>
                <button
                    type="button"
                    :disabled="!canSubmit"
                    @click="submit"
                    class="rounded-full bg-pine px-5 py-2 text-sm font-medium text-cream transition hover:bg-pine-light disabled:opacity-50"
                >
                    Add {{ rows.length || '' }} {{ rows.length === 1 ? 'golfer' : 'golfers' }}
                </button>
            </div>
        </div>
    </Modal>
</template>
