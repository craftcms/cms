<template>
    <div>
        <input class="text fullwidth" id="searchQuery" name="searchQuery" type="text" placeholder="Search plugins" v-model="searchQuery">
        <br />
        <br />

        <plugin-grid :plugins="pluginsToRender"></plugin-grid>

        <div v-show="showSpinner">
            Loading…
        </div>

    </div>
</template>

<script>
    import PluginGrid from './PluginGrid';

    export default {
        name: 'pluginSearch',
        components: {
            PluginGrid,
        },
        data () {
            return {
                searchQuery: '',
                plugins: [],
                showSpinner: 1,
            }
        },
        computed: {
            pluginsToRender() {
                let self = this;

                let searchQuery = this.searchQuery;

                if(!searchQuery) {
                    this.$emit('hideResults');
                    return [];
                }

                this.$emit('showResults');

                return this._.filter(this.plugins, function(o) {
                    if(o.name && self._.includes(o.name.toLowerCase(), searchQuery.toLowerCase())) {
                        return true;
                    }

                    if(o.shortDescription && self._.includes(o.shortDescription.toLowerCase(), searchQuery.toLowerCase())) {
                        return true;
                    }

                    if(o.description && self._.includes(o.description.toLowerCase(), searchQuery.toLowerCase())) {
                        return true;
                    }

                    if(o.developerName && self._.includes(o.developerName.toLowerCase(), searchQuery.toLowerCase())) {
                        return true;
                    }

                    if(o.developerUrl && self._.includes(o.developerUrl.toLowerCase(), searchQuery.toLowerCase())) {
                        return true;
                    }
                });
            },
        },
        created: function() {
            this.$http.get('https://craftid.dev/api/plugins').then(function(data) {
                this.plugins = this.plugins.concat(data.body.data);
                this.showSpinner = 0;
            });
        },
    }
</script>

<style>

    /* #container hack required for modal overlays */

    #container {
        position: static !important;
    }

</style>