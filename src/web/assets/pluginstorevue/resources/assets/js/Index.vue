<template>
    <div>
        <plugin-search @showResults="showingSearchResults = true" @hideResults="showingSearchResults = false"></plugin-search>

        <plugin-grid :plugins="plugins" v-if="!showingSearchResults"></plugin-grid>
    </div>
</template>


<script>
    import PluginGrid from './PluginGrid';
    import PluginSearch from './PluginSearch';

    export default {
        name: 'index',
        components: {
            PluginGrid,
            PluginSearch,
        },
        data () {
            return {
                showingSearchResults: false,
                plugins: [],
            }
        },
        created: function() {
            this.$http.get('https://craftid.dev/api/plugins').then(function(data) {
                this.plugins = this.plugins.concat(data.body.data);
            });
        },
    }
</script>
