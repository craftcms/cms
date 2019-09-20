<template>
    <div id="screenshot-modal" ref="screenshotModal" @keydown.esc="close">
        <a class="close" @click="close">&times;</a>

        <div v-if="screenshotModalImages" class="carousel" ref="carousel">
            <swiper :options="swiperOption" ref="screenshotModalSwiper">
                <swiper-slide v-for="(imageUrl, key) in screenshotModalImages" :key="key">
                    <div class="screenshot">
                        <div class="swiper-zoom-container">
                            <img :src="imageUrl" />
                        </div>
                    </div>
                </swiper-slide>
            </swiper>

            <template v-if="screenshotModalImages.length > 1">
                <div class="swiper-button-prev"><icon icon="chevron-left" size="xl" /></div>
                <div class="swiper-button-next"><icon icon="chevron-right" size="xl" /></div>

                <div class="pagination-wrapper">
                    <div class="pagination-content">
                        <div :class="'swiper-pagination'" slot="pagination"></div>
                    </div>
                </div>
            </template>
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
                    zoom: true,
                    navigation: {
                        nextEl: '.swiper-button-next',
                        prevEl: '.swiper-button-prev'
                    }
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
            @apply .absolute .flex .pin;

            .swiper-container {
                @apply .flex;

                .swiper-wrapper {
                    @apply .flex-1 .flex .w-auto .h-auto;

                    .swiper-slide {
                        @apply .flex-1 .flex .text-center .justify-center .items-center;

                        .screenshot {
                            @apply .flex .flex-1 .justify-center .items-center .h-full;
                            box-sizing: border-box;

                            .swiper-zoom-container {
                                @apply .w-full .h-full .flex .text-center .justify-center .items-center;

                                img {
                                    @apply .max-w-full .max-h-full;
                                }
                            }
                        }
                    }
                }
            }

            .swiper-button-prev,
            .swiper-button-next {
                @apply .flex .justify-center .items-center .w-auto;
                background-color: rgba(248, 250, 252, .7);
                background-image: none;

                .c-icon {
                    @apply .flex-1 .pin-t;
                }
            }

            .swiper-button-prev {
                @apply .rounded .px-2 .py-8 .pin-l .ml-4;

                .c-icon {
                    left: -2px;
                }
            }

            .swiper-button-next {
                @apply .rounded .px-2 .py-8 .pin-r .mr-4;
            }

            .pagination-wrapper {
                @apply .w-full .absolute .pin-b .py-0 .flex .z-10;
                bottom: 40px;

                .pagination-content {
                    @apply .flex .flex-1 .px-8 .max-w-xs .mx-auto;

                    .swiper-pagination {
                        @apply .relative .flex .flex-1 .bg-grey-lighter .p-0 .rounded-full;

                        .swiper-pagination-bullet {
                            @apply .flex-1 .rounded-full .bg-grey-lighter;
                            height: 8px;

                            &.swiper-pagination-bullet-active {
                                @apply .bg-grey-darkest;
                            }
                        }
                    }
                }
            }
        }
    }

    @media (min-width: 700px) {
        .carousel {
            .swiper-container {
                .swiper-wrapper {
                    .swiper-slide {
                        .screenshot {
                            .swiper-zoom-container {
                                img {
                                    padding-left: 100px;
                                    padding-right: 100px;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    @media (min-height: 700px) {
        .carousel {
            .swiper-container {
                .swiper-wrapper {
                    .swiper-slide {
                        .screenshot {
                            .swiper-zoom-container {
                                img {
                                    padding-top: 100px;
                                    padding-bottom: 100px;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
</style>
