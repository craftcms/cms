<template>
    <div>
        <div class="ps-grid-wrapper has-sidebar">
            <div class="ps-grid-sidebar">
                <div class="developer-card">
                    <template v-if="loading">
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
    import PluginIndex from './components/PluginIndex';
    import { mapGetters } from 'vuex'

    export default {

        data() {
            return {
                plugins: [],
                loading: false,
            }
        },

        components: {
            PluginIndex,
        },

        computed: {

            ...mapGetters({
                developer: 'developer'
            }),

        },

        methods: {

            onPluginStoreDataLoaded() {
                let developerId = this.$route.params.id;

                this.$root.loading = true;
                this.loading = true;

                this.plugins = this.$store.getters.getPluginsByDeveloperId(developerId);

                this.$store.dispatch('getDeveloper', developerId)
                    .then(developer => {
                        this.$root.pageTitle = this.$options.filters.escapeHtml(developer.developerName);
                        this.$root.loading = false;
                        this.loading = false;
                    })
                    .catch(response => {
                        this.$root.loading = false;
                        this.loading = false;
                    });
            }

        },

        mounted () {
            if(!this.$root.pluginStoreDataLoaded) {
                this.$root.$on('pluginStoreDataLoaded', function() {
                    this.onPluginStoreDataLoaded();
                }.bind(this));
            } else {
                this.onPluginStoreDataLoaded();
            }

            this.$root.crumbs = [
                {
                    label: this.$options.filters.t("Plugin Store", 'app'),
                    path: '/',
                }
            ];
        },

    }
</script>