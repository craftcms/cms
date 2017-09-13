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

        created() {
            this.$root.pageTitle = 'Installed Plugins';
            this.$root.showCrumbs = true;
        },
    }
</script>
