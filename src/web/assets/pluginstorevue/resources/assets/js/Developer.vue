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
                developer: {},
                developerId: null,
            }
        },

        created () {
            this.$root.showCrumbs = true;
            
            this.developerId = this.$route.params.id;

            this.$http.get('https://craftid.dev/api/developer/' + this.developerId).then(function(data) {
                this.developer = data.body.data[0];
                this.$root.pageTitle = this.developer.developerName;
            });
        },
    }
</script>