<template>
    <div>
        <div class="ps-grid-wrapper has-sidebar">
            <div class="ps-grid-sidebar">
                <div class="developer-card">

                    <div class="avatar">
                        <img :src="developer.photoUrl" />
                    </div>

                    <ul>
                        <li><strong>{{ developer.developerName }}</strong></li>
                        <li>{{ developer.location }}</li>
                    </ul>

                    <ul class="links">
                        <li><a class="btn" :href="developer.developerUrl">Website</a></li>
                        <li><a class="btn" :href="developer.developerUrl">Contact</a></li>
                        <!--<li>{{ developer.plugins.length }} plugins</li>-->
                    </ul>
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

                this.plugins = this.$store.getters.getPluginsByDeveloperId(developerId);

                this.$store.dispatch('getDeveloper', developerId)
                    .then(developer => {
                        this.$root.pageTitle = developer.developerName;
                        this.$root.loading = false;
                    })
                    .catch(response => {
                        this.$root.loading = false;
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
                    label: "Plugin Store",
                    path: '/',
                }
            ];
        },

    }
</script>