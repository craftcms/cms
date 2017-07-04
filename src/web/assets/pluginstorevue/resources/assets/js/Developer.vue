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
    import PluginGrid from './PluginGrid';
    export default {
        name: 'developer',

        components: {
            PluginGrid,
        },

        props: ['developerId'],

        data () {
            return {
                plugins: [],
                developer: {},
            }
        },

        created: function() {
/*            this.$http.get('https://craftid.dev/api/plugins').then(function(data) {
                this.plugins = this.plugins.concat(data.body.data).slice(0,9);
            });*/

            this.$http.get('https://craftid.dev/api/developer/' + this.developerId).then(function(data) {
                this.developer = data.body.data[0];
            });
        },
    }
</script>