<template>
    <div v-if="craftClientPlugin && craftProPlugin">

        <div id="upgrademodal-compare" class="body">
            <table class="data fullwidth">
                <thead>
                <tr class="logos">
                    <th>
                        <img :src="craftClientPlugin.iconUrl" width="70" height="70" />
                    </th>
                    <th scope="col">
                        <h1 class="logo">Personal</h1>
                        <p>{{ "For sites built by and for the developer." }}</p>
                    </th>
                    <th scope="col">
                        <h1 class="logo">Client</h1>
                        <p>{{ "For sites built for clients with only one content manager." }}</p>
                    </th>
                    <th scope="col">
                        <h1 class="logo">Pro</h1>
                        <p>{{ "For everything else." }}</p>
                    </th>
                </tr>
                <tr class="license-statuses">
                    <td></td>

                    <!--<td>{{ "statusBadge(CraftPersonal, licensedEdition)" }}</td>-->
                    <td><craft-status-badge :edition="craftData.CraftPersonal" /></td>

                    <!--<td>{{ "statusBadge(CraftClient, licensedEdition)" }}</td>-->
                    <td><craft-status-badge :edition="craftData.CraftClient" /></td>

                    <!--<td>{{ "statusBadge(CraftPro, licensedEdition)" }}</td>-->
                    <td><craft-status-badge :edition="craftData.CraftPro" /></td>
                </tr>
                <tr class="price">
                    <th scope="row" class="feature">{{ "One-Time Price" }}</th>
                    <td>{{ "Free" }}</td>

                    <!--<td>{{ "price(CraftClient, editions[CraftClient])" }}</td>-->
                    <td>{{ craftData.editions[1].formattedPrice }}</td>

                    <!--<td>{{ "price(CraftPro, editions[CraftPro])" }}</td>-->
                    <td>{{ craftData.editions[2].formattedPrice }}</td>
                </tr>
                <tr class="buybtns">
                    <td></td>
                    <td></td>

                    <!--<td>{{ "buybtn(CraftClient, editions[CraftClient], licensedEdition, canTestEditions)" }}</td>-->
                    <td>
                        <div class="btngroup">
                            <a v-if="isInCart(craftClientPlugin)" class="btn submit disabled">Added to cart</a>
                            <a v-else  @click="buy(craftClientPlugin)" class="btn submit">Buy now</a>

                            <a v-if="isInTrial(craftClientPlugin)" class="btn disabled">Try for free</a>
                            <a v-else @click="installPlugin(craftClientPlugin)" class="btn">Try for free</a>
                        </div>
                    </td>

                    <!--<td>{{ "buybtn(CraftPro, editions[CraftPro], licensedEdition, canTestEditions)" }}</td>-->
                    <td>
                        <div class="btngroup">
                            <div v-if="isInCart(craftProPlugin)" class="btn submit disabled">Added to cart</div>
                            <div v-else  @click="buy(craftProPlugin)" class="btn submit">Buy now</div>

                            <div v-if="isInTrial(craftProPlugin)" class="btn disabled">Try for free</div>
                            <div v-else @click="installPlugin(craftProPlugin)" class="btn">Try for free</div>
                        </div>
                    </td>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <th class="group" colspan="4">{{ "User Accounts" }}</th>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "Additional user accounts" }}</th>
                    <td>{{ "One Admin account" }}</td>
                    <td>{{ "One “Client” account" }}</td>
                    <td>{{ "Unlimited" }}</td>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "User groups" }}</th>
                    <td></td>
                    <td></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "User permissions" }}</th>
                    <td></td>
                    <td></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "Public user registration" }}</th>
                    <td></td>
                    <td></td>
                    <td><span data-icon="check"></span></td>
                </tr>

                <tr>
                    <th class="group" colspan="4">{{ "System Branding" }}</th>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "Custom login screen logo" }}</th>
                    <td></td>
                    <td><span data-icon="check"></span></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "Custom HTML email template" }}</th>
                    <td></td>
                    <td><span data-icon="check"></span></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "Custom email message wording" }}</th>
                    <td></td>
                    <td><span data-icon="check"></span></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                </tbody>
            </table>
        </div>

    </div>

</template>

<script>
    import { mapGetters, mapActions } from 'vuex'
    import CraftStatusBadge from './components/CraftStatusBadge';

    export default {
        components: {
            CraftStatusBadge
        },
        computed: {
            ...mapGetters({
                craftData: 'craftData',
                isInTrial: 'isInTrial',
                isInCart: 'isInCart',
            }),
            craftClientPlugin() {
                const pluginId = this.$store.getters.getCraftClientPluginId();
                return this.$store.getters.getPluginById(pluginId);
            },
            craftProPlugin() {
                const pluginId = this.$store.getters.getCraftProPluginId();
                return this.$store.getters.getPluginById(pluginId);
            },
        },

        methods: {
            ...mapActions([
               'addToCart',
               'removeFromCart',
               'installPlugin',
           ]),
            buy(plugin) {
                this.$store.dispatch('addToCart', plugin);
                this.$root.openGlobalModal('cart');
            },
        },

        created () {
            this.$root.crumbs = [
                {
                    label: "Plugin Store",
                    path: '/',
                }
            ];
            this.$root.pageTitle = 'Upgrade Craft CMS';
        },
    }
</script>