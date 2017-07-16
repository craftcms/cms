<template>
    <div>
        <router-view></router-view>

        <plugin-search @showResults="showingSearchResults = true" @hideResults="showingSearchResults = false"></plugin-search>

        <div v-if="!showingSearchResults" class="row">
            <div class="col-xs-12 col-sm-8">
                <h2>Staff Picks</h2>
                <plugin-grid :plugins="staffPicks"></plugin-grid>

                <h2>Active Trials</h2>
                <plugin-grid :plugins="activeTrialProducts"></plugin-grid>
            </div>
            <div class="col-xs-12 col-sm-4">
                <h2>Categories</h2>
                <ul>
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
            activeTrialProducts: 'activeTrialProducts',
            allCategories: 'allCategories',
        }),

        created: function() {
            this.$root.pageTitle = 'Plugin Store';

            this.$store.dispatch('getStaffPicks')
        },

        mounted: function() {
            this.$root.showCrumbs = false;
        }
    }
</script>
