<template>
    <div class="mx-3 mt-10 sm:w-3/4 xl:w-1/2 sm:mx-auto">
        <!-- Card -->
        <div class="flex p-3 border rounded shadow-sm">
            <div class="w-24 text-gray-400">
                <p>Golfer</p>
                <p>Handicap</p>
            </div>
            <div class="mr-3">
                <P>{{ golferFullName }}</P>
                <p>{{ golfer.handicap }}</p>
            </div>
            <div class="flex items-center justify-end flex-1">
                <button 
                    class="flex items-center px-3 py-1 text-xs text-white bg-green-800 rounded sm:text-base hover:bg-green-900"
                    @click="newModal = true"
                >   
                    <v-icon  
                        class="mr-1 -ml-1"
                        name="hi-plus-sm" 
                        fill="#fff"
                        scale="1.2" 
                    />
                    Enter a new round
                </button>
            </div>
        </div>  

        <!-- Recent calculated rounds list -->
        <div class="mt-12">
            <h2 class="text-4xl">Recent rounds</h2>
            <p class="mt-1 text-sm leading-tight">These are the most recent best {{ roundsLatestLength }} of {{ roundsTotal }} rounds. <br> These are the rounds used to calculate {{ golferFullName }}'s handicap of {{ golfer.handicap }}</p>
            <div class="grid grid-cols-1 gap-2.5 mt-6">
                <div 
                    class="flex items-center justify-between px-2 py-1.5 border rounded"
                    :key="round.id" 
                    v-for="round in rounds.latest"
                >   
                    <div>
                        {{ round.score }} 
                        <span class="text-sm text-gray-400">/ {{ _format_date(round.created_at) }}</span>
                    </div>
                    
                    <div id="round-list-item-right">
                        <v-icon 
                            @click="editModalHandler(round)" 
                            name="fa-regular-edit" 
                            fill="#222F3D"
                            scale="1.2" 
                            class="mr-3 cursor-pointer icon"
                        />
                        <v-icon
                            @click="deleteModal = true; selectedRound = round" 
                            name="ri-delete-bin-2-line" 
                            fill="#ef4444"
                            scale="1.2" 
                            class="cursor-pointer icon"
                        />
                    </div>
                    
                </div> 
            </div>
        </div>

        <!-- Delete round modal -->
        <Modal 
            v-show="deleteModal" 
            @close_modal="closeModal" 
            :title="selectedRoundTitle"
        >   
            <div class="mb-4">
                <v-icon 
                    name="io-warning" 
                    fill="#ef4444"
                    scale="1.1" 
                    class="self-start cursor-pointer"
                />
                <span class="text-gray-400">You are about to delete </span> 
                {{ golferFullName }}'s
                <span class="text-gray-400">round of </span>
                {{ selectedRound.score }}
                <span class="text-gray-400">posted on </span>
                {{ _format_date(selectedRound.created_at) }}
                <span class="text-gray-400">. This may effect their calculated handicap. Are you sure?</span>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                <div    
                    @click="closeModal"
                    class="text-white btn-base bg-slate-400 hover:bg-slate-500"
                >
                    Cancel
                </div>
                <div    
                    @click="deleteRound"
                    class="text-white bg-red-500 btn-base hover:bg-red-600"
                >
                    Yes, delete round
                </div>
            </div>
        </Modal>

        <!-- Edit round modal -->
        <Modal 
            v-show="editModal" 
            @close_modal="closeModal" 
        >
            <form @submit.prevent="updateRound">
                <div class="flex flex-col sm:gap-3 sm:flex-row">
                    <div class="flex-1 my-2">
                        <label for="score" class="block mb-1 text-xs">Score</label>
                        <input 
                            @input="_limitNumber"
                            type="text" 
                            id="score" 
                            class="field-base" 
                            v-model="newOrEditRound.score"
                            required
                        >
                    </div>
                    <div class="flex-1 my-2">
                        <label class="block mb-1 text-xs">Date</label>
                        <VueDatePicker 
                            :enable-time-picker="false"
                            :auto-apply="true"
                            v-model="newOrEditRound.created_at"
                            input-class-name="field-base !bg-gray-50 !py-2"
                            :required="true"
                            format="yyyy-MM-dd"
                        ></VueDatePicker>
                    </div>
                </div>
                <div class="flex">
                    <button 
                        class="self-end mt-3 ml-auto text-white bg-blue-500 btn-base hover:bg-blue-600"
                    >
                        Save changes
                    </button>   
                </div>
                
            </form>
        </Modal>

        <!-- New round modal -->
        <!-- <Modal 
            v-show="newModal" 
            @close_modal="closeModal" 
        >
            <form @submit.prevent="addNewRound">
                <div class="flex flex-col sm:gap-3 sm:flex-row">
                    <div class="flex-1 my-2">
                        <label for="first_name" class="block mb-1 text-xs">First name</label>
                        <input 
                            type="text" 
                            id="first_name" 
                            class="field-base" 
                            
                            required
                        >
                    </div>
                    <div class="flex-1 my-2">
                        <label for="last_name" class="block mb-1 text-xs">Last name</label>
                        <input 
                            type="text" 
                            id="last_name" 
                            class="field-base" 
                            
                            required
                        >
                    </div>
                </div>
                <div class="flex">
                    <button class="self-end mt-3 ml-auto text-white bg-blue-500 btn-base hover:bg-blue-600">Save changes</button>   
                </div>
                
            </form>
        </Modal> -->
    </div>

</template>

<script>
import Modal from '../ui/Modal.vue'
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css'
import { format_date } from '../../utilities'
export default {
    components: {
        Modal,
        VueDatePicker
    },
    data() {
        return {
            rounds: {
                latest: [],
                total: 0
            },
            golfer: {},
            golferId: null,
            deleteModal: false,
            editModal: false,
            newModal: false,
            selectedRound: {},
            newOrEditRound: {}
        }
    },
    computed: {
        golferFullName: function() {
            return `${this.golfer.first_name} ${this.golfer.last_name}`
        },
        roundsLatestLength: function() {
            return this.rounds.latest.length
        },
        roundsTotal: function() {
            return this.rounds.total
        },
        selectedRoundTitle: function() {
            return `${this.selectedRound.score} <span class="text-sm text-gray-400">/ ${this._format_date(this.selectedRound.created_at)}</span>`
        }
    },
    watch: {
        
    },
    methods: {
        closeModal() {
            this.deleteModal = false
            this.selectedRound = {}
            this.newOrEditRound = {}
            this.editModal = false
            this.newModal = false
        },
        editModalHandler(round) {
            this.editModal = true 
            this.newOrEditRound = JSON.parse(JSON.stringify(round))
        },
        async getGolferRounds() {
            try {
                const res = await axios.get(`/golfers/${this.golferId}/rounds`)
                if(res.status===200) {
                    this.rounds = res.data.rounds
                }
            } catch (err) {
                console.error(err);
            }
        },
        async getGolfer() {
            try {
                const res = await axios.get(`/golfer/${this.golferId}`)
                if(res.status===200) {
                    this.golfer = res.data.golfer
                }
            } catch (err) {
                console.error(err);
            }
        },
        async deleteRound() {
            try {
                const res = await axios.delete(`/rounds/${this.selectedRound.id}`)
                if(res.status===200) {
                    console.log(res)
                    this.closeModal()
                    this.getGolfer(this.golferId)
                    this.getGolferRounds(this.golferId)
                }
            } catch (err) {
                console.error(err);
            }
        },
        async updateRound() {
            try {
                const res = await axios.post(`/rounds/edit`, this.newOrEditRound)
                if(res.status===200) {
                    console.log(res)
                    this.closeModal()
                    this.getGolfer(this.golferId)
                    this.getGolferRounds(this.golferId)
                }
            } catch (err) {
                console.error(err);
            }
        },
        _format_date: function(date) {
            return format_date(date)
        },
        _limitNumber() {
            var x = this.newOrEditRound.score.replace(/\D/g, '').match(/^(?:\d{1,2}|1[0-4]\d|150)$/)
            this.newOrEditRound.score = x[0]
        },
    },
    created() {
        var url = new URL(window.location.href)
        var path = url.pathname
        this.golferId = path.split('/').pop()

        this.getGolfer(this.golferId)
        this.getGolferRounds(this.golferId)
    }
}
</script>

<style>
    #round-list-item-right .icon:hover {
        transform: scale(1.2);
        transition: 100ms all ease-in-out;
    }
</style>