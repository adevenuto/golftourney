<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({ games: { type: Array, default: () => [] } });

const meta = {
    active: { label: 'Live', class: 'bg-brass/15 text-brass-dark' },
    lobby: { label: 'Waiting', class: 'bg-pine/10 text-pine' },
    completed: { label: 'Finished', class: 'bg-parchment-dark text-ink/60' },
    abandoned: { label: 'Canceled', class: 'bg-parchment-dark text-ink/40' },
};
</script>

<template>
    <ul class="divide-y divide-parchment-dark">
        <li v-for="g in games" :key="g.id">
            <Link
                :href="route('games.show', g.id)"
                class="flex items-center justify-between gap-3 px-1 py-3 transition rounded-lg hover:bg-parchment/50"
            >
                <div class="min-w-0">
                    <p class="font-medium capitalize truncate text-ink">{{ g.course_name }}</p>
                    <p class="text-xs text-ink/50">
                        {{ g.holes }} holes · {{ g.players_count }} player{{ g.players_count === 1 ? '' : 's' }}
                        <span v-if="g.is_owner" class="text-ink/40"> · you host</span>
                    </p>
                </div>
                <span
                    class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium"
                    :class="(meta[g.status] || meta.completed).class"
                >
                    {{ (meta[g.status] || meta.completed).label }}
                </span>
            </Link>
        </li>
    </ul>
</template>
