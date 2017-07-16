<template>
    <div>
        <ul>
            <li><strong>{{ developer.fullName }}</strong></li>
            <li>{{ developer.username }}</li>
            <li>{{ developer.email }}</li>
        </ul>

        <hr>

        <plugin-grid :plugins="developer.plugins" :plugin-url-prefix="'/developer/' + developerId + '/'"></plugin-grid>
    </div>

</template>

<script>
    import PluginGrid from './components/PluginGrid';

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
            developer() {
                let developer = this.$store.getters.developer;

                if(developer) {
                    this.$root.pageTitle = developer.developerName;
                }

                return developer;
            }
        },

        created () {
            this.$root.showCrumbs = true;
            
            this.developerId = this.$route.params.id;

            this.$store.dispatch('getDeveloper', this.developerId);
        },
    }
</script>