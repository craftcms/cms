<template>
    <div>
        <div class="toolbar">
            <div class="flex">
                <div class="flex-grow texticon search icon clearable">
                    <input class="text fullwidth" id="searchQuery" name="searchQuery" type="text" :placeholder="'Search plugins'|t('app')" v-model="searchQuery">
                    <div class="clear" :class="{ hidden: searchQuery.length == 0 }" @click="searchQuery = ''" title="Clear"></div>
                </div>

                <template v-if="sort">
                    <sort-menu-btn :attributes="sortMenuBtn.attributes" :value="sort" @update:value="val => $emit('update:sort', val)" />
                </template>

                <div class="spinner" v-bind:class="{ invisible: !showSpinner }"></div>
            </div>
        </div>

        <plugin-grid :plugins="pluginsToRender" :columns="4"></plugin-grid>
    </div>
</template>

<script>
    import filter from 'lodash/filter';
    import includes from 'lodash/includes';
    import PluginGrid from './PluginGrid';
    import SortMenuBtn from './SortMenuBtn';

    export default {

        components: {
            PluginGrid,
            SortMenuBtn,
        },

        props: ['plugins', 'sort'],

        data () {
            return {
                searchQuery: '',
                showSpinner: false,

                selectedAttribute: null,
                selectedDirection: null,

                sortMenuBtn: {
                    attributes: {
                        name: "Name",
                        price: "Price",
                    }
                },
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

                return filter(this.plugins, o => {
                    if(o.name && includes(o.name.toLowerCase(), searchQuery.toLowerCase())) {
                        return true;
                    }

                    if(o.shortDescription && includes(o.shortDescription.toLowerCase(), searchQuery.toLowerCase())) {
                        return true;
                    }

                    if(o.description && includes(o.description.toLowerCase(), searchQuery.toLowerCase())) {
                        return true;
                    }

                    if(o.developerName && includes(o.developerName.toLowerCase(), searchQuery.toLowerCase())) {
                        return true;
                    }

                    if(o.developerUrl && includes(o.developerUrl.toLowerCase(), searchQuery.toLowerCase())) {
                        return true;
                    }
                });
            },

        },

    }
</script>
