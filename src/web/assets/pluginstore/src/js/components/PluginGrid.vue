<template>
    <div>
        <div class="ps-grid-plugins" v-if="plugins && plugins.length > 0">
            <div class="ps-grid-box" v-for="(plugin, key) in computedPlugins" :key="key">
                <plugin-card :plugin="plugin" :trialMode="trialMode"></plugin-card>
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

        props: ['plugins', 'trialMode', 'autoLimit'],

        data() {
            return {
                winWidth: null,
            }
        },

        computed: {
            computedPlugins() {
                return this.plugins.filter((plugin, key) => {
                    if (!this.autoLimit || (this.autoLimit && key < this.limit)) {
                        return true
                    }

                    return false
                })
            },

            limit() {
                let totalPlugins = this.plugins.length

                if (this.winWidth < 1400) {
                    totalPlugins = 4
                }

                const remains = totalPlugins % (this.oddNumberOfColumns ? 3 : 2)

                return totalPlugins - remains
            },

            oddNumberOfColumns() {
                if (this.winWidth < 1400 || this.winWidth >= 1824) {
                    return false
                }

                return true
            },
        },

        methods: {
            onWindowResize() {
                this.winWidth = window.innerWidth
            }
        },

        mounted() {
            this.winWidth = window.innerWidth
            this.$root.$on('windowResize', this.onWindowResize)
        },

        beforeDestroy() {
            this.$root.$off('windowResize', this.onWindowResize)
        }
    }
</script>
