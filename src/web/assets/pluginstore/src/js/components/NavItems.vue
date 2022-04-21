<template>
  <div class="nav-items">
    <ul>
      <li>
        <router-link to="/" @click.native="$emit('itemClick')">
          <c-icon icon="star" />
          {{ 'Discover' | t('app') }}
        </router-link>
      </li>
      <li v-if="CraftEdition < CraftPro || licensedEdition < CraftPro">
        <router-link to="/upgrade-craft" @click.native="$emit('itemClick')">
          <img
            src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiB2aWV3Qm94PSIwIDAgMTAwIDEwMCI+CiAgPGcgZmlsbD0ibm9uZSI+CiAgICA8cmVjdCB3aWR0aD0iNDAuOTA5IiBoZWlnaHQ9IjQwLjkwOSIgeD0iMjkuNTQ1IiB5PSIyOS41NDUiIGZpbGw9IiNGRkYiLz4KICAgIDxwYXRoIGZpbGw9IiNFNTQyMkIiIGQ9Ik04OS40NzM2ODQyLDAgTDEwLjUyNjMxNTgsMCBDNC42NzgzNjI1NywwIDAsNC42NzgzNjI1NyAwLDEwLjUyNjMxNTggTDAsODkuNDczNjg0MiBDMCw5NS4zMjE2Mzc0IDQuNjc4MzYyNTcsMTAwIDEwLjUyNjMxNTgsMTAwIEw4OS40NzM2ODQyLDEwMCBDOTUuMjA0Njc4NCwxMDAgMTAwLDk1LjMyMTYzNzQgMTAwLDg5LjQ3MzY4NDIgTDEwMCwxMC41MjYzMTU4IEMxMDAsNC42NzgzNjI1NyA5NS4zMjE2Mzc0LDAgODkuNDczNjg0MiwwIE02MCw1Ni42MDgxODcxIEw2NC42NzgzNjI2LDYxLjk4ODMwNDEgQzU5Ljc2NjA4MTksNjUuOTY0OTEyMyA1NC4xNTIwNDY4LDY4LjE4NzEzNDUgNDguNTM4MDExNyw2OC4xODcxMzQ1IEMzNy40MjY5MDA2LDY4LjE4NzEzNDUgMzAuNDA5MzU2Nyw2MC44MTg3MTM1IDMyLjA0Njc4MzYsNTAuNDA5MzU2NyBDMzMuNjg0MjEwNSw0MCA0My4xNTc4OTQ3LDMyLjYzMTU3ODkgNTQuMjY5MDA1OCwzMi42MzE1Nzg5IEM1OS42NDkxMjI4LDMyLjYzMTU3ODkgNjQuNjc4MzYyNiwzNC43MzY4NDIxIDY4LjE4NzEzNDUsMzguNTk2NDkxMiBMNjEuNjM3NDI2OSw0My45NzY2MDgyIEM1OS43NjYwODE5LDQxLjUyMDQ2NzggNTYuNjA4MTg3MSwzOS44ODMwNDA5IDUzLjA5OTQxNTIsMzkuODgzMDQwOSBDNDYuNDMyNzQ4NSwzOS44ODMwNDA5IDQxLjI4NjU0OTcsNDQuMjEwNTI2MyA0MC4yMzM5MTgxLDUwLjQwOTM1NjcgQzM5LjI5ODI0NTYsNTYuNjA4MTg3MSA0My4wNDA5MzU3LDYwLjkzNTY3MjUgNDkuODI0NTYxNCw2MC45MzU2NzI1IEM1My4wOTk0MTUyLDYwLjkzNTY3MjUgNTYuNjA4MTg3MSw1OS42NDkxMjI4IDYwLDU2LjYwODE4NzEgWiIvPgogIDwvZz4KPC9zdmc+Cg=="
          />
          {{ 'Upgrade Craft CMS' | t('app') }}
        </router-link>
      </li>
      <li v-for="category in categories" :key="category.id">
        <router-link
          :to="'/categories/' + category.id"
          @click.native="$emit('itemClick')"
        >
          <img :src="category.iconUrl" />
          {{ category.title }}
        </router-link>
      </li>
    </ul>

    <div class="tw-border-t tw-border-gray-200 tw-border-solid tw-mt-8 tw-py-8">
      <ul>
        <li>
          <a
            href="https://craftcms.com/"
            title="Craft CMS"
            @click="$emit('itemClick')"
          >
            <c-icon icon="world" class="tw-w-6 tw-h-6" />
            craftcms.com
          </a>
        </li>
        <li>
          <a
            href="https://id.craftcms.com/"
            title="Craft ID"
            @click="$emit('itemClick')"
          >
            <c-icon icon="world" class="tw-w-6 tw-h-6" />
            Craft ID
          </a>
        </li>
      </ul>
    </div>
  </div>
</template>

<script>
  import {mapState} from 'vuex';

  export default {
    computed: {
      ...mapState({
        categories: (state) => state.pluginStore.categories,
        CraftEdition: (state) => state.craft.CraftEdition,
        CraftPro: (state) => state.craft.CraftPro,
        licensedEdition: (state) => state.craft.licensedEdition,
      }),
    },
  };
</script>
