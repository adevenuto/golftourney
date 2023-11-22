<template>
    
    <div class="w-3/4 mt-10 sm:mx-auto">
        <p class="mt-10 text-5xl">Golfers</p>
        <Menu></Menu>
        <!-- SEARCH/CLEAR | ADD GOLFER -->
        <div class="flex justify-between my-10">
            <div class="flex">
                <div class="relative mr-2">
                    <input 
                        class="py-1 pr-3 border border-gray-500 rounded pl-7"
                        type="text"
                        id="searchBox"
                        placeholder="Search">

                    <v-icon 
                        class="absolute left-1.5 top-2"
                        name="hi-search" 
                        fill="#6b7280"
                        scale="1" 
                    />
                </div>
                <div class="px-3 py-1 text-white bg-gray-500 rounded cursor-pointer clear-filters hover:bg-gray-600">
                    Reset table
                </div>
            </div>

            <button 
                class="flex items-center px-3 py-1 text-white bg-green-800 rounded hover:bg-green-900 tablerow_clickevent_target"
                @click="newGolferModal = !newGolferModal"
            >   
                <v-icon 
                    class="mr-1 -ml-1"
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

        <!-- UPDATE MODAL -->
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
                    <button class="self-end mt-3 ml-auto text-white bg-blue-500 btn-base hover:bg-blue-600">Save new golfer</button>   
                </div>
                
            </form>
        </Modal>

        <!-- MANAGE HANDICAP/ROUNDS -->
        <MangeHandicap 
            :showModal="manageHandicapModal"
            :title="golferFullName"
            :golferId="selectedRow.id"
            @close_modal="closeModal"
        />
    </div>

</template>
<script>
    import Modal from '../ui/Modal.vue';
    import Menu from '../ui/Menu.vue';
    import MangeHandicap from './MangeHandicap.vue';
    import { format_date, remove_decimals } from '../../utilities'
    export default {
        components: {
            Modal,
            Menu,
            MangeHandicap
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
                deleteModal: false,
                editModal: false,
                manageHandicapModal: false,
                newGolferModal: false,
            }
        },
        watch: {
            table: function(isSet) {
                if(isSet) {
                    this.setDataTableLogic()
                    this.getGolfers()
                }
            }
        },
        computed: {
            golferFullName: function() {
                return `${this.selectedRow.first_name} ${this.selectedRow.last_name}`
            }
        },
        methods: {
            reloadTable: function() {
                this.table.clear().rows.add(this.golfersList).draw()
                this.table.order([1, 'desc'], [3, 'asc']).search('').draw()
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
                    const res = await axios.delete(`/golfers/${this.selectedRow.id}`)
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
                        case 'handicap_round_data':
                            _this.manageHandicapModal = !_this.manageHandicapModal
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
            closeModal() {
                this.deleteModal = false
                this.editModal = false
                this.manageHandicapModal = false
                this.newGolferModal = false
                this.selectedRow = {}
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
                aaSorting: [[1, 'desc'], [3, 'asc']],
                rowId: 'id',
                iDisplayLength: 30,
                data: _this.golfersList,
                columns: [
                    {
                        data: 'id',
                        visible: false,
                    },
                    {
                        data: 'created_at',
                        visible: false,
                    },
                    {
                        data: 'first_name',
                        title: 'First Name',
                        className: 'text-left',
                        sortable: false
                    },
                    {
                        data: 'last_name',
                        title: 'Last Name',
                        className: 'text-left',
                        sortable: false
                    },
                    {
                        data: 'handicap',
                        title: 'Handicap',
                        className: 'text-left',
                        render: function(data, type, row) {
                            return `<div data-action="handicap_round_data" class="tablerow_clickevent_target">
                                        <div class="flex items-center justify-between w-20 p-1 bg-white border rounded shadow-sm cursor-pointer">
                                            ${row.handicap}
                                            <svg id="handicap_round_data" class="ov-icon" aria-hidden="true" width="23.04" height="23.04" viewBox="-1.6 -1.6 19.2 19.2" fill="#03543F" style="font-size: 1.44em;"><path fill-rule="evenodd" d="M7.429 1.525a6.593 6.593 0 011.142 0c.036.003.108.036.137.146l.289 1.105c.147.56.55.967.997 1.189.174.086.341.183.501.29.417.278.97.423 1.53.27l1.102-.303c.11-.03.175.016.195.046.219.31.41.641.573.989.014.031.022.11-.059.19l-.815.806c-.411.406-.562.957-.53 1.456a4.588 4.588 0 010 .582c-.032.499.119 1.05.53 1.456l.815.806c.08.08.073.159.059.19a6.494 6.494 0 01-.573.99c-.02.029-.086.074-.195.045l-1.103-.303c-.559-.153-1.112-.008-1.529.27-.16.107-.327.204-.5.29-.449.222-.851.628-.998 1.189l-.289 1.105c-.029.11-.101.143-.137.146a6.613 6.613 0 01-1.142 0c-.036-.003-.108-.037-.137-.146l-.289-1.105c-.147-.56-.55-.967-.997-1.189a4.502 4.502 0 01-.501-.29c-.417-.278-.97-.423-1.53-.27l-1.102.303c-.11.03-.175-.016-.195-.046a6.492 6.492 0 01-.573-.989c-.014-.031-.022-.11.059-.19l.815-.806c.411-.406.562-.957.53-1.456a4.587 4.587 0 010-.582c.032-.499-.119-1.05-.53-1.456l-.815-.806c-.08-.08-.073-.159-.059-.19a6.44 6.44 0 01.573-.99c.02-.029.086-.075.195-.045l1.103.303c.559.153 1.112.008 1.529-.27.16-.107.327-.204.5-.29.449-.222.851-.628.998-1.189l.289-1.105c.029-.11.101-.143.137-.146zM8 0c-.236 0-.47.01-.701.03-.743.065-1.29.615-1.458 1.261l-.29 1.106c-.017.066-.078.158-.211.224a5.994 5.994 0 00-.668.386c-.123.082-.233.09-.3.071L3.27 2.776c-.644-.177-1.392.02-1.82.63a7.977 7.977 0 00-.704 1.217c-.315.675-.111 1.422.363 1.891l.815.806c.05.048.098.147.088.294a6.084 6.084 0 000 .772c.01.147-.038.246-.088.294l-.815.806c-.474.469-.678 1.216-.363 1.891.2.428.436.835.704 1.218.428.609 1.176.806 1.82.63l1.103-.303c.066-.019.176-.011.299.071.213.143.436.272.668.386.133.066.194.158.212.224l.289 1.106c.169.646.715 1.196 1.458 1.26a8.094 8.094 0 001.402 0c.743-.064 1.29-.614 1.458-1.26l.29-1.106c.017-.066.078-.158.211-.224a5.98 5.98 0 00.668-.386c.123-.082.233-.09.3-.071l1.102.302c.644.177 1.392-.02 1.82-.63.268-.382.505-.789.704-1.217.315-.675.111-1.422-.364-1.891l-.814-.806c-.05-.048-.098-.147-.088-.294a6.1 6.1 0 000-.772c-.01-.147.039-.246.088-.294l.814-.806c.475-.469.679-1.216.364-1.891a7.992 7.992 0 00-.704-1.218c-.428-.609-1.176-.806-1.82-.63l-1.103.303c-.066.019-.176.011-.299-.071a5.991 5.991 0 00-.668-.386c-.133-.066-.194-.158-.212-.224L10.16 1.29C9.99.645 9.444.095 8.701.031A8.094 8.094 0 008 0zm1.5 8a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM11 8a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        </div>
                                    </div>`;
                        }
                    },
                    {
                        data: 'email',
                        title: 'Email',
                        className: 'text-left',
                        sortable: false
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
                            return `<div data-action="edit_golfer" class="cursor-pointer tablerow_clickevent_target">
                                        <svg id="edit_golfer" class="ov-icon" aria-hidden="true" width="24.96" height="24.96" viewBox="-48.96 -80.96 673.92 673.92" fill="#222F3D" style="font-size: 1.56em;"><path d="M402.3 344.9l32-32c5-5 13.7-1.5 13.7 5.7V464c0 26.5-21.5 48-48 48H48c-26.5 0-48-21.5-48-48V112c0-26.5 21.5-48 48-48h273.5c7.1 0 10.7 8.6 5.7 13.7l-32 32c-1.5 1.5-3.5 2.3-5.7 2.3H48v352h352V350.5c0-2.1.8-4.1 2.3-5.6zm156.6-201.8L296.3 405.7l-90.4 10c-26.2 2.9-48.5-19.2-45.6-45.6l10-90.4L432.9 17.1c22.9-22.9 59.9-22.9 82.7 0l43.2 43.2c22.9 22.9 22.9 60 .1 82.8zM460.1 174L402 115.9 216.2 301.8l-7.3 65.3 65.3-7.3L460.1 174zm64.8-79.7l-43.2-43.2c-4.1-4.1-10.8-4.1-14.8 0L436 82l58.1 58.1 30.9-30.9c4-4.2 4-10.8-.1-14.9z"></path></svg>
                                    </div>`;
                        }
                    },
                    {
                        sortable: false,
                        render: function(data, type, row) {
                            return `<div data-action="delete_golfer" class="cursor-pointer tablerow_clickevent_target">
                                        <svg id="delete_golfer" class="ov-icon" aria-hidden="true" width="24.96" height="24.96" viewBox="-51.2 -51.2 614.4 614.4" fill="#9B1C1C" style="font-size: 1.56em;"><path fill="#9B1C1C" d="M96 472a23.82 23.82 0 0023.579 24h272.842A23.82 23.82 0 00416 472V152H96zm32-288h256v280H128z" class="ci-primary"></path><path fill="#9B1C1C" d="M168 216h32v200h-32zM240 216h32v200h-32zM312 216h32v200h-32zM328 88V40c0-13.458-9.488-24-21.6-24H205.6C193.488 16 184 26.542 184 40v48H64v32h384V88zM216 48h80v40h-80z"></path></svg>
                                    </div>`;
                        }
                    }
                    
                ]
            })
        }
    }
</script>
<style lang="css">
    /* #dt_players_list tr:hover svg#handicap_round_data {
        fill: #000;
        transition: 100ms all ease-in-out;
    } */
    #dt_players_list tr  [data-action="handicap_round_data"]:hover {
        transform: scale(1.07);
        transition: 100ms all ease-in-out;
    }

    /* #dt_players_list tr:hover svg#manage_handicap {
        fill: #000;
        transition: 100ms all ease-in-out;
    } */
    #dt_players_list tr svg#manage_handicap:hover {
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
