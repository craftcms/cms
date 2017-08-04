<template>
    <div>
        <div class="toolbar">
            <div class="flex">
                <div class="flex-grow texticon search icon clearable">
                    <input class="text fullwidth" id="searchQuery" name="searchQuery" type="text" :placeholder="'Search plugins'|t('app')" v-model="searchQuery">
                    <div class="clear" :class="{ hidden: searchQuery.length == 0 }" @click="searchQuery = ''" title="Clear"></div>
                </div>
                <div class="spinner" v-bind:class="{ invisible: !showSpinner }"></div>
            </div>
        </div>

        <plugin-grid :plugins="pluginsToRender"></plugin-grid>

    </div>
</template>

<script>
    import PluginGrid from './PluginGrid';

    export default {
        name: 'pluginSearch',
        components: {
            PluginGrid,
        },
        props: ['plugins'],
        data () {
            return {
                searchQuery: '',
                showSpinner: false,
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
    }
</script>
