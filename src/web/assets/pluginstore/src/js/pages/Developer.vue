<template>
    <div>
        <div class="ps-grid-wrapper has-sidebar">
            <div class="ps-grid-sidebar">
                <div class="developer-card">
                    <template v-if="loading || !developer">
                        <div class="spinner"></div>
                    </template>

                    <template v-else>
                        <div class="avatar">
                            <img :src="developer.photoUrl" />
                        </div>

                        <ul>
                            <li><strong>{{ developer.developerName }}</strong></li>
                            <li>{{ developer.location }}</li>
                        </ul>

                        <ul class="links">
                            <li><a class="btn" :href="developer.developerUrl">{{ "Website"|t('app') }}</a></li>
                            <li><a class="btn" :href="developer.developerUrl">{{ "Contact"|t('app') }}</a></li>
                        </ul>
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

    export default {

        data() {
            return {
                plugins: [],
                loading: false,
            }
        },

        components: {
            PluginIndex: require('../components/PluginIndex'),
        },

        computed: {

            ...mapState({
                developer: state => state.pluginStore.developer,
            }),

        },

        mounted() {
            let developerId = this.$route.params.id

            this.loading = true

            this.plugins = this.$store.getters.getPluginsByDeveloperId(developerId)

            this.$store.dispatch('getDeveloper', developerId)
                .then(developer => {
                    this.$root.pageTitle = this.$options.filters.escapeHtml(developer.developerName)
                    this.$root.loading = false
                    this.loading = false
                })
                .catch(response => {
                    this.$root.loading = false
                    this.loading = false
                })

            this.$root.crumbs = [
                {
                    label: this.$options.filters.t("Plugin Store", 'app'),
                    path: '/',
                }
            ]
        },

    }
</script>