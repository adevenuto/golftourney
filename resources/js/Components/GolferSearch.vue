<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const emit = defineEmits(['select', 'create']);

const MIN_CHARS = 3;
const DEBOUNCE_MS = 300;
const MAX_DROPDOWN = 320; // matches max-h-80

const query = ref('');
const results = ref([]);
const open = ref(false);
const loading = ref(false);
const activeIndex = ref(-1);
const dropUp = ref(false);
const root = ref(null);

let debounceTimer = null;
let controller = null;

const term = computed(() => query.value.trim());
const canCreate = computed(() => term.value.length >= MIN_CHARS);
// Total navigable rows = results + the trailing "add new" row.
const rowCount = computed(() => results.value.length + (canCreate.value ? 1 : 0));

// Flip the dropdown above the input when there isn't room below.
function updatePlacement() {
    const el = root.value;
    if (! el) return;
    const rect = el.getBoundingClientRect();
    const spaceBelow = window.innerHeight - rect.bottom;
    dropUp.value = spaceBelow < MAX_DROPDOWN && rect.top > spaceBelow;
}

watch(query, (value) => {
    clearTimeout(debounceTimer);
    activeIndex.value = -1;
    const t = value.trim();

    if (t.length < MIN_CHARS) {
        results.value = [];
        open.value = false;
        loading.value = false;
        controller?.abort();
        return;
    }

    loading.value = true;
    open.value = true;
    debounceTimer = setTimeout(() => runSearch(t), DEBOUNCE_MS);
});

async function runSearch(t) {
    controller?.abort();
    controller = new AbortController();
    try {
        const res = await fetch(`/golfers/search?q=${encodeURIComponent(t)}`, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            signal: controller.signal,
        });
        const data = await res.json();
        results.value = data.golfers ?? [];
        open.value = true;
        nextTick(updatePlacement);
    } catch (error) {
        if (error.name !== 'AbortError') results.value = [];
    } finally {
        loading.value = false;
    }
}

function reset() {
    query.value = '';
    results.value = [];
    open.value = false;
    activeIndex.value = -1;
}

function pick(golfer) {
    emit('select', golfer);
    reset();
}

function createNew() {
    emit('create', term.value);
    reset();
}

// Enter / click on the active row.
function commitActive() {
    if (activeIndex.value < 0) return;
    if (activeIndex.value < results.value.length) {
        pick(results.value[activeIndex.value]);
    } else if (canCreate.value) {
        createNew();
    }
}

function fullName(g) {
    return `${g.first_name} ${g.last_name}`;
}

function onFocus() {
    if (canCreate.value) {
        open.value = true;
        nextTick(updatePlacement);
    }
}

function onKeydown(event) {
    if (event.key === 'Enter') {
        // Default the highlight to the "add new" row when nothing is active.
        event.preventDefault();
        if (activeIndex.value < 0 && canCreate.value) {
            createNew();
        } else {
            commitActive();
        }
        return;
    }
    if (! open.value || rowCount.value === 0) return;
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        activeIndex.value = Math.min(activeIndex.value + 1, rowCount.value - 1);
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        activeIndex.value = Math.max(activeIndex.value - 1, 0);
    } else if (event.key === 'Escape') {
        open.value = false;
    }
}

function onDocumentClick(event) {
    if (root.value && !root.value.contains(event.target)) open.value = false;
}

function onReposition() {
    if (open.value) updatePlacement();
}

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    window.addEventListener('resize', onReposition);
    window.addEventListener('scroll', onReposition, true);
});
onBeforeUnmount(() => {
    document.removeEventListener('click', onDocumentClick);
    window.removeEventListener('resize', onReposition);
    window.removeEventListener('scroll', onReposition, true);
    clearTimeout(debounceTimer);
    controller?.abort();
});
</script>

<template>
    <div ref="root" class="relative">
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

            <input
                v-model="query"
                type="text"
                role="combobox"
                :aria-expanded="open"
                aria-autocomplete="list"
                autocomplete="off"
                placeholder="Type a name to add or reuse a golfer…"
                class="w-full rounded-lg border-pine/20 bg-cream py-2.5 pl-10 pr-9 text-sm text-ink shadow-sm placeholder:text-pine/40 focus:border-brass focus:ring-brass"
                @focus="onFocus"
                @keydown="onKeydown"
            />

            <span v-if="loading" class="absolute right-3 top-2.5">
                <svg class="h-5 w-5 animate-spin text-brass" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z" />
                </svg>
            </span>
        </div>

        <ul
            v-if="open && canCreate"
            class="absolute left-0 right-0 z-30 max-h-80 overflow-auto rounded-xl border border-parchment-dark bg-cream py-1 shadow-xl"
            :class="dropUp ? 'bottom-full mb-1' : 'top-full mt-1'"
        >
            <li
                v-for="(g, i) in results"
                :key="g.id"
                @mousedown.prevent="pick(g)"
                @mouseenter="activeIndex = i"
                class="flex cursor-pointer items-center justify-between gap-3 px-4 py-2.5"
                :class="i === activeIndex ? 'bg-parchment' : ''"
            >
                <span class="min-w-0">
                    <span class="block truncate font-medium capitalize text-ink">{{ fullName(g) }}</span>
                    <span class="block truncate text-xs text-ink/50">
                        <span v-if="g.email">{{ g.email }}</span>
                        <span v-if="g.via"> · in {{ g.via }}</span>
                    </span>
                </span>
                <span class="shrink-0 rounded-full bg-brass/15 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-brass-dark">
                    Reuse
                </span>
            </li>

            <!-- Always-present "add new" row -->
            <li
                @mousedown.prevent="createNew"
                @mouseenter="activeIndex = results.length"
                class="flex cursor-pointer items-center gap-2 border-t border-parchment-dark px-4 py-2.5"
                :class="activeIndex === results.length ? 'bg-parchment' : ''"
            >
                <svg class="h-4 w-4 text-pine" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                </svg>
                <span class="text-sm text-ink">
                    Add new golfer: <span class="font-medium capitalize">“{{ term }}”</span>
                </span>
            </li>
        </ul>
    </div>
</template>
