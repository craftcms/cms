<template>
	<div>
		<div id="thank-you">
			<div id="graphic" class="spinner big success"></div>
			<h2>{{ "Thank You!"|t('app') }}</h2>
			<p class="light">{{ "Your order has been processed successfully."|t('app') }}</p>
			<p>
				<a :href="managePluginsUrl" class="btn submit">{{ "Manage plugins"|t('app') }}</a>
			</p>
		</div>
	</div>
</template>

<script>
    export default {

        computed: {

            managePluginsUrl() {
                return Craft.getCpUrl('settings/plugins')
            }

        },

    }
</script>
