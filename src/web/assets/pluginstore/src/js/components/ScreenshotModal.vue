<template>
  <div
    id="screenshot-modal"
    ref="screenshotModal"
    @keydown.esc="close">
    <a
      class="close"
      @click="close">&times;</a>

    <div
      v-if="screenshotModalImages"
      class="carousel"
      ref="carousel">
      <swiper
        :options="swiperOption"
        ref="screenshotModalSwiper">
        <swiper-slide
          v-for="(imageUrl, key) in screenshotModalImages"
          :key="key">
          <div class="screenshot">
            <div class="swiper-zoom-container">
              <img :src="imageUrl" />
            </div>
          </div>
        </swiper-slide>
      </swiper>

      <template v-if="screenshotModalImages.length > 1">
        <div class="swiper-button-prev">
          <c-icon
            icon="chevron-left"
            size="6" />
        </div>
        <div class="swiper-button-next">
          <c-icon
            icon="chevron-right"
            size="6" />
        </div>

        <div class="pagination-wrapper">
          <div class="pagination-content">
            <div
              :class="'swiper-pagination'"
              slot="pagination"></div>
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
      screenshotModalImageKey: state => state.app.screenshotModalImageKey,
      screenshotModalImages: state => state.app.screenshotModalImages,
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

    handleEscapeKey(e) {
      if (e.keyCode === 27) {
        this.close()
      }
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
  },

  mounted() {
    this.swiper.slideTo(this.screenshotModalImageKey, 0)
    window.addEventListener('resize', this.handleResize)
    this.handleResize()
  },

  created() {
    window.addEventListener('keydown', this.handleEscapeKey)
  },

  beforeDestroy: function() {
    this.swiper.destroy(true, false)
    window.removeEventListener('resize', this.handleResize)
    window.removeEventListener('keydown', this.handleEscapeKey)
  }
}
</script>

<style lang="scss">
#screenshot-modal {
  @apply tw-fixed tw-inset-0 tw-bg-gray-100 tw-overflow-hidden;
  z-index: 101;

  .close {
    @apply tw-inline-block tw-text-center tw-absolute tw-top-0 tw-left-0 tw-z-30;
    font-size: 30px;
    color: rgba(0, 0, 0, 0.6);
    padding: 14px 24px;
    line-height: 16px;

    &:hover {
      @apply tw-no-underline;
      color: rgba(0, 0, 0, 0.8);
    }
  }

  .carousel {
    @apply tw-absolute tw-flex tw-inset-0;
    @apply tw-absolute tw-flex tw-inset-0;

    .swiper-container {
      @apply tw-flex;

      .swiper-wrapper {
        @apply tw-flex-1 tw-flex tw-w-auto tw-h-auto;

        .swiper-slide {
          @apply tw-flex-1 tw-flex tw-text-center tw-justify-center tw-items-center;

          .screenshot {
            @apply tw-flex tw-flex-1 tw-justify-center tw-items-center tw-h-full;
            box-sizing: border-box;

            .swiper-zoom-container {
              @apply tw-w-full tw-h-full tw-flex tw-text-center tw-justify-center tw-items-center;

              img {
                @apply tw-max-w-full tw-max-h-full;
              }
            }
          }
        }
      }
    }

    .swiper-button-prev,
    .swiper-button-next {
      @apply tw-flex tw-justify-center tw-items-center tw-w-auto;
      background-color: rgba(248, 250, 252, .7);
      background-image: none;

      .c-icon {
        @apply tw-flex-1 tw-top-0;
      }
    }

    .swiper-button-prev {
      @apply tw-rounded tw-px-2 tw-py-8 tw-left-0 tw-ml-4;

      .c-icon {
        left: -2px;
      }
    }

    .swiper-button-next {
      @apply tw-rounded tw-px-2 tw-py-8 tw-right-0 tw-mr-4;
    }

    .pagination-wrapper {
      @apply tw-w-full tw-absolute tw-bottom-0 tw-py-0 tw-flex tw-z-10;
      bottom: 40px;

      .pagination-content {
        @apply tw-flex tw-flex-1 tw-px-8 tw-max-w-xs tw-mx-auto;

        .swiper-pagination {
          @apply tw-relative tw-flex tw-flex-1 tw-bg-gray-200 tw-p-0 tw-rounded-full;

          .swiper-pagination-bullet {
            @apply tw-flex-1 tw-rounded-full tw-bg-gray-200;
            height: 8px;

            &.swiper-pagination-bullet-active {
              @apply tw-bg-gray-900;
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
