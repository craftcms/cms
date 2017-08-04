<template>

    <div>
        <div :class="cssClass" v-if="plugins && plugins.length > 0">
            <div class="cssgrid-box" v-for="plugin in plugins">
                <plugin-card :plugin="plugin" @click="showPlugin(plugin)"></plugin-card>
            </div>
        </div>
    </div>

</template>


<script>

    import PluginCard from './PluginCard';
    import PluginDetails from './PluginDetails';

    export default {
        props: ['plugins', 'columns'],

        components: {
            PluginCard,
            PluginDetails,
        },

        data () {
            return {
            }
        },

        computed: {
            cssClass() {
                let cssClass = 'cssgrid-plugins';

                if(this.columns) {
                    cssClass += ' cssgrid-plugins-'+this.columns;
                }

                return cssClass;
            }
        },

        methods: {
            showPlugin: function(plugin) {
                this.$root.showPlugin(plugin);
            },
        },
    }
</script>
