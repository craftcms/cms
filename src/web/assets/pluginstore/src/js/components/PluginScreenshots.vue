<template>
    <div class="screenshots">
        <a class="screenshot" v-for="(image, key) in images" @click="zoomImage(key)" :key="key">
            <img :src="image" />
        </a>
    </div>
</template>


<script>
    export default {
        props: ['images'],

        methods: {
            zoomImage(key) {
                this.$store.commit('app/updateScreenshotModalImages', this.images)
                this.$store.commit('app/updateShowingScreenshotModal', true)
                this.$store.commit('app/updateScreenshotModalImageKey', key)
            }
        }
    }
</script>

<style lang="scss">
    .screenshots {
        @apply tw-overflow-auto tw-flex tw--mx-4;
        -webkit-overflow-scrolling: touch;

        .screenshot {
            @apply tw-px-4 tw-shrink-0 tw-grow-0;
            flex-basis: 90%;

            img {
                @apply tw-rounded;
            }
        }
    }

    @media (min-width: 672px) {
        .screenshots {
            .screenshot {
                flex-basis: 45%;
            }
        }
    }

    @media only screen and (min-width: 1400px) {
        .screenshots {
            .screenshot {
                flex-basis: 31%;
            }
        }
    }
</style>
