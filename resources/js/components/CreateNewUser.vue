<template>
<div class="p-4 mx-auto mt-10 border rounded shadow md:max-w-2xl ">
        <h1 class="pb-5 text-4xl font-medium">Add Player</h1>
        
        <form @submit.prevent="addPlayer">
            <div class="flex flex-col gap-3 sm:flex-row">

                <label for="name">
                    <p class="pb-2 font-medium text-slate-700">Name</p>
                    <input id="name" type="name" class="w-full p-2 border rounded-lg sm:w-44 border-slate-200 focus:outline-none focus:border-slate-500 hover:shadow" name="name" required v-model="form.name" autocomplete="name" autofocus>
                </label>

                <label for="handicap">
                    <p class="pb-2 font-medium text-slate-700">Handicap</p>

                    <input id="handicap" type="number" step=".1" class="w-full p-2 border rounded-lg sm:w-44 border-slate-200 focus:outline-none focus:border-slate-500 hover:shadow" name="handicap" v-model="form.handicap" required autofocus>
                </label>

                <button class="inline-flex items-center self-end justify-center w-full p-2 mt-6 font-medium text-white bg-green-600 border border-green-700 rounded-lg sm:mt-0 sm:w-40 sm:flex-1 hover:bg-green-700 hover:shadow">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    <span>Add Player</span>
                </button>
                
            </div>
        </form>
    </div>
</template>

<script>
    export default {
        data() {
            return {
                form: {
                    name: '',
                    handicap: ''
                }
            }
        },
        created() {
            
        },
        methods: {
            addPlayer() {
                axios.post('/create/user', {
                    name: this.form.name,
                    handicap: this.form.handicap
                })
                .then(res => {
                    console.log(res)
                }).catch(err => {
                    console.log(err)
                })
            }
        }
    }
</script>
