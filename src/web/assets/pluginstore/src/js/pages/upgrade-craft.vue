<template>
    <div ref="upgradecraft" v-if="cart" id="upgrade-craft" class="ps-container">
        <div id="upgrade-craft-compare" class="body">
            <h1>{{ "Upgrade Craft CMS"|t('app') }}</h1>
            <table class="data fullwidth mt-6">
                <thead>
                <tr class="logos">
                    <th>
                        <img :src="craftLogo" width="70" height="70" />
                    </th>
                    <th scope="col">
                        <h1 class="logo">Solo</h1>
                        <p>{{ "For when you’re building a website for yourself or a friend."|t('app') }}</p>
                    </th>
                    <th scope="col">
                        <h1 class="logo">Pro</h1>
                        <p>{{ "For when you’re building something professionally for a client or team."|t('app') }}</p>
                    </th>
                </tr>
                <tr class="license-statuses">
                    <td></td>
                    <td><status-badge :edition="CraftSolo"></status-badge></td>
                    <td><status-badge :edition="CraftPro"></status-badge></td>
                </tr>
                <tr class="price">
                    <th scope="row" class="feature"></th>
                    <td>{{ "Free"|t('app') }}</td>
                    <td v-if="editions">
                        {{ "{price} plus {renewalPrice}/year for updates"|t('app', {
                            price: $options.filters.currency(editions.pro.price),
                            renewalPrice: $options.filters.currency(editions.pro.renewalPrice)
                        }) }}
                    </td>
                </tr>
                <tr class="buybtns">
                    <td></td>
                    <td><buy-btn :edition="CraftSolo" edition-handle="solo"></buy-btn></td>
                    <td><buy-btn :edition="CraftPro" edition-handle="pro"></buy-btn></td>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <th class="group" colspan="3">{{ "Features"|t('app') }}</th>
                </tr>
                <tr>
                    <th class="feature" scope="row">
                        {{ "Content Modeling"|t('app') }}
                        <info-hud>{{ "Includes Sections, Global sets, Category groups, Tag groups, Asset volumes, Custom fields, Entry versioning, and Entry drafts"|t('app') }}</info-hud>
                    </th>
                    <td><span data-icon="check"></span></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "Multi-site Multi-lingual"|t('app') }} <info-hud>{{ "Includes Multiple locales, Section and entry locale targeting, Content translations"|t('app') }}</info-hud></th>
                    <td><span data-icon="check"></span></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "Cloud Storage Integration"|t('app') }}</th>
                    <td><span data-icon="check"></span></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "User Accounts"|t('app') }} <info-hud>{{ "Includes User accounts, User groups, User permissions, Public user registration"|t('app') }}</info-hud></th>
                    <td></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "System Branding"|t('app') }} <info-hud>{{ "Includes Custom login screen logo, Custom site icon, Custom HTML email template, Custom email message wording"|t('app') }}</info-hud></th>
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
    /* global Craft */

    import {mapState} from 'vuex'
    import StatusBadge from '../components/upgradecraft/StatusBadge'
    import BuyBtn from '../components/upgradecraft/BuyBtn'
    import InfoHud from '../components/InfoHud'

    export default {

        components: {
            StatusBadge,
            BuyBtn,
            InfoHud,
        },

        computed: {

            ...mapState({
                cart: state => state.cart.cart,
                craftLogo: state => state.craft.craftLogo,
                CraftPro: state => state.craft.CraftPro,
                CraftSolo: state => state.craft.CraftSolo,
                editions: state => state.craft.editions,
            }),

        },

        mounted() {
            this.$root.$on('allDataLoaded', function() {
                Craft.initUiElements(this.$refs.upgradecraft)
            }.bind(this))

            Craft.initUiElements(this.$refs.upgradecraft)
        },
    }
</script>

<style lang="scss" scoped>
    @import "../../../../../../../lib/craftcms-sass/mixins";

    #upgrade-craft {
        .logo {
            @apply .inline-block;
            margin: 0 auto 24px !important;
            font-weight: 500;
            font-size: 18px;
            color: $submitColor;
            letter-spacing: 3.3px;
            line-height: 14px;
            border: 1px solid $submitColor;
            border-radius: 3px;
            padding: 10px 6.7px 9px 10px;
            text-transform: uppercase;
        }
    }

    #upgrade-craft-compare table {
        table-layout: fixed;

        th,
        td {
            @apply .w-1/3;
        }

        th.feature {
            font-weight: normal;
            color: $mediumTextColor;
        }

        thead {
            th {
                @apply .font-normal .pt-0;
                font-size: 13px;
                line-height: 18px;
            }

            tr {
                &.logos th {
                    padding-bottom: 14px;

                    .logo {
                        @apply .mb-0;
                    }

                    p {
                        @apply .whitespace-normal;
                        max-width: 250px;
                    }
                }

                &.license-statuses td {
                    @apply .pt-0;
                    padding-bottom: 14px;
                }
            }

            tr.price {
                th,
                td {
                    @apply .relative;
                    padding-top: 14px;
                }

                td {
                    &:before {
                        @apply .absolute .block .pin-t;
                        width: 24px;
                        height: 1px;
                        content: '.';
                        font-size: 0;
                        background: $hairlineColor;
                    }

                    .listedprice {
                        @include margin-right(5px);
                        text-decoration: line-through;
                        color: $lightTextColor;
                    }
                }

                th:before {
                    @include left(0);
                }

                td:before {
                    @include left(14px);
                }
            }
        }

        tbody {
            th,
            td {
                @apply .w-1/3;
            }

            tr:first-child {
                th,
                td {
                    @apply .border-t-0;
                }
            }

            th.group {
                padding-top: 30px;
                border-bottom-style: solid;
                color: $mediumTextColor;

                &:before {
                    margin-top: -2px;
                    width: 24px;
                    font-size: 17px;
                }
            }

            tr.buybtns td {
                @apply .border-b-0;
                padding-top: 14px;
            }
        }
    }
</style>
