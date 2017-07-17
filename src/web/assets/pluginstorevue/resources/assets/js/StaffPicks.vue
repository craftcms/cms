<template>
    <div>
        <plugin-grid :plugins="staffPicks"></plugin-grid>
    </div>

</template>

<script>
    import PluginGrid from './components/PluginGrid';
    import { mapGetters } from 'vuex'

    export default {
        name: 'developer',

        components: {
            PluginGrid,
        },

        computed: {
            ...mapGetters({
                staffPicks: 'staffPicks'
            }),
        },

        created () {
            this.$root.showCrumbs = true;

            this.$root.pageTitle = 'Staff Picks';
        },
    }
</script>