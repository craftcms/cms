<template>
    <div>
        <plugin-search @showResults="showingSearchResults = true" @hideResults="showingSearchResults = false"></plugin-search>

        <div v-if="!showingSearchResults" class="row">
            <div class="col-xs-12 col-sm-8">
                <h2>Staff Picks</h2>
                <plugin-grid :plugins="plugins"></plugin-grid>
                <h2>Active Trials</h2>
                <plugin-grid :plugins="activeTrials"></plugin-grid>
            </div>
            <div class="col-xs-12 col-sm-4">
                <h2>Categories</h2>
                <ul>
                    <li><a href="#">Analytics</a></li>
                    <li><a href="#">Customer Support</a></li>
                    <li><a href="#">Developer Tools</a></li>
                    <li><a href="#">E-commerce</a></li>
                    <li><a href="#">File Management</a></li>
                    <li><a href="#">Sales</a></li>
                    <li><a href="#">Marteking</a></li>
                    <li><a href="#">Security</a></li>
                    <li><a href="#">Templates</a></li>
                </ul>
            </div>
        </div>
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
                activeTrials: [],
            }
        },
        created: function() {
            this.$http.get('https://craftid.dev/api/plugins').then(function(data) {
                var plugins = this.plugins.concat(data.body.data);
                this.plugins = plugins.slice(0,6);
                this.activeTrials = plugins.slice(6,9);
            });
        },
    }
</script>
