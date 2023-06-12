<template>
    <div class="mx-auto mt-10 border rounded shadow md:max-w-2xl ">
        <div class="flex items-center justify-around p-3">
            <div class="text-2xl">{{tournament.course_name}}</div>
            <div class="text-2xl">{{tournament.tournament_name}}</div>
        </div>
        <div class="flex items-center justify-around p-3">
            <div class="flex flex-col text-center">
                <div class="text-lg">Entry Cost</div>
                <div class="text-lg">{{tournament.entry_cost}}</div>
            </div>
            <div class="flex flex-col text-center">
                <div class="text-lg">Entry Skin/Prox Cost</div>
                <div class="text-lg">{{tournament.skin_prox_cost}}</div>
            </div>
            <div class="flex flex-col text-center">
                <div class="text-lg">Hole Count</div>
                <div class="text-lg">{{tournament.hole_count}}</div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        data() {
            return {
                tournament: {}
            }
        },
        created() {
            axios.get('/get/tournament')
                .then(res => {
                    this.tournament = res.data
                }).catch(err => {
                    console.log(err)
                })
        }
    }
</script>
