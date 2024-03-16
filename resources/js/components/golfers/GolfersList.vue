<template>
    
    <div class="p-3 mt-10 sm:w-3/4 sm:mx-auto">
        <p class="mt-10 text-5xl">Golfers</p>
        <!-- SEARCH/CLEAR | ADD GOLFER -->
        <div class="flex flex-col justify-between mt-5 mb-10 lg:flex-row">
            <div class="flex mb-6 lg:mb-0">
                <div class="relative mr-2">
                    <input 
                        class="w-40 py-1 pr-3 border border-gray-500 rounded pl-7"
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

            <div class="flex items-center space-x-2">
              <div id="export"></div>
              <button 
                v-if="role==='admin'"
                class="flex items-center px-3 py-1 text-white bg-green-800 rounded hover:bg-green-900"
                @click="newGolferModal = !newGolferModal"
              >   
                <v-icon 
                  class="mr-1 -ml-1"
                  name="hi-plus-sm" 
                  fill="#fff"
                  scale="1.2" 
                />
                Add a new golfer
              </button>
            </div>
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
                You are about to delete {{ golferFullName }}, are you sure?
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
                            class="capitalize field-base" 
                            v-model="selectedRow.first_name" 
                            required
                        >
                    </div>
                    <div class="flex-1 my-2">
                        <label for="last_name" class="block mb-1 text-xs">Last name</label>
                        <input 
                            type="text" 
                            id="last_name" 
                            class="capitalize field-base" 
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
            :title="`${newGolfer.first_name ? newGolfer.first_name : ''} ${newGolfer.last_name ? newGolfer.last_name : ''}`"
        >
            <form @submit.prevent="addNewGolfer">
                <div class="flex flex-col sm:gap-3 sm:flex-row">
                    <div class="flex-1 my-2">
                        <label for="first_name" class="block mb-1 text-xs">First name</label>
                        <input 
                            type="text" 
                            id="first_name" 
                            class="capitalize field-base" 
                            v-model="newGolfer.first_name" 
                            required
                        >
                    </div>
                    <div class="flex-1 my-2">
                        <label for="last_name" class="block mb-1 text-xs">Last name</label>
                        <input 
                            type="text" 
                            id="last_name" 
                            class="capitalize field-base" 
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
    </div>

</template>
<script>
    import Modal from '../ui/Modal.vue';
    export default {
        components: {
            Modal
        },
        props: {
            role: String
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
                this.table.order([9,'desc']).search('').draw()
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

                _this.table.buttons().container().appendTo( $('#export' ) );
            },
            async updateGolfer() {
                try {
                    const res = await axios.post(`/golfers/${this.selectedRow.id}/edit`, this.selectedRow)
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
                this.newGolferModal = false
                this.selectedRow = {}
                this.newGolfer = {}
            }
        },
        mounted() {
            const _this = this;
            _this.table = $('#dt_players_list').DataTable({
                responsive: true,
                scrollX: true,
                rowId: 'id',
                iDisplayLength: 30,
                dom: 'Bfrtip',
                columnDefs: [
                    { orderable: true, targets: [4,5] },
                    { orderable: false, targets: '_all' }
                ],
                order: [5, 'desc'],
                buttons: [{
                    extend: 'pdfHtml5',
                    text: "PDF Export",
                    title: 'Black League Handicaps',
                    exportOptions: {
                        columns: [ 2,3,4,5 ],
                        modifier: { order: 'index' }
                    },
                    className: 'px-3 py-1 text-white bg-gray-500 rounded hover:bg-gray-600',
                    customize: function (doc) {
                        doc.content[1].table.widths = 
                        Array(doc.content[1].table.body[0].length + 1).join('*').split('');
                    }
                }],
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
                        className: 'text-left capitalize',
                    },
                    {
                        data: 'last_name',
                        title: 'Last Name',
                        className: 'text-left capitalize',
                    },
                    {
                        data: 'handicap',
                        title: 'Handicap',
                        className: 'text-left',
                        render: function(data, type, row) {
                            return `<a href="/rounds/${row.id}" class="flex items-center justify-between w-20 p-1 bg-white border rounded shadow-sm cursor-pointer handicapcell">
                                        ${row.handicap}
                                        <svg class="ov-icon" aria-hidden="true" width="23.04" height="23.04" viewBox="0.48 0.48 23.04 23.04" fill="#03543F" style="font-size: 1.44em;"><path fill="none" d="M0 0h24v24H0V0z"></path><path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h4c.55 0 1-.45 1-1s-.45-1-1-1H4v-6h18V6c0-1.1-.9-2-2-2zm0 4H4V6h16v2zm-5.07 11.17l-2.12-2.12a.996.996 0 10-1.41 1.41l2.83 2.83c.39.39 1.02.39 1.41 0l5.66-5.66a.996.996 0 10-1.41-1.41l-4.96 4.95z"></path></svg>
                                    </a>`;
                        }
                    },
                    {
                        data: 'number_of_rounds',
                        title: 'Rounds',
                        className: 'text-left',
                    },
                    {
                        data: 'email',
                        title: 'Email',
                        className: 'text-left',
                    },
                    {
                        data: 'phone',
                        title: 'Phone',
                        className: 'text-left',
                    },
                    {
                        visible: _this.role==='admin',
                        render: function(data, type, row) {
                            return `<div data-action="edit_golfer" class="cursor-pointer tablerow_clickevent_target">
                                        <svg id="edit_golfer" class="ov-icon" aria-hidden="true" width="24.96" height="24.96" viewBox="-48.96 -80.96 673.92 673.92" fill="#222F3D" style="font-size: 1.56em;"><path d="M402.3 344.9l32-32c5-5 13.7-1.5 13.7 5.7V464c0 26.5-21.5 48-48 48H48c-26.5 0-48-21.5-48-48V112c0-26.5 21.5-48 48-48h273.5c7.1 0 10.7 8.6 5.7 13.7l-32 32c-1.5 1.5-3.5 2.3-5.7 2.3H48v352h352V350.5c0-2.1.8-4.1 2.3-5.6zm156.6-201.8L296.3 405.7l-90.4 10c-26.2 2.9-48.5-19.2-45.6-45.6l10-90.4L432.9 17.1c22.9-22.9 59.9-22.9 82.7 0l43.2 43.2c22.9 22.9 22.9 60 .1 82.8zM460.1 174L402 115.9 216.2 301.8l-7.3 65.3 65.3-7.3L460.1 174zm64.8-79.7l-43.2-43.2c-4.1-4.1-10.8-4.1-14.8 0L436 82l58.1 58.1 30.9-30.9c4-4.2 4-10.8-.1-14.9z"></path></svg>
                                    </div>`;
                        }
                    },
                    {
                        visible: _this.role==='admin',
                        render: function(data, type, row) {
                            return `<div data-action="delete_golfer" class="cursor-pointer tablerow_clickevent_target">
                                        <svg id="delete_golfer" class="ov-icon" aria-hidden="true" width="24.96" height="24.96" viewBox="0 0 24 24" fill="#9B1C1C" style="font-size: 1.56em;"><path fill="none" d="M0 0h24v24H0z"></path><path d="M17 6h5v2h-2v13a1 1 0 01-1 1H5a1 1 0 01-1-1V8H2V6h5V3a1 1 0 011-1h8a1 1 0 011 1v3zm1 2H6v12h12V8zm-4.586 6l1.768 1.768-1.414 1.414L12 15.414l-1.768 1.768-1.414-1.414L10.586 14l-1.768-1.768 1.414-1.414L12 12.586l1.768-1.768 1.414 1.414L13.414 14zM9 4v2h6V4H9z"></path></svg>
                                    </div>`;
                        }
                    }
                ]
            })
        }
    }
</script>