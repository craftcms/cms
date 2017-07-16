<template>
    <div>
        <ul>
            <li><strong>{{ developer.fullName }}</strong></li>
            <li>{{ developer.username }}</li>
            <li>{{ developer.email }}</li>
        </ul>

        <hr>

        <plugin-grid :plugins="developer.plugins"></plugin-grid>
    </div>

</template>

<script>
    import PluginGrid from './components/PluginGrid';
    import { mapGetters } from 'vuex'

    export default {
        name: 'developer',

        components: {
            PluginGrid,
        },

        data () {
            return {
                plugins: [],
                developerId: null,
            }
        },

        computed: {
            ...mapGetters({
                developer: 'developer'
            }),
        },

        created () {
            this.$root.showCrumbs = true;
            
            this.developerId = this.$route.params.id;

            this.$store.dispatch('getDeveloper', this.developerId).then((developer) => {
                this.$root.pageTitle = developer.developerName;
            });
        },
    }
</script>