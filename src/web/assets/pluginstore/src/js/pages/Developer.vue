<template>
    <div>
        <h1 v-if="developer">{{developer.developerName}}</h1>
        <div class="ps-grid-wrapper has-sidebar">
            <div class="ps-grid-sidebar">
                <div class="text-center">
                    <template v-if="loading || !developer">
                        <div class="spinner mt-8"></div>
                    </template>

                    <template v-else>
                        <developer-card :developer="developer"></developer-card>
                    </template>
                </div>
            </div>

            <div class="ps-grid-main">
                <plugin-index :plugins="plugins" columns="3"></plugin-index>
            </div>
        </div>
    </div>
</template>

<script>
    import {mapState} from 'vuex'
    import PluginIndex from '../components/PluginIndex'
    import DeveloperCard from '../components/DeveloperCard'

    export default {

        data() {
            return {
                plugins: [],
                loading: false,
            }
        },

        components: {
            PluginIndex,
            DeveloperCard,
        },

        computed: {

            ...mapState({
                developer: state => state.pluginStore.developer,
            }),

        },

        mounted() {
            let developerId = this.$route.params.id

            this.loading = true

            this.plugins = this.$store.getters['pluginStore/getPluginsByDeveloperId'](developerId)

            this.$store.dispatch('pluginStore/getDeveloper', developerId)
                .then(developer => {
                    this.$root.loading = false
                    this.loading = false
                })
                .catch(response => {
                    this.$root.loading = false
                    this.loading = false
                })
        },

    }
</script>