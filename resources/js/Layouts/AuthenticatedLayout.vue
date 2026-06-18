<script setup>
import { ref, computed } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);

const user = computed(() => usePage().props.auth.user);

const navLinks = [
    { label: 'Dashboard', route: 'dashboard' },
    { label: 'Golfers', route: 'golfers.index' },
];

const isActive = (name) => route().current(name);
</script>

<template>
    <div class="min-h-screen bg-parchment font-sans text-ink">
        <nav class="bg-pine text-cream shadow-[0_1px_0_0_theme(colors.brass.dark)]">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex">
                        <!-- Brand -->
                        <Link
                            :href="route('golfers.index')"
                            class="flex shrink-0 items-center gap-2.5"
                        >
                            <span
                                class="flex h-7 w-7 items-center justify-center rounded-full border border-brass/60"
                            >
                                <span class="h-2.5 w-2.5 rounded-full bg-brass"></span>
                            </span>
                            <span
                                class="font-display text-xl font-semibold tracking-tight text-cream"
                            >
                                The Black League
                            </span>
                        </Link>

                        <!-- Desktop nav -->
                        <div class="hidden sm:ms-10 sm:flex sm:items-center sm:gap-1">
                            <Link
                                v-for="link in navLinks"
                                :key="link.route"
                                :href="route(link.route)"
                                class="relative px-3 py-2 text-sm font-medium tracking-wide transition"
                                :class="
                                    isActive(link.route)
                                        ? 'text-cream'
                                        : 'text-cream/60 hover:text-cream'
                                "
                            >
                                {{ link.label }}
                                <span
                                    v-if="isActive(link.route)"
                                    class="absolute inset-x-3 -bottom-px h-0.5 rounded-full bg-brass"
                                ></span>
                            </Link>
                        </div>
                    </div>

                    <!-- User menu -->
                    <div class="hidden sm:flex sm:items-center">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-full border border-cream/20 px-3 py-1.5 text-sm font-medium capitalize text-cream/90 transition hover:border-brass/60 hover:text-cream"
                                >
                                    {{ user.first_name }} {{ user.last_name }}
                                    <span
                                        class="rounded-full bg-brass/20 px-2 py-0.5 text-[10px] uppercase tracking-widest text-brass-light"
                                    >
                                        {{ user.role }}
                                    </span>
                                </button>
                            </template>
                            <template #content>
                                <DropdownLink :href="route('profile.edit')">
                                    Profile
                                </DropdownLink>
                                <DropdownLink
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                >
                                    Log Out
                                </DropdownLink>
                            </template>
                        </Dropdown>
                    </div>

                    <!-- Hamburger -->
                    <div class="-me-2 flex items-center sm:hidden">
                        <button
                            @click="showingNavigationDropdown = !showingNavigationDropdown"
                            class="inline-flex items-center justify-center rounded-md p-2 text-cream/70 transition hover:bg-pine-light hover:text-cream focus:outline-none"
                        >
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path
                                    :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16"
                                />
                                <path
                                    :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile menu -->
            <div
                :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }"
                class="bg-cream text-ink sm:hidden"
            >
                <div class="space-y-1 pb-3 pt-2">
                    <ResponsiveNavLink
                        v-for="link in navLinks"
                        :key="link.route"
                        :href="route(link.route)"
                        :active="isActive(link.route)"
                    >
                        {{ link.label }}
                    </ResponsiveNavLink>
                </div>
                <div class="border-t border-parchment-dark pb-1 pt-4">
                    <div class="px-4">
                        <div class="text-base font-medium capitalize text-ink">
                            {{ user.first_name }} {{ user.last_name }}
                        </div>
                        <div class="text-sm font-medium text-ink/60">
                            {{ user.email }}
                        </div>
                    </div>
                    <div class="mt-3 space-y-1">
                        <ResponsiveNavLink :href="route('profile.edit')">
                            Profile
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('logout')" method="post" as="button">
                            Log Out
                        </ResponsiveNavLink>
                    </div>
                </div>
            </div>
        </nav>

        <header v-if="$slots.header">
            <slot name="header" />
        </header>

        <main>
            <slot />
        </main>
    </div>
</template>
