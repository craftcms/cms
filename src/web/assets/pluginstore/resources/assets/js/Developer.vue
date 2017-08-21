<template>
    <div>
        <ul>
            <li>{{ developer.location }}</li>
            <!--<li>{{ developer.plugins.length }} plugins</li>-->
        </ul>

        <hr>

        <plugin-index :plugins="developer.plugins"></plugin-index>
    </div>

</template>

<script>
    import PluginIndex from './components/PluginIndex';
    import { mapGetters } from 'vuex'

    export default {
        components: {
            PluginIndex,
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