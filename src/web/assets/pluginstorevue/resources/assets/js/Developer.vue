<template>
    <div>
        <ul>
            <li><strong>{{ developer.fullName }}</strong></li>
            <li>{{ developer.username }}</li>
            <li>{{ developer.email }}</li>
        </ul>

        <hr>
        <plugin-search @showResults="showingSearchResults = true" @hideResults="showingSearchResults = false" :plugins="developer.plugins"></plugin-search>

        <plugin-grid v-if="!showingSearchResults" :plugins="developer.plugins"></plugin-grid>
    </div>

</template>

<script>
    import PluginGrid from './components/PluginGrid';
    import PluginSearch from './components/PluginSearch';
    import { mapGetters } from 'vuex'

    export default {
        components: {
            PluginGrid,
            PluginSearch,
        },

        data () {
            return {
                showingSearchResults: false,
            }
        },

        computed: {
            ...mapGetters({
                developer: 'developer'
            }),
        },

        created () {
            this.$root.showCrumbs = true;
            
            let developerId = this.$route.params.id;

            this.$root.loading = true;

            this.$store.dispatch('getDeveloper', developerId).then((developer) => {
                this.$root.pageTitle = developer.developerName;
                this.$root.loading = false;
            });
        },
    }
</script>