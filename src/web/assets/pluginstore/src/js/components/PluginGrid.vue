<template>
    <div>
        <div :class="cssClass" v-if="plugins && plugins.length > 0">
            <div class="ps-grid-box" v-for="plugin in plugins">
                <plugin-card :plugin="plugin" @click="showPlugin(plugin)"></plugin-card>
            </div>
        </div>
    </div>
</template>

<script>
    import PluginCard from './PluginCard'

    export default {

        components: {
            PluginCard,
        },

        props: ['plugins', 'columns'],

        computed: {

            cssClass() {
                let cssClass = 'ps-grid-plugins'

                if (this.columns) {
                    cssClass += ' ps-grid-plugins-' + this.columns
                }

                return cssClass
            }

        },

        methods: {

            showPlugin(plugin) {
                this.$root.showPlugin(plugin)
            },

        },

    }
</script>
