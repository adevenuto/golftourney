import { computed, ref, watch } from 'vue';

/**
 * Client-side data-table logic shared across tables: search, sort, pagination.
 *
 * @param {import('vue').Ref<Array>|Function} source  Ref/getter returning the full row set.
 * @param {Object}   options
 * @param {string[]} [options.searchFields]   Row keys included in the search filter.
 * @param {Object}   [options.sortAccessors]  Map of sortKey -> (row) => comparable value.
 * @param {{key:string,dir:'asc'|'desc'}} [options.initialSort]
 * @param {number[]} [options.perPageOptions]
 * @param {number}   [options.initialPerPage]
 */
export function useDataTable(source, options = {}) {
    const {
        searchFields = [],
        sortAccessors = {},
        initialSort = null,
        perPageOptions = [25, 50, 75, 100],
        initialPerPage = 25,
    } = options;

    const rows = computed(() =>
        typeof source === 'function' ? source() : source.value,
    );

    const search = ref('');
    const sortKey = ref(initialSort?.key ?? null);
    const sortDir = ref(initialSort?.dir ?? 'asc');
    const perPage = ref(initialPerPage);
    const page = ref(1);

    const filtered = computed(() => {
        const term = search.value.trim().toLowerCase();
        if (!term || searchFields.length === 0) return rows.value;
        return rows.value.filter((row) =>
            searchFields.some((field) =>
                String(row[field] ?? '')
                    .toLowerCase()
                    .includes(term),
            ),
        );
    });

    const sorted = computed(() => {
        if (!sortKey.value) return filtered.value;
        const dir = sortDir.value === 'asc' ? 1 : -1;
        const accessor =
            sortAccessors[sortKey.value] ?? ((row) => row[sortKey.value]);

        return [...filtered.value].sort((a, b) => {
            const av = accessor(a);
            const bv = accessor(b);
            if (typeof av === 'number' && typeof bv === 'number') {
                return (av - bv) * dir;
            }
            return String(av).localeCompare(String(bv)) * dir;
        });
    });

    const total = computed(() => sorted.value.length);
    const pageCount = computed(() =>
        Math.max(1, Math.ceil(total.value / perPage.value)),
    );

    const paginated = computed(() => {
        const start = (page.value - 1) * perPage.value;
        return sorted.value.slice(start, start + perPage.value);
    });

    const range = computed(() => {
        if (total.value === 0) return { from: 0, to: 0, total: 0 };
        return {
            from: (page.value - 1) * perPage.value + 1,
            to: Math.min(page.value * perPage.value, total.value),
            total: total.value,
        };
    });

    function toggleSort(key) {
        if (sortKey.value === key) {
            sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
        } else {
            sortKey.value = key;
            sortDir.value = 'asc';
        }
    }

    function setPage(target) {
        page.value = Math.min(Math.max(1, target), pageCount.value);
    }

    // Reset to first page when the result set shrinks below the current page.
    watch([search, perPage], () => (page.value = 1));
    watch(pageCount, () => {
        if (page.value > pageCount.value) page.value = pageCount.value;
    });

    return {
        search,
        sortKey,
        sortDir,
        perPage,
        perPageOptions,
        page,
        pageCount,
        paginated,
        total,
        range,
        toggleSort,
        setPage,
    };
}
