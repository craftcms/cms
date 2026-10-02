<script setup lang="ts">
  defineProps<{
    status: 'valid' | 'invalid' | 'unknown';
  }>();
</script>

<template>
  <div>
    <template v-if="status === 'valid'">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        xml:space="preserve"
        id="Layer_1"
        x="0"
        y="0"
        version="1.1"
        viewBox="0 0 100 100"
      >
        <circle cx="50" cy="50" r="50" class="st0" style="fill: #fff" />
        <linearGradient
          id="SVGID_1_"
          x1="-256.765"
          x2="-257.388"
          y1="443.387"
          y2="442.734"
          gradientTransform="matrix(-98.796 0 0 -100 -25349.086 44355)"
          gradientUnits="userSpaceOnUse"
        >
          <stop offset="0" stop-color="#b4ed50" />
          <stop offset=".518" stop-color="#79be38" />
          <stop offset="1" stop-color="#429321" />
        </linearGradient>
        <circle cx="50" cy="50" r="46.3" fill="url(#SVGID_1_)" />
        <path
          d="M66.8 33.9 40 60.5h5.8L32.4 47.2c-1.6-1.6-4.2-1.6-5.8 0s-1.6 4.2 0 5.8L40 66.2c1.6 1.6 4.2 1.6 5.8 0l26.9-26.5c1.6-1.6 1.6-4.2 0-5.8s-4.3-1.6-5.9 0"
          style="fill: #fff"
        />
      </svg>
    </template>
    <template v-if="status === 'invalid'">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        xml:space="preserve"
        viewBox="0 0 100 100"
      >
        <path
          d="M90.7 99.2H9.3c-3.3 0-6.3-1.7-8-4.6s-1.7-6.4 0-9.3L42 15.4c1.7-2.9 4.7-4.6 8-4.6s6.3 1.7 8 4.6l40.7 69.9c1.7 2.9 1.7 6.4 0 9.3-1.6 2.8-4.6 4.6-8 4.6"
          style="fill: #fff"
        />
        <linearGradient
          id="a"
          x1=".069"
          x2=".499"
          y1="105.887"
          y2="105.028"
          gradientTransform="matrix(93.8057 0 0 -82 3.14 8702)"
          gradientUnits="userSpaceOnUse"
        >
          <stop offset="0" style="stop-color: #fbda61" />
          <stop offset="1" style="stop-color: #f76b1c" />
        </linearGradient>
        <path
          d="M55.3 17c-2.4-4-8.2-4-10.6 0L4 86.8c-2.4 4.1.5 9.2 5.3 9.2h81.5c4.7 0 7.7-5.1 5.3-9.2z"
          style="fill: url(#a)"
        />
      </svg>
    </template>
  </div>
</template>

<style scoped lang="scss">
  svg {
    display: inline-block;
    max-width: 100%;
    max-height: 100%;
  }
</style>
