<template>
    <div class="changelog-release">
        <div class="version">
            <a :href="'#' + version" class="anchor"><font-awesome-icon icon="link" /></a>
            <h2 :id="version">Version {{ version }}</h2>
            <div class="date">{{date}}</div>
        </div>

        <div class="details">
            <slot></slot>
        </div>
    </div>
</template>

<script>
    export default {

        props: ['version', 'date'],

    }
</script>

<style lang="scss">
    .changelog-release {
        @apply .pt-2 .pb-4 .border-b .border-grey-light .border-solid;

        .version {
            @apply .relative;

            .anchor {
                @apply .absolute .text-white .p-1 .rounded-full;
                left: -24px;
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
                    top: 24px;
                }
            }

            .details {
                @apply .flex-1;
            }
        }
    }

</style>