<template>
    <div class="mx-auto mt-10 md:max-w-2xl">
        <input
            type="text"
            v-model="searchQuery"
            @input="handleInput"
            @keydown.down="selectNextItem"
            @keydown.up="selectPreviousItem"
            @keydown.enter="selectOnEnter"
            class="w-full p-2 border rounded-lg border-slate-200 focus:outline-none focus:border-slate-500 hover:shadow"
        />
        <ul v-if="showSuggestions" class="mt-2 border max-h-[200px] overflow-auto rounded-lg">
            <li
                v-for="(item, index) in filteredItems"
                :key="index"
                :class="[
                    {'bg-slate-300': index === activeIndex, 'border-b': index+1!==filteredItems.length},
                    'bg-slate-50 p-2 hover:bg-slate-300'
                ]"
                @click="selectItem(item)"
            >
                <box-icon name='user-circle' size="xs"></box-icon> {{ item.name }} – {{ item.handicap }}
            </li>
        </ul>

        {{ selectedUsers }}
    </div>
</template>

<script>
export default {
    data() {
        return {
            searchQuery: '',
            items: [],
            activeIndex: -1,
            selectedUsers: []
        }
    },
    computed: {
        filteredItems() {
            return this.items.filter(item => item.name.toLowerCase().includes(this.searchQuery.toLowerCase()))
        },
        showSuggestions() {
            return this.searchQuery!=='' && this.filteredItems.length > 0
        },
    },
    created() {
        this.getUsers()
    },
    methods: {
        handleInput() {
            this.activeIndex = -1
        },
        selectNextItem() {
            if (this.activeIndex < this.filteredItems.length - 1) {
                this.activeIndex++
            }
        },
        selectPreviousItem() {
            if (this.activeIndex > 0) {
                this.activeIndex--
            }
        },
        selectOnClick(item) {
            this.selectItem(item)
        },
        selectOnEnter() {
            if(this.activeIndex>=0) this.selectItem(this.filteredItems[this.activeIndex])
        },
        selectItem(item) {
            this.selectedUsers.push(item)
            this.searchQuery = ''
            this.activeIndex = -1
        },
        async getUsers() {
            try {
                const res = await axios.get('/users')
                if(res.data) {
                   this.items = res.data.users
                }
            } catch (error) {
                console.error(error)
            }
        }
    },
}
</script>
