<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';

// A small round "i" trigger that reveals slotted help content in a popover.
// Closes on outside click or Escape. Reusable wherever a field needs context.
defineProps({
    label: { type: String, default: 'More information' },
    width: { type: String, default: 'w-72' },
    align: { type: String, default: 'left' }, // 'left' | 'right'
});

const open = ref(false);
const root = ref(null);

function onDocumentClick(event) {
    if (open.value && root.value && !root.value.contains(event.target)) {
        open.value = false;
    }
}
function onKeydown(event) {
    if (event.key === 'Escape') open.value = false;
}

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    document.addEventListener('keydown', onKeydown);
});
onBeforeUnmount(() => {
    document.removeEventListener('click', onDocumentClick);
    document.removeEventListener('keydown', onKeydown);
});
</script>

<template>
    <span ref="root" class="relative inline-flex">
        <button
            type="button"
            @click.stop="open = !open"
            :aria-label="label"
            :aria-expanded="open"
            class="flex items-center justify-center w-4 h-4 rounded-full border border-pine/40 text-[10px] font-semibold leading-none text-pine/70 transition hover:border-pine hover:text-pine"
        >
            i
        </button>

        <transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                :class="[
                    'absolute top-7 z-20 rounded-xl border border-parchment-dark bg-cream p-3 text-xs leading-relaxed text-ink/70 shadow-lg',
                    width,
                    align === 'right' ? 'right-0' : 'left-0',
                ]"
            >
                <slot />
            </div>
        </transition>
    </span>
</template>
