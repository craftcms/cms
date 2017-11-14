<template>
	<div>
		<template v-if="craftData.CraftEdition == edition">
			<template v-if="craftData.licensedEdition == edition">
				<div class="license-status installed" data-icon="check">{{ "Installed"|t('app') }}</div>
			</template>
			<template v-else>
				<div class="license-status installed" data-icon="check">{{ "Installed as a trial"|t('app') }}</div>
			</template>
		</template>

		<template v-else-if="craftData.licensedEdition == edition">
			<div class="license-status licensed" data-icon="check">{{ "Licensed"|t('app') }}</div>
		</template>
	</div>
</template>

<script>
    import { mapGetters } from 'vuex'

	export default {

	    props: ['edition'],

        computed: {

            ...mapGetters({
                craftData: 'craftData',
            })

        }

	}
</script>