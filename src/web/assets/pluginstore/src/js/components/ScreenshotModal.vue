<template>
    <div id="screenshot-modal" ref="screenshotModal" @keydown.esc="close">
        <a class="close" @click="close">&times;</a>

        <div v-if="screenshotModalImages" class="carousel" ref="carousel">
            <swiper :options="swiperOption" ref="screenshotModalSwiper">
                <swiper-slide v-for="(imageUrl, key) in screenshotModalImages" :key="key">
                    <div class="screenshot">
                        <img :src="imageUrl" />
                    </div>
                </swiper-slide>

                <div class="swiper-pagination" slot="pagination"></div>
            </swiper>
        </div>
    </div>
</template>

<script>
    import {mapState} from 'vuex'

    export default {

        data() {
            return {
                ratio: '4:3'
            }
        },

        computed: {

            ...mapState({
                screenshotModalImages: state => state.app.screenshotModalImages,
                screenshotModalImageKey: state => state.app.screenshotModalImageKey,
            }),

            swiper() {
                return this.$refs.screenshotModalSwiper.swiper
            },

            swiperOption() {
                return {
                    initialSlide: 0,
                    loop: false,
                    pagination: {
                        el: '.swiper-pagination',
                        clickable: true
                    },
                    keyboard: true,
                }
            }

        },

        methods: {

            close() {
                this.$store.commit('app/updateShowingScreenshotModal', false)
            },

            handleResize() {

                if (this.screenshotModalImages.length === 0) {
                    return
                }

                const ratio = this.ratio.split(':')
                const ratioWidth = ratio[0]
                const ratioHeight = ratio[1]
                const $carousel = this.$refs.carousel
                const carouselWidth = $carousel.offsetWidth
                const carouselHeight = $carousel.offsetHeight
                let imageElements = $carousel.getElementsByTagName("img")
                let maxHeight

                if (this.inline) {
                    maxHeight = carouselWidth * ratioHeight / ratioWidth
                } else {
                    if (carouselWidth > carouselHeight) {
                        maxHeight = carouselWidth * ratioHeight / ratioWidth
                    } else {
                        maxHeight = carouselHeight * ratioWidth / ratioHeight
                    }

                    if (carouselHeight > 0 && maxHeight > carouselHeight) {
                        maxHeight = carouselHeight
                    }
                }

                for (let i = 0; i < imageElements.length; i++) {
                    let imageElement = imageElements[i]
                    imageElement.style.maxHeight = maxHeight + 'px'
                }
            },

            handleEscapeKey(e) {
                if (e.keyCode === 27) {
                    this.close()
                }
            }

        },

        mounted: function () {
            this.swiper.slideTo(this.screenshotModalImageKey, 0)
            window.addEventListener('resize', this.handleResize)
            this.handleResize()
        },

        created() {
            window.addEventListener('keydown', this.handleEscapeKey)
        },

        beforeDestroy: function () {
            this.swiper.destroy(true, false)
            window.removeEventListener('resize', this.handleResize)
            window.removeEventListener('keydown', this.handleEscapeKey)
        }

    }
</script>

<style lang="scss">
    #screenshot-modal {
        @apply .fixed .pin .bg-grey-lightest .overflow-hidden;
        z-index: 101;

        .close {
            @apply .inline-block .text-center .absolute .pin-t .pin-l .z-30;
            font-size: 30px;
            color: rgba(0, 0, 0, 0.6);
            padding: 14px 24px;
            line-height: 16px;

            &:hover {
                @apply .no-underline;
                color: rgba(0, 0, 0, 0.8);
            }
        }

        .carousel {
            @apply .absolute;
            top: 100px;
            right: 100px;
            bottom: 100px;
            left: 100px;

            .swiper-container {
                @apply .flex .overflow-hidden;

                .swiper-wrapper {
                    @apply .flex .flex-1 .w-auto .h-auto;

                    .swiper-slide {
                        @apply .flex-1 .flex .text-center .justify-center .items-center .overflow-hidden;

                        .screenshot {
                            @apply .flex .justify-center .items-center;
                            box-sizing: border-box;

                            img {
                                @apply .max-w-full .max-h-full;
                                padding-bottom: 50px;
                            }
                        }
                    }
                }
            }

            .swiper-pagination {
                @apply .w-full;
                bottom: 0;

                .swiper-pagination-bullet {
                    @apply .mx-2;

                    &.swiper-pagination-bullet-active {
                        @apply .bg-grey-darker;
                    }
                }
            }
        }
    }
</style>