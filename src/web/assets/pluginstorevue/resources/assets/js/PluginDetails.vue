<template>
    <div v-if="plugin">
        <div class="plugin-details-header">
            <div class="plugin-icon-large">
                <img v-if="plugin.iconUrl" :src="plugin.iconUrl" height="60" />
            </div>

            <div class="description">
                <h2>{{ plugin.name }}</h2>

                <p>{{ plugin.shortDescription }}</p>

                <p>
                    <router-link :to="'/developer/' + plugin.developerId">{{ plugin.developerName }}</router-link>
                </p>
            </div>

            <div class="buttons">

                <div v-if="plugin.price != '0.00'">
                    <a :href="installUrl" class="btn">Try</a>

                    <a @click="buy(plugin)" class="btn submit">Buy ${{ plugin.price }}</a>
                </div>
                <div v-else>
                    <a :href="installUrl" class="btn submit">Install</a>
                </div>

            </div>
        </div>

        <hr>

        <div class="plugin-details-body">
            <div class="plugin-description">
                <h2>Description</h2>
                <div v-html="description"></div>

                <h2>Screenshots</h2>

                <div v-for="screenshot in plugin.screenshots">
                    <img :src="screenshot" height="150" />
                </div>

            </div>

            <div class="plugin-sidebar">
                <div class="plugin-meta">
                    <ul>
                        <li><span>Version</span> <strong>X.X.X</strong></li>
                        <li><span>Last update</span> <strong>—</strong></li>
                        <li><span>Active installs</span> <strong>XXX,XXX</strong></li>
                        <li><span>Compatibility</span> <strong>Craft X</strong></li>
                        <li><span>Categories</span> <strong>—</strong></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<script>

    import { mapGetters, mapActions } from 'vuex'

    var marked = require('marked');

    export default {
        name: 'pluginDetails',
        props: ['pluginId'],
        computed: {
            ...mapGetters({
                plugins: 'allProducts',
            }),
            plugin() {
                return this.plugins.find(plugin => {
                    if(plugin.id == this.pluginId) {
                        return plugin;
                    }
                })
            },
            description() {
                if(this.plugin.description && this.plugin.description.length > 0) {
                    return marked(this.plugin.description, { sanitize: true });
                }
            },

            developerUrl() {
                return Craft.getCpUrl('plugin-store/developer/' + this.plugin.developerId);
            },

            installUrl() {
                return Craft.getCpUrl('plugin-store/install');
            }
        },
        methods: {
            ...mapActions([
               'addToCart'
            ]),
            buy(plugin) {
                this.$store.dispatch('addToCart', plugin);

                this.$emit('buy');

                this.$root.$refs.cartButton.openModal();
            },
        },

        created() {
            this.$store.dispatch('getAllProducts')
        }
    }
</script>

<style scoped>
    .plugin-details-header {
        display: flex;
    }
    .plugin-details-header .plugin-icon-large {
        margin-right: 14px;
    }


    .plugin-details-header { display: flex; }
    .plugin-details-header .description { flex-grow:1; margin-left: 14px; }
    .plugin-details-header .description h2 { margin-bottom: 10px; }
    .plugin-details-header .description p { margin: 0.4em 0; }
    .plugin-details-header .buttons { margin-top:0; }

    .plugin-details-body { display: flex;  height: 500px; overflow: auto; }
    .plugin-details-body .plugin-description { flex-grow: 1; }
    .plugin-details-body .plugin-sidebar { width: 300px; flex-shrink:0; margin-left: 24px; }
    .plugin-details-body .plugin-sidebar .plugin-meta { border: 1px solid #eee; border-radius: 4px; padding: 24px; }
    .plugin-details-body .plugin-sidebar .plugin-meta ul li { display: flex; border-bottom: 1px solid #eee; padding: 7px 0; }
    .plugin-details-body .plugin-sidebar .plugin-meta ul li:last-child { border-bottom:0; }
    .plugin-details-body .plugin-sidebar .plugin-meta ul li span,
    .plugin-details-body .plugin-sidebar .plugin-meta ul li strong { flex-grow:1; }
    .plugin-details-body .plugin-sidebar .plugin-meta ul li strong { text-align: right; }

</style>
