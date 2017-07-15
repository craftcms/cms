<template>

    <div>
        <div class="row plugin-grid" v-if="plugins && plugins.length > 0">
            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-4 plugin" v-for="plugin in plugins">
                <plugin-card :plugin="plugin" @click="openModal(plugin)"></plugin-card>
            </div>
        </div>

        <modal :show.sync="showModal" :on-close="closeModal">
            <div slot="body">
                <plugin-details :plugin="selectedPlugin" @buy="onBuy"></plugin-details>
            </div>
        </modal>
    </div>

</template>


<script>

    import PluginCard from './PluginCard';
    import PluginDetails from './PluginDetails';
    import Modal from './Modal.vue'

    export default {
        name: 'pluginGrid',
        props: ['plugins', 'pluginUrlPrefix'],
        components: {
            PluginCard,
            PluginDetails,
            Modal,
        },
        data () {
            return {
                showModal: false,
                selectedPlugin: null,
            }
        },
        methods: {
            openModal: function(plugin) {
                this.selectedPlugin = plugin;
                this.showModal = true;
            },
            closeModal: function() {
                this.selectedPlugin = null;
                this.showModal = false;
            },
            onBuy() {
               console.log('onBuy');

               this.closeModal();
            }
        },
    }
</script>

<style>

/*    .plugin-grid {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        margin: 0 -7px;
    }

    .plugin {
        box-sizing: border-box;
        flex: 0 0 auto;
        width: 33.33%;
    }*/

</style>