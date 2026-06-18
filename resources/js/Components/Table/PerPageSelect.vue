<script setup>
defineProps({
    modelValue: { type: Number, required: true },
    options: { type: Array, default: () => [25, 50, 75, 100] },
});
defineEmits(['update:modelValue']);
</script>

<template>
    <label class="inline-flex items-center gap-2 text-sm text-ink/60">
        <span>Show</span>
        <select
            :value="modelValue"
            @change="$emit('update:modelValue', Number($event.target.value))"
            class="rounded-lg border-pine/15 bg-cream py-1.5 pl-3 pr-8 text-sm font-medium text-ink shadow-sm focus:border-brass focus:ring-brass"
        >
            <option v-for="opt in options" :key="opt" :value="opt">{{ opt }}</option>
        </select>
        <span>per page</span>
    </label>
</template>
