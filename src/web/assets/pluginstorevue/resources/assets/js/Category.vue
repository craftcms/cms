<template>
    <div>
        <plugin-grid :plugins="plugins"></plugin-grid>
    </div>

</template>

<script>
    import PluginGrid from './PluginGrid';
    export default {
        name: 'category',

        components: {
            PluginGrid,
        },

        data () {
            return {
                plugins: [],
            }
        },

        created: function() {
            this.$http.get('https://craftid.dev/api/plugins').then(function(data) {
                var plugins = this.plugins.concat(data.body.data);
                this.plugins = plugins.slice(0,9);
            });
        },
    }
</script>