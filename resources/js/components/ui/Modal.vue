<template>
    <div @click="closeModal" class="fixed top-0 bottom-0 left-0 right-0 z-[1000] flex items-center justify-center p-6 bg-black bg-opacity-60">
        <div @click.stop class="w-full p-6 bg-white rounded shadow-lg self-baseline sm:mx-auto md:w-1/2 xl:w-1/3">
            <div class="flex items-center justify-between mb-7">
                <h1 class="text-xl" v-html="title"/>
                <v-icon 
                    @click="closeModal" 
                    
                    name="io-close-outline" 
                    scale="1.5" 
                    class="self-start cursor-pointer"
                />
            </div>
            <slot></slot>
        </div>
    </div>
</template>

<script>
    export default {
        props: ['title'],
        methods: {
            closeModal: function() {
                this.$emit('close_modal');
            }
        }
    }
</script>