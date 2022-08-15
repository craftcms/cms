<template>
  <div v-if="plugin" class="plugin-actions tw-relative tw-space-y-2">
    <template v-if="!isPluginEditionFree">
      <template v-if="isInCart(plugin, edition)">
        <!-- Already in cart -->
        <c-btn
          v-if="allowUpdates"
          kind="primary"
          icon="check"
          block
          large
          disabled
          @click="$root.openModal('cart')"
          >{{ 'Already in your cart' | t('app') }}
        </c-btn>
      </template>

      <template v-else>
        <!-- Add to cart / Upgrade (from lower edition) -->
        <c-btn
          v-if="allowUpdates && isEditionMoreExpensiveThanLicensed"
          kind="primary"
          @click="addEditionToCart(edition.handle)"
          :loading="addToCartloading"
          :disabled="
            addToCartloading ||
            !plugin.latestCompatibleVersion ||
            !plugin.phpVersionCompatible ||
            licenseMismatched ||
            plugin.abandoned
          "
          block
          large
        >
          <plugin-edition-price :edition="edition" />
        </c-btn>

        <!-- Licensed -->
        <c-btn
          v-else-if="licensedEdition === edition.handle"
          kind="primary"
          block
          large
          disabled
          >{{ 'Licensed' | t('app') }}
        </c-btn>
      </template>
    </template>

    <!-- Install/Try -->
    <template v-if="!isPluginInstalled || currentEdition !== edition.handle">
      <form
        v-if="allowUpdates || isPluginInstalled"
        method="post"
        @submit="onSwitchOrInstallSubmit"
      >
        <input type="hidden" :name="csrfTokenName" :value="csrfTokenValue" />

        <template v-if="isPluginInstalled">
          <!-- Switch -->
          <input type="hidden" name="action" value="plugins/switch-edition" />
          <input type="hidden" name="pluginHandle" :value="plugin.handle" />
          <input type="hidden" name="edition" :value="edition.handle" />
        </template>
        <template v-else>
          <!-- Install -->
          <input type="hidden" name="action" value="pluginstore/install" />
          <input type="hidden" name="packageName" :value="plugin.packageName" />
          <input type="hidden" name="handle" :value="plugin.handle" />
          <input type="hidden" name="edition" :value="edition.handle" />
          <input
            type="hidden"
            name="version"
            :value="plugin.latestCompatibleVersion"
          />
        </template>

        <!-- Install (Free) -->
        <template v-if="isPluginEditionFree">
          <c-btn
            kind="primary"
            type="submit"
            :loading="loading"
            :disabled="
              !plugin.latestCompatibleVersion || !plugin.phpVersionCompatible
            "
            block
            large
            >{{ 'Install' | t('app') }}
          </c-btn>
        </template>

        <template v-else>
          <template
            v-if="
              (isEditionMoreExpensiveThanLicensed &&
                currentEdition === edition.handle) ||
              (licensedEdition === edition.handle && !currentEdition)
            "
          >
            <!-- Install (Commercial) -->
            <c-btn
              type="submit"
              :loading="loading"
              :disabled="
                !plugin.latestCompatibleVersion || !plugin.phpVersionCompatible
              "
              block
              large
              >{{ 'Install' | t('app') }}
            </c-btn>
          </template>

          <template
            v-else-if="
              isEditionMoreExpensiveThanLicensed &&
              currentEdition !== edition.handle
            "
          >
            <!-- Try -->
            <c-btn
              type="submit"
              :disabled="
                !(
                  (pluginLicenseInfo &&
                    pluginLicenseInfo.isInstalled &&
                    pluginLicenseInfo.isEnabled) ||
                  !pluginLicenseInfo
                ) ||
                !plugin.latestCompatibleVersion ||
                !plugin.phpVersionCompatible
              "
              :loading="loading"
              block
              large
              >{{ 'Try' | t('app') }}
            </c-btn>
          </template>

          <template
            v-else-if="
              currentEdition &&
              licensedEdition === edition.handle &&
              currentEdition !== edition.handle
            "
          >
            <!-- Reactivate -->
            <c-btn type="submit" :loading="loading" block large
              >{{ 'Reactivate' | t('app') }}
            </c-btn>
          </template>
        </template>
      </form>
    </template>

    <template v-else>
      <template
        v-if="currentEdition !== licensedEdition && !isPluginEditionFree"
      >
        <!-- Installed as a trial -->
        <c-btn icon="check" :disabled="true" large block>
          {{ 'Installed as a trial' | t('app') }}
        </c-btn>
      </template>

      <template v-else>
        <!-- Installed -->
        <c-btn icon="check" :disabled="true" block large>
          {{ 'Installed' | t('app') }}
        </c-btn>
      </template>
    </template>

    <template
      v-if="
        plugin.latestCompatibleVersion &&
        plugin.latestCompatibleVersion != plugin.version
      "
    >
      <div class="tw-text-gray-600 tw-mt-4">
        <p>
          {{
            'Only up to {version} is compatible with your version of Craft.'
              | t('app', {version: plugin.latestCompatibleVersion})
          }}
        </p>
      </div>
    </template>
    <template v-else-if="!plugin.latestCompatibleVersion">
      <div class="tw-text-gray-600 tw-mt-4">
        <p>
          {{
            'This plugin isn’t compatible with your version of Craft.'
              | t('app')
          }}
        </p>
      </div>
    </template>
    <template v-else-if="!plugin.phpVersionCompatible">
      <div class="tw-text-gray-600 tw-mt-4">
        <p v-if="plugin.incompatiblePhpVersion === 'php'">
          {{
            'This plugin requires PHP {v1}, but your environment is currently running {v2}.'
              | t('app', {
                v1: plugin.phpConstraint,
                v2: phpVersion(),
              })
          }}
        </p>
        <p v-else>
          {{
            'This plugin requires PHP {v1}, but your composer.json file is currently set to {v2}.'
              | t('app', {
                v1: plugin.phpConstraint,
                v2: composerPhpVersion(),
              })
          }}
        </p>
      </div>
    </template>
    <template v-else-if="!isPluginEditionFree && plugin.abandoned">
      <div class="tw-text-gray-600 tw-mt-4">
        <p>{{ 'This plugin is no longer maintained.' | t('app') }}</p>
      </div>
    </template>
  </div>
</template>

<script>
  /* global Craft */

  import {mapGetters} from 'vuex';
  import licensesMixin from '../mixins/licenses';
  import PluginEditionPrice from './PluginEditionPrice';

  export default {
    components: {PluginEditionPrice},
    mixins: [licensesMixin],

    props: {
      edition: {
        type: Object,
        required: true,
      },
      plugin: {
        type: Object,
        required: true,
      },
    },

    data() {
      return {
        loading: false,
        addToCartloading: false,
      };
    },

    computed: {
      ...mapGetters({
        getPluginLicenseInfo: 'craft/getPluginLicenseInfo',
        isInCart: 'cart/isInCart',
      }),

      pluginLicenseInfo() {
        return this.getPluginLicenseInfo(this.plugin.handle);
      },

      isPluginEditionFree() {
        return this.$store.getters['pluginStore/isPluginEditionFree'](
          this.edition
        );
      },

      isPluginInstalled() {
        return this.$store.getters['craft/isPluginInstalled'](
          this.plugin.handle
        );
      },

      isEditionMoreExpensiveThanLicensed() {
        // A plugin edition is buyable if it’s more expensive than the licensed one
        if (!this.edition) {
          return false;
        }

        if (this.pluginLicenseInfo) {
          const licensedEditionHandle = this.licensedEdition;
          const licensedEdition = this.plugin.editions.find(
            (edition) => edition.handle === licensedEditionHandle
          );

          if (
            licensedEdition &&
            this.edition.price &&
            parseFloat(this.edition.price) <= parseFloat(licensedEdition.price)
          ) {
            return false;
          }
        }

        return true;
      },

      licensedEdition() {
        if (!this.pluginLicenseInfo) {
          return null;
        }

        return this.pluginLicenseInfo.licensedEdition;
      },

      currentEdition() {
        if (!this.pluginLicenseInfo) {
          return null;
        }

        return this.pluginLicenseInfo.edition;
      },

      allowUpdates() {
        return Craft.allowUpdates && Craft.allowAdminChanges;
      },

      csrfTokenName() {
        return Craft.csrfTokenName;
      },

      csrfTokenValue() {
        return Craft.csrfTokenValue;
      },
    },

    methods: {
      addEditionToCart(editionHandle) {
        this.addToCartloading = true;

        const item = {
          type: 'plugin-edition',
          plugin: this.plugin.handle,
          edition: editionHandle,
        };

        this.$store
          .dispatch('cart/addToCart', [item])
          .then(() => {
            this.addToCartloading = false;
            this.$root.openModal('cart');
          })
          .catch(() => {
            this.addToCartloading = false;
          });
      },

      onSwitchOrInstallSubmit($ev) {
        this.loading = true;

        if (this.isPluginInstalled) {
          // Switch (prevent form submit)

          $ev.preventDefault();

          this.$store
            .dispatch('craft/switchPluginEdition', {
              pluginHandle: this.plugin.handle,
              edition: this.edition.handle,
            })
            .then(() => {
              this.loading = false;
              this.$root.displayNotice('Plugin edition changed.');
            });

          return false;
        }

        // Install (don’t prevent form submit)
      },

      phpVersion() {
        return window.phpVersion;
      },

      composerPhpVersion() {
        return window.composerPhpVersion;
      },
    },
  };
</script>

<style lang="scss">
  .plugin-actions {
    .c-spinner {
      @apply tw-absolute tw-left-1/2;
      bottom: -32px;
    }
  }
</style>
