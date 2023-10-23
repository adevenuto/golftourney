<template>
    
    <div class="w-3/4 mt-10 sm:mx-auto">
        <p class="my-10 text-5xl">Players</p>
        
        <table id="dt-players-list" class="table text-sm table-striped hover row-border" cellspacing="0" width="100%"></table>
        
    </div>

</template>
<script>
    export default {
        data() {
            return {
                playersList: [],
                table: null,
            }
        },
        watch: {
            table: function(isSet) {
                if(isSet) {
                    this.getPlayers()
                }
            }
        },
        methods: {
            reloadTable: function() {
                this.table.clear().rows.add(this.playersList).draw();
            },
            async getPlayers() {
                try {
                    const res = await axios.get('/users')
                    if(res.data) {
                        console.log(res.data.users)
                        this.playersList = res.data.users
                        this.reloadTable()
                    } 
                } catch (err) {
                    console.error(err);
                }
            }
        },
        mounted() {
            const _this = this;
            _this.table = $('#dt-players-list').DataTable({
                responsive: true,
                scrollX: true,
                rowId: 'id',
                iDisplayLength: 30,
                data: _this.playersList,
                columns: [
                    // {
                    //     title: '',
                    //     orderable: false,
                    //     defaultContent: `<tr></tr>`
                    // },
                    {
                        data: 'id',
                        visible: false,
                    },
                    {
                        data: 'name',
                        title: 'Name',
                        className: 'text-left'
                    },
                    {
                        data: 'handicap',
                        title: 'Handicap',
                        className: 'text-left'
                    },
                    {
                        data: 'email',
                        title: 'Email',
                        className: 'text-left'
                    }
                ]
            });
        }
    }
</script>
