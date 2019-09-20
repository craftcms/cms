<template>
    <div>
        <plugin-grid :plugins="plugins"></plugin-grid>
    </div>
</template>

<script>
    import PluginGrid from './PluginGrid'

    export default {
        components: {
            PluginGrid,
        },

        props: ['plugins'],
    }
</script>
