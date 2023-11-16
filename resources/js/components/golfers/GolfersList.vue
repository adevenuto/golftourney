<template>
    
    <div class="w-3/4 mt-10 sm:mx-auto">
        <p class="mt-10 text-5xl">Golfers</p>

        <!-- SEARCH/CLEAR | ADD GOLFER -->
        <div class="flex items-center justify-between my-10">
            <div class="flex">
                <div class="mr-2">
                    <input class="px-3 py-1 border rounded"
                    type="text"
                    id="searchBox"
                    placeholder="Search table">
                </div>
                <div class="px-3 py-1 text-white bg-gray-500 rounded cursor-pointer clear-filters hover:bg-gray-600">
                    Clear filters
                </div>
            </div>

            <button 
                class="flex items-center px-3 py-1 text-white bg-green-500 rounded hover:bg-green-600 tablerow_clickevent_target"
                @click="newGolferModal = !newGolferModal"
            >   
                <v-icon 
                    class="-ml-1"
                    name="hi-plus-sm" 
                    fill="#fff"
                    scale="1.2" 
                />
                New golfer
            </button>
        </div>
        
        <table id="dt_players_list" class="table text-sm table-striped hover row-border" cellspacing="0" width="100%"></table>

        <!-- DELETE MODAL -->
        <Modal 
            v-show="deleteModal" 
            @close_modal="closeModal" 
            :title="golferFullName"
        >
            <div class="mb-4">
                <v-icon 
                    name="io-warning" 
                    fill="#ef4444"
                    scale="1.1" 
                    class="self-start cursor-pointer"
                />
                <span class="text-gray-400">You are about to</span> 
                delete {{ golferFullName }}<span class="text-gray-400">, are you sure?</span>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                <div    
                    @click="closeModal"
                    class="text-white btn-base bg-slate-400 hover:bg-slate-500"
                >
                    Cancel
                </div>
                <div    
                    @click="deleteGolfer"
                    class="text-white bg-red-500 btn-base hover:bg-red-600"
                >
                    Yes, delete {{ golferFullName }}
                </div>
            </div>
        </Modal>

        <!-- EDIT MODAL -->
        <Modal 
            v-show="editModal" 
            @close_modal="closeModal" 
            :title="golferFullName"
        >
            <form @submit.prevent="updateGolfer">
                <div class="flex flex-col sm:gap-3 sm:flex-row">
                    <div class="flex-1 my-2">
                        <label for="first_name" class="block mb-1 text-xs">First name</label>
                        <input 
                            type="text" 
                            id="first_name" 
                            class="field-base" 
                            v-model="selectedRow.first_name" 
                            required
                        >
                    </div>
                    <div class="flex-1 my-2">
                        <label for="last_name" class="block mb-1 text-xs">Last name</label>
                        <input 
                            type="text" 
                            id="last_name" 
                            class="field-base" 
                            v-model="selectedRow.last_name" 
                            required
                        >
                    </div>
                </div>
                <div class="flex flex-col sm:gap-3 sm:flex-row">
                    <div class="flex-1 my-2">
                        <label for="email" class="block mb-1 text-xs">Email</label>
                        <input 
                            type="email" 
                            id="email" 
                            class="field-base" 
                            v-model="selectedRow.email" 
                            required
                        >
                    </div>
                    <div class="flex-1 my-2">
                        <label for="phone" class="block mb-1 text-xs">Phone</label>
                        <input 
                            type="text" 
                            id="phone" 
                            class="field-base" 
                            v-model="selectedRow.phone"
                            placeholder="(***) *** ****"
                            v-phone-format="selectedRow.phone"
                        >
                    </div>
                </div>
                <div class="flex">
                    <button class="self-end mt-3 ml-auto text-white bg-blue-500 btn-base hover:bg-blue-600">Save changes</button>   
                </div>
                
            </form>
        </Modal>

        <!-- NEW GOLFER MODAL -->
        <Modal 
            v-show="newGolferModal" 
            @close_modal="closeModal" 
            :title="`${newGolfer.first_name} ${newGolfer.last_name}`"
        >
            <form @submit.prevent="addNewGolfer">
                <div class="flex flex-col sm:gap-3 sm:flex-row">
                    <div class="flex-1 my-2">
                        <label for="first_name" class="block mb-1 text-xs">First name</label>
                        <input 
                            type="text" 
                            id="first_name" 
                            class="field-base" 
                            v-model="newGolfer.first_name" 
                            required
                        >
                    </div>
                    <div class="flex-1 my-2">
                        <label for="last_name" class="block mb-1 text-xs">Last name</label>
                        <input 
                            type="text" 
                            id="last_name" 
                            class="field-base" 
                            v-model="newGolfer.last_name" 
                            required
                        >
                    </div>
                </div>
                <div class="flex flex-col sm:gap-3 sm:flex-row">
                    <div class="flex-1 my-2">
                        <label for="email" class="block mb-1 text-xs">Email</label>
                        <input 
                            type="email" 
                            id="email" 
                            class="field-base" 
                            v-model="newGolfer.email" 
                            required
                        >
                    </div>
                    <div class="flex-1 my-2">
                        <label for="phone" class="block mb-1 text-xs">Phone</label>
                        <input 
                            type="text" 
                            id="phone" 
                            class="field-base" 
                            v-model="newGolfer.phone"
                            placeholder="(***) *** ****"
                            v-phone-format="newGolfer.phone"
                        >
                    </div>
                </div>
                <div class="flex">
                    <button class="self-end mt-3 ml-auto text-white bg-blue-500 btn-base hover:bg-blue-600">Add new golfer</button>   
                </div>
                
            </form>
        </Modal>

        <!-- ADD SCORE MODAL -->
        <Modal 
            v-show="addScoreModal" 
            @close_modal="closeModal" 
            :title="golferFullName"
        >
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
        </Modal>

        <!-- RECENT ROUNDS MODAL -->
        <Modal 
            v-show="recentRoundsModal" 
            @close_modal="closeModal" 
            :title="golferFullName"
        >   
            <div class="flex items-center gap-3"></div>
            <div 
                class="flex items-center justify-between gap-3 px-2 py-1 my-1 border rounded"
                :key="round.id" 
                v-for="round in golfersRecentRounds"
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
        </Modal>
        
    </div>

</template>
<script>
    import Modal from '../ui/Modal.vue';
    import { format_date, remove_decimals } from '../../utilities'
    export default {
        components: {
            Modal
        },
        data() {
            return {
                table: null,
                golfersList: [],
                golfersRecentRounds: [],
                selectedRow: {},
                newGolfer: {
                    first_name: '',
                    last_name: '',
                    email: '',
                    phone: ''
                },
                newScore: null,

                deleteModal: false,
                editModal: false,
                addScoreModal: false,
                recentRoundsModal: false,
                newGolferModal: false,
            }
        },
        watch: {
            table: function(isSet) {
                if(isSet) {
                    this.setDataTableLogic()
                    this.getGolfers()
                }
            },
            recentRoundsModal: function(isOpen) {
                if(isOpen) return this.getRounds()
                return this.golfersRecentRounds = []
            }
        },
        computed: {
            golferFullName: function() {
                return `${this.selectedRow.first_name} ${this.selectedRow.last_name}`
            }
        },
        methods: {
            reloadTable: function() {
                this.table.clear().rows.add(this.golfersList).draw();
            },
            async getGolfers() {
                try {
                    const res = await axios.get('/golfers-list')
                    if(res.data) {
                        this.golfersList = res.data.golfers
                        this.reloadTable()
                    } 
                } catch (err) {
                    console.error(err);
                }
            },
            async deleteGolfer() {
                try {
                    const res = await axios.delete(`/golfers/${this.selectedRow.golfer_id}`)
                    if(res.status===200) {
                        console.log(res)
                        this.closeModal()
                        this.getGolfers()
                    }
                } catch (err) {
                    console.error(err);
                }
            },
            setDataTableLogic() {
                const _this = this;
                $('#dt_players_list').on('click', '.tablerow_clickevent_target', function(e) {
                    var theRow = $(this).closest('tr')
                    var rowData = _this.table.row( theRow ).data()
                    var btnAction = $(this).attr('data-action')
                    
                    _this.selectedRow = JSON.parse(JSON.stringify(rowData))

                    switch (btnAction) {
                        case 'delete_golfer':
                            _this.deleteModal = !_this.deleteModal
                            break
                        case 'edit_golfer':
                            _this.editModal = !_this.editModal
                            break
                        case 'add_score':
                            _this.addScoreModal = !_this.addScoreModal
                            break
                        case 'handicap_round_data':
                            _this.recentRoundsModal = !_this.recentRoundsModal
                            break
                        default:
                            break;
                    }
                })

                $("#searchBox").keyup(function() {
                    _this.table.search(this.value).draw();
                });

                $('.clear-filters').click(function () {
                    $('#searchBox').val('')
                    _this.table.order([]).search('').draw()
                    _this.reloadTable()
                });
            },
            async updateGolfer() {
                try {
                    const res = await axios.post(`/golfers/${this.selectedRow.id}/edit`, this.selectedRow)
                    if(res.status===200) {
                        console.log(res)
                        this.closeModal()
                        this.getGolfers()
                    }
                } catch (err) {
                    console.error(err);
                }
            },
            async addScore() {
                try {
                    const res = await axios.post(`/golfers/${this.selectedRow.id}/add/score/${this.newScore}`)
                    if(res.status===200) {
                        console.log(res)
                        this.closeModal()
                        this.getGolfers()
                    }
                } catch (err) {
                    console.error(err);
                }
            },
            async addNewGolfer() {
                try {
                    const res = await axios.post('/create/golfer', this.newGolfer)
                    if(res.status===200) {
                        console.log(res)
                        this.closeModal()
                        this.getGolfers()
                    }
                } catch (err) {
                    console.error(err);
                }
            },
            async getRounds() {
                try {
                    const res = await axios.get(`/golfers/${this.selectedRow.id}/latest`)
                    if(res.status===200) {
                        console.log(res)
                        this.golfersRecentRounds = res.data.latest_rounds
                    }
                } catch (err) {
                    console.error(err);
                }
            },
            closeModal() {
                this.deleteModal = false
                this.editModal = false
                this.addScoreModal = false
                this.recentRoundsModal = false
                this.newGolferModal = false
                this.selectedRow = {}
                this.newScore = null
            },
            limitTwo() {
                var x = this.newScore.replace(/\D/g, '').match(/^[1-9][0-9]?$|^150$/)
                this.newScore = x
            },
            _format_date: function(date) {
                return format_date(date)
            },
            _remove_decimals: function(string) {
                return remove_decimals(string)
            }
        },
        mounted() {
            const _this = this;
            _this.table = $('#dt_players_list').DataTable({
                responsive: true,
                scrollX: true,
                aaSorting: [[2, 'asc']],
                rowId: 'id',
                iDisplayLength: 30,
                data: _this.golfersList,
                columns: [
                    {
                        data: 'id',
                        visible: false,
                    },
                    {
                        data: 'first_name',
                        title: 'First Name',
                        className: 'text-left'
                    },
                    {
                        data: 'last_name',
                        title: 'Last Name',
                        className: 'text-left'
                    },
                    {
                        data: 'handicap',
                        title: 'Handicap',
                        className: 'text-left',
                        render: function(data, type, row) {
                            return `<div data-action="handicap_round_data" class="tablerow_clickevent_target">
                                        <div class="flex items-center justify-between w-20 p-1 bg-white border rounded shadow-sm cursor-pointer">
                                            ${row.handicap}
                                            <svg class="ov-icon" aria-hidden="true" width="19.2" height="19.2" viewBox="-1.6 -1.6 19.2 19.2" fill="#22c55e" style="font-size: 1.2em;"><path fill-rule="evenodd" d="M8 1.5a6.5 6.5 0 100 13 6.5 6.5 0 000-13zM0 8a8 8 0 1116 0A8 8 0 010 8zm6.5-.25A.75.75 0 017.25 7h1a.75.75 0 01.75.75v2.75h.25a.75.75 0 010 1.5h-2a.75.75 0 010-1.5h.25v-2h-.25a.75.75 0 01-.75-.75zM8 6a1 1 0 100-2 1 1 0 000 2z"></path></svg>
                                        </div>
                                    </div>`;
                        }
                    },
                    {
                        data: 'email',
                        title: 'Email',
                        className: 'text-left'
                    },
                    {
                        data: 'phone',
                        title: 'Phone',
                        className: 'text-left',
                        sortable: false
                    },
                    {
                        sortable: false,
                        render: function(data, type, row) {
                            return `<div data-action="add_score" class="cursor-pointer tablerow_clickevent_target">
                                        <svg id="add_score" class="ov-icon" aria-hidden="true" width="24.96" height="24.96" viewBox="0.48 0.48 23.04 23.04" fill="#222F3D" style="font-size: 1.56em;"><path fill="none" d="M0 0h24v24H0V0z"></path><circle cx="19.5" cy="19.5" r="1.5"></circle><path d="M17 5.92L9 2v18H7v-1.73c-1.79.35-3 .99-3 1.73 0 1.1 2.69 2 6 2s6-.9 6-2c0-.99-2.16-1.81-5-1.97V8.98l6-3.06z"></path></svg>
                                    </div>`;
                        }
                    },
                    {
                        sortable: false,
                        render: function(data, type, row) {
                            return `<div data-action="edit_golfer" class="cursor-pointer tablerow_clickevent_target">
                                        <svg id="edit_golfer" class="ov-icon" aria-hidden="true" width="24.96" height="24.96" viewBox="-48.96 -80.96 673.92 673.92" fill="#222F3D" style="font-size: 1.56em;"><path d="M402.3 344.9l32-32c5-5 13.7-1.5 13.7 5.7V464c0 26.5-21.5 48-48 48H48c-26.5 0-48-21.5-48-48V112c0-26.5 21.5-48 48-48h273.5c7.1 0 10.7 8.6 5.7 13.7l-32 32c-1.5 1.5-3.5 2.3-5.7 2.3H48v352h352V350.5c0-2.1.8-4.1 2.3-5.6zm156.6-201.8L296.3 405.7l-90.4 10c-26.2 2.9-48.5-19.2-45.6-45.6l10-90.4L432.9 17.1c22.9-22.9 59.9-22.9 82.7 0l43.2 43.2c22.9 22.9 22.9 60 .1 82.8zM460.1 174L402 115.9 216.2 301.8l-7.3 65.3 65.3-7.3L460.1 174zm64.8-79.7l-43.2-43.2c-4.1-4.1-10.8-4.1-14.8 0L436 82l58.1 58.1 30.9-30.9c4-4.2 4-10.8-.1-14.9z"></path></svg>
                                    </div>`;
                        }
                    },
                    {
                        sortable: false,
                        render: function(data, type, row) {
                            return `<div data-action="delete_golfer" class="cursor-pointer tablerow_clickevent_target">
                                        <svg id="delete_golfer" class="ov-icon" aria-hidden="true" width="24.96" height="24.96" viewBox="-51.2 -51.2 614.4 614.4" fill="#222F3D" style="font-size: 1.56em;"><path fill="#222F3D" d="M96 472a23.82 23.82 0 0023.579 24h272.842A23.82 23.82 0 00416 472V152H96zm32-288h256v280H128z" class="ci-primary"></path><path fill="#222F3D" d="M168 216h32v200h-32zM240 216h32v200h-32zM312 216h32v200h-32zM328 88V40c0-13.458-9.488-24-21.6-24H205.6C193.488 16 184 26.542 184 40v48H64v32h384V88zM216 48h80v40h-80z"></path></svg>
                                    </div>`;
                        }
                    }
                    
                ]
            })
        }
    }
</script>
<style lang="css">
    /* #dt_players_list tr:hover svg#add_score {
        fill: #000;
        transition: 100ms all ease-in-out;
    } */
    #dt_players_list tr svg#add_score:hover {
        transform: scale(1.2);
        transition: 100ms all ease-in-out;
    }

    /* #dt_players_list tr:hover svg#edit_golfer{
        fill: #3F83F8;
        transition: 100ms fill ease-in-out;
    } */
    #dt_players_list tr svg#edit_golfer:hover {
        transform: scale(1.2);
        transition: 100ms all ease-in-out;
    }

    /* #dt_players_list tr:hover svg#delete_golfer path {
        fill: #F05252;
        transition: 100ms fill ease-in-out;
    } */
    #dt_players_list tr svg#delete_golfer:hover {
        transform: scale(1.2);
        transition: 100ms all ease-in-out;
    }
</style>
