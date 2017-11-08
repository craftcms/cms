<template>
    <div>
        <ul>
            <li>{{ developer.location }}</li>
            <!--<li>{{ developer.plugins.length }} plugins</li>-->
        </ul>

        <hr>

        <plugin-index :plugins="plugins"></plugin-index>
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