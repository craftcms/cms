<template>

    <div>
        <div class="row plugin-grid" v-if="plugins && plugins.length > 0">
            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-4 plugin" v-for="plugin in plugins">
                <plugin-card :plugin="plugin" @click="showPlugin(plugin)"></plugin-card>
            </div>
        </div>
    </div>

</template>


<script>

    import PluginCard from './PluginCard';
    import PluginDetails from './PluginDetails';

    export default {
        name: 'pluginGrid',
        props: ['plugins'],
        components: {
            PluginCard,
            PluginDetails,
        },
        data () {
            return {
            }
        },
        methods: {
            showPlugin: function(plugin) {
                this.$root.showPlugin(plugin);
            },
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