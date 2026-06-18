<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const emit = defineEmits(['select']);

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
    const term = value.trim();

    // Clearing (or < min chars) closes and clears results.
    if (term.length < MIN_CHARS) {
        results.value = [];
        open.value = false;
        loading.value = false;
        controller?.abort();
        return;
    }

    loading.value = true;
    open.value = true;
    debounceTimer = setTimeout(() => runSearch(term), DEBOUNCE_MS);
});

async function runSearch(term) {
    controller?.abort();
    controller = new AbortController();
    try {
        const res = await fetch(`/courses/search?q=${encodeURIComponent(term)}`, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            signal: controller.signal,
        });
        const data = await res.json();
        results.value = data.courses ?? [];
        open.value = true;
        nextTick(updatePlacement);
    } catch (error) {
        if (error.name !== 'AbortError') results.value = [];
    } finally {
        loading.value = false;
    }
}

function choose(course) {
    emit('select', course);
    query.value = course.name;
    results.value = [];
    open.value = false;
}

function clearAll() {
    query.value = '';
    results.value = [];
    open.value = false;
    emit('select', null);
}

function onFocus() {
    if (results.value.length > 0) {
        open.value = true;
        nextTick(updatePlacement);
    }
}

function onKeydown(event) {
    if (!open.value || results.value.length === 0) return;
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        activeIndex.value = Math.min(activeIndex.value + 1, results.value.length - 1);
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        activeIndex.value = Math.max(activeIndex.value - 1, 0);
    } else if (event.key === 'Enter' && activeIndex.value >= 0) {
        event.preventDefault();
        choose(results.value[activeIndex.value]);
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
                placeholder="Search the course catalog…"
                class="w-full rounded-lg border-pine/20 bg-cream py-2.5 pl-10 pr-9 text-sm text-ink shadow-sm placeholder:text-pine/40 focus:border-brass focus:ring-brass"
                @focus="onFocus"
                @keydown="onKeydown"
            />

            <!-- spinner / clear -->
            <span v-if="loading" class="absolute right-3 top-2.5">
                <svg class="h-5 w-5 animate-spin text-brass" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z" />
                </svg>
            </span>
            <button
                v-else-if="query"
                type="button"
                aria-label="Clear search"
                class="absolute right-2.5 top-2.5 text-pine/40 transition hover:text-pine"
                @click="clearAll"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>
        </div>

        <!-- results -->
        <ul
            v-if="open"
            class="absolute left-0 right-0 z-30 max-h-80 overflow-auto rounded-xl border border-parchment-dark bg-cream py-1 shadow-xl"
            :class="dropUp ? 'bottom-full mb-1' : 'top-full mt-1'"
        >
            <li
                v-for="(course, i) in results"
                :key="course.id"
                @mousedown.prevent="choose(course)"
                @mouseenter="activeIndex = i"
                class="cursor-pointer px-4 py-2.5"
                :class="i === activeIndex ? 'bg-parchment' : ''"
            >
                <p class="font-medium text-ink">{{ course.name }}</p>
                <p class="mt-0.5 text-xs text-ink/50">
                    <span v-if="course.location">{{ course.location }}</span>
                    <span v-if="course.teeboxes.length"> · {{ course.teeboxes.length }} tee{{ course.teeboxes.length === 1 ? '' : 's' }}</span>
                    <span v-else> · no tee data</span>
                </p>
            </li>

            <li v-if="!loading && results.length === 0" class="px-4 py-3 text-sm text-ink/50">
                No courses found.
            </li>
        </ul>
    </div>
</template>
