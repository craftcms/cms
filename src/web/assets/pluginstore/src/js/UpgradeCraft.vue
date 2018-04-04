<template>
    <div ref="upgradecraft" v-if="craftData && cart" id="upgrade-craft">
        <div id="upgrade-craft-compare" class="body">
            <table class="data fullwidth">
                <thead>
                <tr class="logos">
                    <th>
                        <img :src="craftData.craftLogo" width="70" height="70" />
                    </th>
                    <th scope="col">
                        <h1 class="logo">Solo</h1>
                        <p>{{ "For personal projects." }}</p>
                    </th>
                    <th scope="col">
                        <h1 class="logo">Pro</h1>
                        <p>{{ "For everything else." }}</p>
                    </th>
                </tr>
                <tr class="license-statuses">
                    <td></td>
                    <td><craft-status-badge :edition="craftData.CraftSolo" /></td>
                    <td><craft-status-badge :edition="craftData.CraftPro" /></td>
                </tr>
                <tr class="price">
                    <th scope="row" class="feature"></th>
                    <td>{{ "Free" }}</td>
                    <td v-if="craftData.editions">{{ craftData.editions.pro.price|currency }}</td>
                </tr>
                <tr class="buybtns">
                    <td></td>
                    <td></td>
                    <td>
                        <div class="btngroup">
                            <template v-if="craftData.licensedEdition < craftData.CraftPro">
                                <template v-if="!isCmsEditionInCart('pro')">
                                    <div @click="buyCraft('pro')" class="btn submit">Buy now</div>
                                </template>
                                <template v-else>
                                    <div class="btn submit disabled">Added to cart</div>
                                </template>
                            </template>


                            <template v-if="craftData.canTestEditions && craftData.CraftPro != craftData.CraftEdition && craftData.CraftPro > craftData.licensedEdition">
                                <div @click="installCraft('pro')" class="btn">Try for free</div>
                            </template>

                            <template v-if="craftData.CraftEdition === craftData.CraftPro && craftData.licensedEdition === craftData.CraftSolo">
                                <div @click="installCraft('solo')" class="btn">Uninstall</div>
                            </template>

                            <template v-if="craftData.CraftPro === craftData.licensedEdition && craftData.CraftPro != craftData.CraftEdition">
                                <div @click="installCraft('pro')" class="btn">Reinstall</div>
                            </template>

                            <div v-if="loading" class="spinner"></div>
                        </div>
                    </td>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <th class="group" colspan="3">{{ "Features" }}</th>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "Content Modeling" }} <span class="info">Includes Sections, Global sets, Category groups, Tag groups, Asset volumes, Custom fields, Entry versioning, and Entry drafts</span></th>
                    <td><span data-icon="check"></span></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "Multi-site Multi-lingual" }} <span class="info">Includes Multiple locales, Section and entry locale targeting, Content translations</span></th>
                    <td><span data-icon="check"></span></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "Cloud Storage Integration" }}</th>
                    <td><span data-icon="check"></span></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "User Accounts" }} <span class="info">Includes User accounts, User groups, User permissions, Public user registration</span></th>
                    <td></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "System Branding" }} <span class="info">Includes Custom login screen logo, Custom site icon, Custom HTML email template, Custom email message wording</span></th>
                    <td></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                <tr>
                    <th class="group" colspan="3">{{ "Support" }}</th>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "Security & Bug Fixes" }}</th>
                    <td><span data-icon="check"></span></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "Community Support (Slack, Stack Exchange)" }}</th>
                    <td><span data-icon="check"></span></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "Developer Support" }}</th>
                    <td></td>
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

        data() {
            return {
                loading: false,
            }
        },

        components: {
            CraftStatusBadge
        },

        computed: {
            ...mapGetters({
                craftData: 'craftData',
                cart: 'cart',
                isCmsEditionInCart: 'isCmsEditionInCart',
            }),
        },

        methods: {
            ...mapActions({
                addToCart: 'addToCart',
                tryEdition: 'tryEdition',
                getCraftData: 'getCraftData',
            }),

            buyCraft(edition) {
                this.loading = true

                const item = {
                    type: 'cms-edition',
                    edition: edition,
                    licenseKey: window.cmsLicenseKey,
                    autoRenew: false,
                }

                this.addToCart([item])
                    .then(() => {
                        this.loading = false
                        this.$root.openGlobalModal('cart')
                    })
                    .catch(() => {
                        this.loading = false
                    })
            },

            installCraft(edition) {
                this.loading = true

                this.tryEdition(edition)
                    .then(() =>  {
                        this.getCraftData()
                            .then(() => {
                                this.loading = false
                                this.$root.displayNotice("Craft CMS edition changed.")
                            })
                    })
                    .catch(() => {
                        this.loading = false
                        this.$root.displayError("Couldn’t change Craft CMS edition.")
                    })
            },

        },

        created () {
            this.$root.crumbs = [
                {
                    label: this.$options.filters.t("Plugin Store", 'app'),
                    path: '/',
                }
            ];

            this.$root.pageTitle = 'Upgrade Craft CMS';
        },

        mounted() {
            this.$root.$on('allDataLoaded', function() {
                Craft.initUiElements(this.$refs.upgradecraft);
            }.bind(this));
        },
    }
</script>
