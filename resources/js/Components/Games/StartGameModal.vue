<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import CourseSearch from '@/Components/CourseSearch.vue';
import TeePicker from '@/Components/Games/TeePicker.vue';

defineProps({ show: { type: Boolean, default: false } });
const emit = defineEmits(['close']);

const course = ref(null);
const teeboxes = ref([]);
const form = useForm({ course_id: null, teebox: '' });

function onCourse(c) {
    course.value = c;
    teeboxes.value = c?.teeboxes ?? [];
    form.course_id = c?.id ?? null;
    form.teebox = teeboxes.value[0]?.name ?? '';
}

function close() {
    form.reset();
    form.clearErrors();
    course.value = null;
    teeboxes.value = [];
    emit('close');
}

function submit() {
    form.post(route('games.store'), { onSuccess: () => close() });
}
</script>

<template>
    <Modal :show="show" @close="close" max-width="md">
        <div class="rounded-lg bg-cream">
            <!-- Header band -->
            <div class="rounded-t-lg bg-pine px-6 pt-6 pb-7 text-center text-cream">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-cream/10 ring-1 ring-cream/20">
                    <svg class="h-7 w-7 text-brass-light" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v18M5 4h11l-2 3 2 3H5" /></svg>
                </span>
                <h2 class="mt-3 font-display text-2xl font-semibold">New game</h2>
                <p class="mt-0.5 text-sm text-cream/60">Pick where you're playing — invite your group next.</p>
            </div>

            <form @submit.prevent="submit" class="p-6">
                <div class="space-y-5">
                    <div>
                        <p class="mb-1.5 text-[11px] font-semibold uppercase tracking-wider text-pine/60">Course</p>
                        <CourseSearch @select="onCourse" />
                        <InputError :message="form.errors.course_id" class="mt-1" />
                    </div>

                    <div v-if="course && teeboxes.length">
                        <p class="mb-1.5 text-[11px] font-semibold uppercase tracking-wider text-pine/60">Tee</p>
                        <TeePicker v-model="form.teebox" :teeboxes="teeboxes" />
                    </div>
                    <p v-else-if="course" class="text-sm text-ink/50">This course has no tee data — pick another.</p>
                </div>

                <div class="mt-7 flex justify-end gap-3">
                    <button type="button" @click="close" class="px-4 py-2.5 text-sm font-medium text-ink/60 transition hover:text-ink">Cancel</button>
                    <button
                        type="submit"
                        :disabled="form.processing || !form.course_id || !teeboxes.length"
                        class="rounded-full bg-pine px-6 py-2.5 text-sm font-semibold text-cream transition hover:bg-pine-light disabled:opacity-50"
                    >
                        Start game
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>
