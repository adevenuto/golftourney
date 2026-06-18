<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
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
        <!-- Hero -->
        <header class="border-b border-parchment-dark bg-pine text-cream">
            <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
                <p class="text-xs uppercase tracking-[0.35em] text-brass-light">
                    Account
                </p>
                <h1 class="mt-3 font-display text-4xl font-semibold capitalize leading-none sm:text-5xl">
                    {{ user.first_name }} {{ user.last_name }}
                </h1>
                <p class="mt-3 text-sm text-cream/60">{{ user.email }}</p>
            </div>
        </header>

        <div class="mx-auto max-w-3xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-parchment-dark bg-cream p-6 shadow-sm sm:p-8">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    class="max-w-xl"
                />
            </div>

            <div class="rounded-2xl border border-parchment-dark bg-cream p-6 shadow-sm sm:p-8">
                <UpdatePasswordForm class="max-w-xl" />
            </div>

            <div class="rounded-2xl border border-parchment-dark bg-cream p-6 shadow-sm sm:p-8">
                <DeleteUserForm class="max-w-xl" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
