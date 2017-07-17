<template>
    <div>
        <router-view></router-view>

        <plugin-search @showResults="showingSearchResults = true" @hideResults="showingSearchResults = false"></plugin-search>

        <div v-if="!showingSearchResults" class="row">
            <div class="col-xs-12 col-sm-8">
                <p class="right">
                    <router-link to="/staff-picks/">See all</router-link>
                </p>

                <h2>Staff Picks</h2>
                <plugin-grid :plugins="staffPicks.slice(0,9)"></plugin-grid>

                <template v-if="activeTrialPlugins.length > 0">
                    <h2>Active Trials</h2>
                    <plugin-grid :plugins="activeTrialPlugins"></plugin-grid>
                </template>
            </div>
            <div class="col-xs-12 col-sm-4">
                <h2>Categories</h2>
                <ul class="categories">
                    <li><router-link to="/craft">Craft</router-link></li>
                    <li v-for="category in allCategories">
                        <router-link :to="'/categories/'+category.id">{{ category.title }}</router-link>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>


<script>
    import PluginGrid from './components/PluginGrid';
    import PluginSearch from './components/PluginSearch';
    import { mapGetters } from 'vuex'

    export default {
        name: 'index',
        components: {
            PluginGrid,
            PluginSearch,
        },
        data () {
            return {
                showingSearchResults: false,
            }
        },

        computed: mapGetters({
            staffPicks: 'staffPicks',
            activeTrialPlugins: 'activeTrialPlugins',
            allCategories: 'allCategories',
        }),

        created: function() {
            this.$root.pageTitle = 'Plugin Store';
        },

        mounted: function() {
            this.$root.showCrumbs = false;
        }
    }
</script>

<style scoped>
    ul.categories li:first-child a {
        border-top: 1px solid #eee;
    }
    ul.categories li a {
        display: block;
        padding: 10px;
        border-bottom: 1px solid #eee;
    }
    ul.categories li a:hover {
        background-color: #fafafa;
        text-decoration: none;
    }
</style>