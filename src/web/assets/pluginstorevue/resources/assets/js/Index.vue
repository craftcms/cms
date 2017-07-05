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
                    <li v-for="category in categories">
                        <a :href="'./plugin-store/categories/'+category.id">{{ category.title }}</a>
                    </li>
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
                categories: [],
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

            this.$http.get('https://craftid.dev/api/categories').then(function(data) {
                console.log('data', data.body);
                this.categories = this.categories.concat(data.body.data);
            });
        },
    }
</script>
