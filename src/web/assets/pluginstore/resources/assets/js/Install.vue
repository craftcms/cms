<template>
	<div>
		<div :class="{hidden: !loading}">
			Loading…
		</div>

		<div v-if="plugin">
			<div class="spinner"></div>
			<p>Installing {{ plugin.title }}…</p>
		</div>
	</div>
</template>

<script>

    import { mapGetters, mapActions } from 'vuex'

    export default {
        data() {
            return {
                plugin: null,
            }
        },

		computed: {
            loading() {
                return !this.$root.craftIdDataLoaded;
			}
		},

		methods: {
          	onPluginStoreDataLoaded() {
				if(this.$root.craftIdDataLoaded) {
				    this.install();
				}
			},

			onCraftIdDataLoaded() {
                if(this.$root.pluginStoreDataLoaded) {
                    this.install();
                }
			},

			install() {
          	    if(!this.installing) {
          	        this.installing = true;
					let pluginId = parseInt(this.$route.params.id);
					let plugin = this.$store.getters.getPluginById(pluginId);

					this.plugin = plugin;

					this.$store.dispatch('installPlugin', { plugin })
						.then(data => {
							console.log('plugin installed');
							this.$root.displayNotice(Craft.t('app', 'Plugin installed.'));
							this.$router.push({ path: '/' });
						})
						.catch(response => {

						});
                }
			}
		},
		mounted() {
            this.installing = false;

            if(!this.$root.pluginStoreDataLoaded) {
				this.$root.$on('pluginStoreDataLoaded', function() {
					this.onPluginStoreDataLoaded();
				}.bind(this));
            } else {
                this.onPluginStoreDataLoaded();
			}

            if(!this.$root.craftIdDataLoaded) {
				this.$root.$on('craftIdDataLoaded', function() {
					this.onCraftIdDataLoaded();
				}.bind(this));
            } else {
                this.onCraftIdDataLoaded();
			}
		},
    };
</script>