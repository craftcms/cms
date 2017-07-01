<template>
    <div>
        <plugin-search @showResults="showingSearchResults = true" @hideResults="showingSearchResults = false"></plugin-search>

        <div class="row">
            <div class="col-xs-12 col-sm-8">
                <h2>Staff Picks</h2>
                <plugin-grid :plugins="plugins" v-if="!showingSearchResults"></plugin-grid>
            </div>
            <div class="col-xs-12 col-sm-4">
                <h2>Categories</h2>
                <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Architecto asperiores beatae corporis, doloremque est explicabo facilis illo inventore iusto libero magnam maxime modi, mollitia nulla perferendis placeat quisquam reiciendis rem.</p>
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
            }
        },
        created: function() {
            this.$http.get('https://craftid.dev/api/plugins').then(function(data) {
                this.plugins = this.plugins.concat(data.body.data).slice(0,6);
            });
        },
    }
</script>
