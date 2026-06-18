<script setup>
import PageInfo from './PageInfo.vue';
import TablePager from './TablePager.vue';

defineProps({
    range: { type: Object, required: true },
    page: { type: Number, required: true },
    pageCount: { type: Number, required: true },
});
defineEmits(['update:page']);
</script>

<template>
    <!--
        Sticks to the bottom of the viewport while a long table scrolls, then
        rests in place at the end. The negative margins let the bar span the
        full width of the page container (which uses px-4 sm:px-6 lg:px-8).
    -->
    <div
        class="sticky bottom-0 z-10 -mx-4 mt-4 border-t border-parchment-dark bg-parchment/90 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8"
    >
        <div class="flex flex-wrap items-center justify-between gap-3">
            <PageInfo :from="range.from" :to="range.to" :total="range.total" />
            <TablePager
                :page="page"
                :page-count="pageCount"
                @update:page="$emit('update:page', $event)"
            />
        </div>
    </div>
</template>
