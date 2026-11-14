<template>
  <div>
    <meta-stat>
      <template #title>
        {{ 'Installation Instructions' | t('app') }}
      </template>
      <template #content>
        <!-- Installation instructions -->
        <div class="copy-package">
          <div class="tw-flex">
            <c-textbox
              ref="input"
              class="tw-w-full tw-flex tw-rounded-r-none tw-font-mono focus:tw-relative focus:tw-z-10 tw-text-sm"
              readonly="readonly"
              type="text"
              :value="command"
              @focus="select"
            />
            <c-btn
              class="tw--ml-px tw-w-14 tw-rounded-l-none"
              :class="{
                'tw-border-green-500 hover:tw-border-green-500 active:tw-border-green-500':
                  showSuccess,
              }"
              :disable-shadow="true"
              @click="copy"
            >
              <template v-if="showSuccess">
                <c-icon class="tw-text-green-500" icon="check" />
              </template>
              <template v-else>
                <c-icon class="tw-text-black" icon="clipboard-copy" />
              </template>
            </c-btn>
          </div>

          <div class="tw-mt-4 tw-text-sm tw-text-gray-500">
            <p>
              {{
                'To install this plugin with composer, copy the command above to your terminal.'
                  | t('app')
              }}
            </p>
          </div>
        </div>
      </template>
    </meta-stat>
  </div>
</template>

<script>
  import MetaStat from './MetaStat';
  export default {
    components: {
      MetaStat,
    },

    props: {
      plugin: {
        type: Object,
        required: true,
      },
    },

    data() {
      return {
        showSuccess: false,
      };
    },

    computed: {
      command() {
        const composer = window.isDdev ? 'ddev composer' : 'composer';
        const craft = window.isDdev ? 'ddev craft' : 'php craft';
        return `${composer} require "${this.plugin.packageName}:^${this.plugin.version}" -w && ${craft} plugin/install ${this.plugin.handle}`;
      },
    },

    methods: {
      select() {
        this.$refs.input.$el.select();
      },

      copy() {
        if (this.showSuccess) {
          return;
        }

        this.select();

        window.document.execCommand('copy');

        this.showSuccess = true;

        setTimeout(() => {
          this.showSuccess = false;
        }, 3000);
      },
    },
  };
</script>
