<script setup>
import { ref, computed } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);

const user = computed(() => usePage().props.auth.user);
const leagues = computed(() => user.value?.leagues ?? []);
const currentLeagueId = computed(() => user.value?.current_league?.id ?? null);

const navLinks = [
    { label: 'Dashboard', route: 'dashboard' },
    { label: 'Golfers', route: 'golfers.index' },
    { label: 'Profile', route: 'profile.edit' },
];

const isActive = (name) => route().current(name);

function switchLeague(id) {
    if (id !== currentLeagueId.value) {
        router.post(route('leagues.switch', id), {}, { preserveScroll: true });
    }
}
</script>

<template>
    <div class="min-h-screen font-sans bg-parchment text-ink">
        <nav class="bg-pine text-cream shadow-[0_1px_0_0_theme(colors.brass.dark)]">
            <div class="max-w-5xl px-4 mx-auto sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <!-- Brand -->
                        <Link
                            :href="route('golfers.index')"
                            class="flex shrink-0 items-center gap-2.5"
                        >
                            <img
                                src="/img/logo-emblem.svg?v=2"
                                alt="GolfTourney"
                                class="w-auto h-14"
                            />
                            <span
                                class="text-xl font-semibold tracking-tight font-display text-cream"
                            >
                                {{ $page.props.auth.user?.current_league?.name ?? 'GolfTourney' }}
                            </span>
                        </Link>

                        <!-- Desktop nav -->
                        <div class="hidden sm:ms-10 sm:flex sm:items-center sm:gap-1">
                            <Link
                                v-for="link in navLinks"
                                v-show="link.label !== 'Profile'"
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

                    <!-- Right: league switcher + user menu -->
                    <div class="flex sm:items-center sm:gap-3">
                        
                        <div class="hidden lg:flex">
                          <Dropdown v-if="leagues.length > 1" align="right" width="56">
                            <template #trigger>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-full border border-cream/20 px-3 py-1.5 text-sm font-medium text-cream/90 transition hover:border-brass/60 hover:text-cream"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-brass"></span>
                                    {{ user.current_league?.name ?? 'No league' }}
                                    <svg class="w-4 h-4 text-cream/50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </template>
                            <template #content>
                                <p class="px-4 pt-2 pb-1 text-xs tracking-wider uppercase text-ink/40">Switch league</p>
                                <button
                                    v-for="l in leagues"
                                    :key="l.id"
                                    type="button"
                                    @click="switchLeague(l.id)"
                                    class="flex items-center justify-between w-full gap-2 px-4 py-2 text-sm text-left transition text-ink hover:bg-parchment"
                                >
                                    <span class="truncate">{{ l.name }}</span>
                                    <span v-if="l.id === currentLeagueId" class="text-brass">●</span>
                                </button>
                                <div class="my-1 border-t border-parchment-dark"></div>
                                <DropdownLink :href="route('dashboard')">Manage leagues</DropdownLink>
                            </template>
                          </Dropdown>
                        </div>
                        
                        <div class="hidden md:flex">
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
                    </div>

                    <!-- Hamburger -->
                    <div class="flex items-center -me-2 md:hidden">
                        <button
                            @click="showingNavigationDropdown = !showingNavigationDropdown"
                            class="inline-flex items-center justify-center p-2 transition rounded-md text-cream/70 hover:bg-pine-light hover:text-cream focus:outline-none"
                        >
                            <svg class="w-6 h-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
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
                class="bg-cream text-ink"
            >
                <div class="pt-2 pb-3 space-y-1">
                    <ResponsiveNavLink
                        v-for="link in navLinks"
                        :key="link.route"
                        :href="route(link.route)"
                        :active="isActive(link.route)"
                    >
                        {{ link.label }}
                    </ResponsiveNavLink>
                    <div class="border-t">
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
