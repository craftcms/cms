<template>
    <div>
        <div class="ps-grid-plugins" v-if="plugins && plugins.length > 0">
            <div class="ps-grid-box" v-for="(plugin, key) in plugins" :key="key">
                <plugin-card :plugin="plugin" @click="showPlugin(plugin)" :trialMode="trialMode"></plugin-card>
            </div>
        </div>
    </div>
</template>

<script>
    import PluginCard from './PluginCard'

    export default {
        components: {
            PluginCard,
        },

        props: ['plugins', 'trialMode'],

        methods: {

            showPlugin(plugin) {
                this.$router.push({path: '/' + plugin.handle})
            },

        },
    }
</script>
