<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import CourseSearch from '@/Components/CourseSearch.vue';

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
        <form @submit.prevent="submit" class="p-6">
            <h2 class="text-2xl font-semibold font-display text-pine">Start a game</h2>
            <p class="mt-1 text-sm text-ink/60">Pick where you're playing — you'll get a code to invite others once you're in.</p>

            <div class="mt-5 space-y-4">
                <div>
                    <InputLabel value="Course" />
                    <CourseSearch class="mt-1" @select="onCourse" />
                    <InputError :message="form.errors.course_id" class="mt-1" />
                </div>

                <div v-if="course">
                    <InputLabel for="sg_tee" value="Teebox" />
                    <select
                        id="sg_tee"
                        v-model="form.teebox"
                        class="block w-full mt-1 text-sm rounded-lg shadow-sm border-pine/20 bg-cream text-ink focus:border-brass focus:ring-brass"
                    >
                        <option v-if="!teeboxes.length" value="">No tee data</option>
                        <option v-for="t in teeboxes" :key="t.name" :value="t.name">
                            {{ t.name }}<template v-if="t.rating"> — {{ t.rating }}/{{ t.slope }}</template>
                        </option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button type="button" @click="close" class="px-4 py-2 text-sm font-medium transition rounded-full text-ink/60 hover:text-ink">
                    Cancel
                </button>
                <button
                    type="submit"
                    :disabled="form.processing || !form.course_id"
                    class="px-5 py-2 text-sm font-medium transition rounded-full bg-pine text-cream hover:bg-pine-light disabled:opacity-50"
                >
                    Start game
                </button>
            </div>
        </form>
    </Modal>
</template>
