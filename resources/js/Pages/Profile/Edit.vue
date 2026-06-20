<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = computed(() => usePage().props.auth.user);
</script>

<template>
    <Head title="Account" />

    <AuthenticatedLayout>
        <PageHeader
            eyebrow="Account"
            :title="`${user.first_name} ${user.last_name}`"
            max-width="5xl"
            capitalize-title
        >
            <template #below>
                <p class="mt-3 text-sm text-cream/60">{{ user.email }}</p>
            </template>
        </PageHeader>

        <div class="max-w-3xl px-4 py-8 mx-auto space-y-6 sm:px-6 lg:px-8">
            <div class="p-6 border shadow-sm rounded-2xl border-parchment-dark bg-cream sm:p-8">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    class="max-w-xl"
                />
            </div>

            <div class="p-6 border shadow-sm rounded-2xl border-parchment-dark bg-cream sm:p-8">
                <UpdatePasswordForm class="max-w-xl" />
            </div>

            <div class="p-6 border shadow-sm rounded-2xl border-parchment-dark bg-cream sm:p-8">
                <DeleteUserForm class="max-w-xl" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
