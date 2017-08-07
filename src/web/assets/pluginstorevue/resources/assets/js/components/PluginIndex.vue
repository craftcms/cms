<template>

	<div>
		<plugin-search @showResults="showingSearchResults = true" @hideResults="showingSearchResults = false" :plugins="pluginsToRender" :sort.sync="sort"></plugin-search>

		<plugin-grid v-if="!showingSearchResults" :plugins="pluginsToRender" :columns="4"></plugin-grid>
	</div>

</template>

<script>
    import PluginGrid from './PluginGrid';
    import PluginSearch from './PluginSearch';

    export default {

        props: ['plugins'],

        components: {
            PluginGrid,
            PluginSearch,
        },

        data () {
            return {
                showingSearchResults: false,
                sort: 'name:asc',
            }
        },

        computed: {
            pluginsToRender() {

                if(!this.plugins) {
                    return [];
                }

                let plugins = this.plugins;

                let sortOptions = this.sort.split(':');
                let sortKey = sortOptions[0];
                let sortOrder = sortOptions[1];

                function compareASC(a,b) {
                    if (a[sortKey] < b[sortKey])
                        return -1;
                    if (a[sortKey] > b[sortKey])
                        return 1;
                    return 0;
                }

                function compareDESC(a,b) {
                    if (a[sortKey] > b[sortKey])
                        return -1;
                    if (a[sortKey] < b[sortKey])
                        return 1;
                    return 0;
                }

                if(sortOrder === 'desc') {
                    plugins.sort(compareDESC);
                } else {
                    plugins.sort(compareASC);
                }

                return plugins;
            }
        },
    }
</script>
