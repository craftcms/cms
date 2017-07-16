<template>
    <div>

        <h2>Staff Picks</h2>
        <plugin-grid :plugins="staffPicks"></plugin-grid>

        <h2>All plugins</h2>
        <plugin-grid :plugins="plugins"></plugin-grid>

    </div>
</template>


<script>
    import PluginGrid from './PluginGrid';
    import { mapGetters, mapActions } from 'vuex'

    export default {
        name: 'allPlugins',

        components: {
            PluginGrid,
        },

        computed: mapGetters({
            plugins: 'allPlugins',
            staffPicks: 'staffPicks'
        }),

        methods: mapActions([
            'addToCart'
        ]),

        created: function() {
            this.$store.dispatch('getAllPlugins')
        },
    }
</script>
