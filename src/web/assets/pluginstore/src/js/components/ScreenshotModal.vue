<template>
    <div id="screenshot-modal" ref="screenshotModal" @keydown.esc="close">
        <a class="close" @click="close">&times;</a>

        <div v-if="screenshotModalImages" class="carousel" ref="carousel">
            <swiper :options="swiperOption" :instanceName="identifier" ref="mySwiper">
                <swiper-slide v-for="(imageUrl, key) in screenshotModalImages" :key="key">
                    <div class="screenshot">
                        <img :src="imageUrl" />
                    </div>
                </swiper-slide>

                <div :class="'swiper-pagination swiper-pagination-' + identifier" slot="pagination"></div>
            </swiper>
        </div>
    </div>
</template>

<script>
    import {mapState} from 'vuex'

    export default {

        data() {
            return {
                identifier: 'screenshot-modal-carousel',
                ratio: '4:3'
            }
        },

        computed: {

            ...mapState({
                screenshotModalImages: state => state.app.screenshotModalImages,
            }),

            swiperOption() {
                return {
                    initialSlide: 0,
                    loop: true,
                    pagination: {
                        el: '.swiper-pagination-' + this.identifier,
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
            window.addEventListener('resize', this.handleResize)
            this.handleResize()
        },

        created() {
            window.addEventListener('keydown', this.handleEscapeKey)
        },

        beforeDestroy: function () {
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
                @apply .flex;

                .swiper-wrapper {
                    @apply .flex .flex-1 .w-auto .h-auto;

                    .swiper-slide {
                        @apply .flex-1 .flex .text-center .justify-center .items-center .overflow-hidden;

                        .screenshot {
                            @apply .flex .justify-center .items-center;
                            box-sizing: border-box;

                            img {
                                @apply .max-w-full .max-h-full;
                            }
                        }
                    }
                }
            }

            .swiper-pagination {
                bottom: -60px;

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