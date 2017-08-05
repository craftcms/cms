<template>

	<div v-if="indexPluginGroup">
		<plugin-grid :columns="4" :plugins="getPluginsByIds(indexPluginGroup.plugins)"></plugin-grid>
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
                getIndexPluginGroup: 'getIndexPluginGroup',
                getPluginsByIds: 'getPluginsByIds',
            }),

			indexPluginGroup() {
                let indexPluginGroup = this.getIndexPluginGroup(this.$route.params.id);

                if(indexPluginGroup) {
                    this.$root.pageTitle = indexPluginGroup.title;
                }

                return indexPluginGroup;
			}
        },

        created () {
            this.$root.showCrumbs = true;
        },
    }
</script>