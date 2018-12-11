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
        @apply .overflow-auto .flex .-mx-4;

        .screenshot {
            @apply .px-4 .flex-no-shrink .flex-no-grow;
            flex-basis: 50%;

            img {
                @apply .rounded;
            }
        }
    }

    @media only screen and (min-width: 1400px) {
        .screenshots {
            .screenshot {
                flex-basis: 33.3333%;
            }
        }
    }
</style>