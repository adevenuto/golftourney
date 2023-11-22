<template>
    <Modal 
            v-show="showModal" 
            @close_modal="closeModal" 
            :title="title"
        >   
            Hello
    </Modal>
</template>

<script>
import Modal from '../ui/Modal.vue';
export default {
    components: {
        Modal
    },
    props: {
        title: {
            type: String,
            required: false,
            default: 'Title here'
        },
        showModal: {
            type: Boolean,
            default: false
        },
        golferId: {
            id: Number,
            required: true
        }
    },
    data() {
        return {
            recentRounds: []
        }
    },
    watch: {
        showModal: function(isOpen) {
            if(isOpen) return this.getRounds()
            return this.recentRounds = []
        } 
    },
    methods: {
        closeModal: function() {
            this.$emit('close_modal');
        },
        async getRounds() {
            try {
                const res = await axios.get(`/golfers/${this.golferId}/latest`)
                if(res.status===200) {
                    console.log(res)
                    this.recentRounds = res.data.latest_rounds
                }
            } catch (err) {
                console.error(err);
            }
        },
    }
}
</script>

<style>

</style>



<!-- 
    
    <form @submit.prevent="addScore">
        <div class="my-2">
            <label for="email" class="block mb-1 text-xs">New score</label>
            <input 
                @input="limitTwo"
                v-model="newScore"
                type="text"
                min="0"
                step="1"
                id="email" 
                class="field-base" 
                placeholder="Add new score to update handicap"
                required
            >
        </div>
        <div class="flex">
            <button class="self-end mt-3 ml-auto text-white bg-blue-500 btn-base hover:bg-blue-600">
                Save score
            </button>   
        </div>
    </form> 
            
            
            
    <div 
        class="flex items-center justify-between gap-3 px-2 py-1 my-1 border rounded"
        :key="round.id" 
        v-for="round in recentRounds"
    >   
        <div>
            <v-icon 
                name="gi-golf-tee" 
                fill="#046C4E"
                scale="1.2" 
            />
            {{ _remove_decimals(round.score) }}
        </div>
        
        {{ _format_date(round.created_at) }}
    </div> 


    manageHandicapModal: function(isOpen) {
        if(isOpen) return this.getRounds()
        return this.recentRounds = []
    } 

-->