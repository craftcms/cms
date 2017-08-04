<template>
    <div>
        <plugin-grid :plugins="installedPlugins"></plugin-grid>
    </div>
</template>


<script>
    import { mapGetters } from 'vuex'
    import PluginGrid from './components/PluginGrid';

    export default {
        components: {
            PluginGrid,
        },

        computed: {
            ...mapGetters({
                installedPlugins: 'installedPlugins',
            }),
        },

        created: function() {
            this.$root.pageTitle = 'Installed Plugins';
        },

        mounted: function() {
            this.$root.showCrumbs = true;
        }
    }
</script>
