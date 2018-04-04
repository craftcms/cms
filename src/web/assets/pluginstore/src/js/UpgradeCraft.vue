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
                        <p>{{ "For personal projects."|t('app') }}</p>
                    </th>
                    <th scope="col">
                        <h1 class="logo">Pro</h1>
                        <p>{{ "For everything else."|t('app') }}</p>
                    </th>
                </tr>
                <tr class="license-statuses">
                    <td></td>
                    <td><craft-status-badge :edition="craftData.CraftSolo" /></td>
                    <td><craft-status-badge :edition="craftData.CraftPro" /></td>
                </tr>
                <tr class="price">
                    <th scope="row" class="feature"></th>
                    <td>{{ "Free"|t('app') }}</td>
                    <td v-if="craftData.editions">{{ craftData.editions.pro.price|currency }}</td>
                </tr>
                <tr class="buybtns">
                    <td></td>
                    <td></td>
                    <td>
                        <div class="btngroup">
                            <template v-if="craftData.licensedEdition < craftData.CraftPro">
                                <template v-if="!isCmsEditionInCart('pro')">
                                    <div @click="buyCraft('pro')" class="btn submit">{{ "Buy now"|t('app') }}</div>
                                </template>
                                <template v-else>
                                    <div class="btn submit disabled">{{ "Added to cart"|t('app') }}</div>
                                </template>
                            </template>


                            <template v-if="craftData.canTestEditions && craftData.CraftPro != craftData.CraftEdition && craftData.CraftPro > craftData.licensedEdition">
                                <div @click="installCraft('pro')" class="btn">{{ "Try for free"|t('app') }}</div>
                            </template>

                            <template v-if="craftData.CraftEdition === craftData.CraftPro && craftData.licensedEdition === craftData.CraftSolo">
                                <div @click="installCraft('solo')" class="btn">{{ "Uninstall"|t('app') }}</div>
                            </template>

                            <template v-if="craftData.CraftPro === craftData.licensedEdition && craftData.CraftPro != craftData.CraftEdition">
                                <div @click="installCraft('pro')" class="btn">{{ "Reinstall"|t('app') }}</div>
                            </template>

                            <div v-if="loading" class="spinner"></div>
                        </div>
                    </td>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <th class="group" colspan="3">{{ "Features"|t('app') }}</th>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "Content Modeling"|t('app') }} <span class="info">{{ "Includes Sections, Global sets, Category groups, Tag groups, Asset volumes, Custom fields, Entry versioning, and Entry drafts"|t('app') }}</span></th>
                    <td><span data-icon="check"></span></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "Multi-site Multi-lingual"|t('app') }} <span class="info">{{ "Includes Multiple locales, Section and entry locale targeting, Content translations"|t('app') }}</span></th>
                    <td><span data-icon="check"></span></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "Cloud Storage Integration"|t('app') }}</th>
                    <td><span data-icon="check"></span></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "User Accounts"|t('app') }} <span class="info">{{ "Includes User accounts, User groups, User permissions, Public user registration"|t('app') }}</span></th>
                    <td></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "System Branding"|t('app') }} <span class="info">{{ "Includes Custom login screen logo, Custom site icon, Custom HTML email template, Custom email message wording"|t('app') }}</span></th>
                    <td></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                <tr>
                    <th class="group" colspan="3">{{ "Support"|t('app') }}</th>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "Security & Bug Fixes"|t('app') }}</th>
                    <td><span data-icon="check"></span></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "Community Support (Slack, Stack Exchange)"|t('app') }}</th>
                    <td><span data-icon="check"></span></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "Developer Support"|t('app') }}</th>
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

            this.$root.pageTitle = this.$options.filters.t('Upgrade Craft CMS', 'app');
        },

        mounted() {
            this.$root.$on('allDataLoaded', function() {
                Craft.initUiElements(this.$refs.upgradecraft);
            }.bind(this));

            Craft.initUiElements(this.$refs.upgradecraft);
        },
    }
</script>
