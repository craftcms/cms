<template>
    <div v-if="release" class="changelog-release">
        <div class="version">
            <a :href="'#' + release.version" class="anchor"><icon icon="link" /></a>
            <h2 :id="release.version">{{ "Version {version}"|t('app', {version: release.version}) }}</h2>
            <div class="date">{{date}}</div>
            <div v-if="release.critical" class="critical">{{ 'Critical'|t('app') }}</div>
        </div>

        <div class="details readable" v-html="release.notes"></div>
    </div>
</template>

<script>
    /* global Craft */

    export default {
        props: ['release'],

        computed: {
            date() {
                return Craft.formatDate(this.release.date)
            }
        }
    }
</script>

<style lang="scss">
    @import "../../../../../../../node_modules/craftcms-sass/mixins";

    .changelog-release {
        @apply .pt-2 .pb-4 .border-b .border-grey-light .border-solid;

        .version {
            @apply .relative;

            .anchor {
                @apply .absolute .text-white .p-1 .rounded-full;
                @include left(-24px);
                top: 0px;
                font-size: 14px;
                transform: rotate(45deg);

                &:hover {
                    @apply .text-black;
                }
            }

            &:hover {
                .anchor {
                    @apply .text-black;
                }
            }

            h2 {
                @apply .mt-6 .mb-2;
            }

            .date {
                @apply .text-grey;
            }

            .critical {
                @apply .uppercase .text-red .border .border-red .border-solid .inline-block .px-1 .py-0 .rounded .text-sm .mt-2;
            }
        }

        .details {
            @apply .pt-6;

            h3 {
                @apply .mt-6 .mb-4;
            }

            ul {
                @apply .mb-4 .ml-6 .leading-normal;
                list-style-type: disc;

                li:not(:first-child) {
                    @apply .mt-1;
                }
            }
        }
    }

    @media (min-width: 992px) {
        .changelog-release {
            @apply .flex;

            .version {
                @apply .w-full .max-w-xs;

                .anchor {
                    top: 20px;
                }
            }

            .details {
                @apply .flex-1;
            }
        }
    }

</style>
