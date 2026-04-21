<template>
  <div class="search-tabs tw-mt-2">
    <ul class="tw--mb-px tw-flex tw-space-x-6">
      <li v-for="(tab, tabKey) in tabs" :key="tabKey">
        <button
          :class="{
            'tw-text-base tw-inline-block tw-px-1 tw-py-3 tw-border-0 tw-border-b-2 tw-border-solid tw-text-gray-500 tw-text-opacity-75 hover:tw-text-opacity-100': true,
            'tw-border-transparent hover:tw-border-separator hover:tw-border-gray-200':
              tab.handle !== activeTab,
            'tw-text-blue-600 tw-border-blue-600 tw-text-opacity-100':
              tab.handle === activeTab,
          }"
          @click="$emit('tab-click', tab.handle)"
        >
          {{ tab.name }}
        </button>
      </li>
    </ul>
  </div>
</template>

<script>
  export default {
    props: {
      activeTab: {
        type: String,
        required: true,
      },
    },
    data() {
      return {
        tabs: [
          {
            name: 'Plugins',
            handle: 'plugins',
          },
          // TODO: Enable developer search
          // {
          //   name: 'Developers',
          //   handle: 'developers',
          // },
        ],
      };
    },
  };
</script>
