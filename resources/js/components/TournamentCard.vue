<template>
    <div v-if="!activeTournament" @click="selectTournament" class="border rounded hover:cursor-pointer hover:shadow-md">
        <div class="flex items-center justify-around p-3">
            <div class="text-2xl">{{tournament_config.course_name}}</div>
            <div class="text-2xl">{{tournament_config.tournament_name}}</div>
        </div>
        <div class="flex items-center justify-around p-3">
            <div class="flex flex-col text-center">
                <div class="text-lg">Entry Cost</div>
                <div class="text-lg">{{tournament_config.entry_cost}}</div>
            </div>
            <div class="flex flex-col text-center">
                <div class="text-lg">Skins Cost</div>
                <div class="text-lg">{{tournament_config.skin_prox_cost}}</div>
            </div>
            <div class="flex flex-col text-center">
                <div class="text-lg">Hole Count</div>
                <div class="text-lg">{{tournament_config.hole_count}}</div>
            </div>
        </div>
        
        <div class="flex mt-3 border-t">
            <div class="flex flex-col border-r">
                <div class="w-24 px-3 py-1 border-b">Hole</div>
                <div class="w-24 px-3 py-1 border-b">Par</div>
                <div class="w-24 px-3 py-1">Length</div>
            </div>
            <div v-for="(hole, i) in tournament_holes" :key="hole" class="flex flex-col flex-1">
                <div :class="[{'border-r': tournament_holes.length !== i+1},'p-1 text-center border-b']">{{ i+1 }}</div>
                <div :class="[{'border-r': tournament_holes.length !== i+1},'p-1 text-center border-b']">{{ hole[`hole-${i+1}`].par }}</div>
                <div :class="[{'border-r': tournament_holes.length !== i+1},'p-1 text-center']">{{ hole[`hole-${i+1}`].length }}</div>
            </div>
        </div>
    </div>
    <div v-else>
        Active tournament found – <a class="inline-flex items-center px-2 py-0.5 bg-green-600 text-white rounded-md" :href="`/tournament/${activeTournament.uuid}`">
            Back to tournament <box-icon class="ml-3" name='right-arrow-circle' type='solid' animation='tada' size="sm" color="white"></box-icon> </a>
        
    </div>
</template>

<script>
    import 'boxicons'
    export default {
        data() {
            return {
                tournament_config: {},
                tournament_holes: [],
                activeTournament: true
            }
        },
        created() {
            this.checkActiveTournaments()
        },
        methods: {
            async selectTournament() {
                try {
                    const res = await axios.post(`/select/tournament/${this.tournament_config.id}`)
                    if(res.data) {
                        window.location = res.data.redirectUrl
                    }
                } catch (error) {
                    console.error(error);
                }
            },
            async getTournaments() {
                try {
                    const res = await axios.get('/get/tournament')
                    if(res.data) {
                        this.tournament_config = res.data
                        this.tournament_holes = JSON.parse(res.data.course_details).holes
                    }
                } catch (error) {
                    console.error(error);
                }
            },
            async checkActiveTournaments() {
                try {
                    const res = await axios.get('/user/active/tournament')
                    if(!res.data) {
                        this.getTournaments()
                        this.activeTournament = false
                    } else {
                        this.activeTournament = res.data
                    }
                } catch (error) {
                    console.error(error);
                }
            }
        }
    }
</script>
