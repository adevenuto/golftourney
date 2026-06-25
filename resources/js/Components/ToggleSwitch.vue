<script setup>
// A small pine on/off switch bound with v-model.
defineProps({
    disabled: { type: Boolean, default: false },
    label: { type: String, default: 'Toggle' },
});
const model = defineModel({ type: Boolean, default: false });
</script>

<template>
    <button
        type="button"
        role="switch"
        :aria-checked="model"
        :aria-label="label"
        :disabled="disabled"
        @click="model = !model"
        :class="[
            'relative inline-flex h-5 w-10 shrink-0 items-center rounded-full transition disabled:opacity-50',
            model ? 'bg-pine' : 'bg-pine/20',
        ]"
    >
        <span
            :class="[
                'inline-block h-4 w-4 transform rounded-full bg-cream shadow transition',
                model ? 'translate-x-5' : 'translate-x-1',
            ]"
        ></span>
    </button>
</template>
