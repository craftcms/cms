<script setup lang="ts">
  import {computed, ref} from 'vue';
  import {router, usePage} from '@inertiajs/vue3';
  import {useEventListener} from '@vueuse/core';
  import {attrs, t} from '@craftcms/ui';
  import {openSlideout} from '@/common/slideouts';
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import HtmlFragmentRenderer from '@/common/components/HtmlFragmentRenderer.vue';
  import axios from 'axios';
  import UserScreen from '@/modules/user/components/UserScreen.vue';

  defineOptions({
    inheritAttrs: false,
  });

  type UserAddressesPageProps =
    CraftCms.Cms.Http.ViewModels.UserAddressesViewModel;

  // Read props through the page object (not a captured `page.props`
  // reference) so partial reloads — which replace `page.props` wholesale —
  // stay reactive.
  const page = usePage<UserAddressesPageProps>();
  const props = computed(() => page.props);

  // `data` is a mode-discriminated union; only cards mode carries `elements`.
  const cardsData = computed(() =>
    page.props.data.mode === 'cards' ? page.props.data : null
  );

  // Past the card limit the server switches to index mode; the embedded
  // element index arrives as a server-rendered fragment whose
  // `<craft-nested-element-manager>` wrapper initializes itself once injected.
  const indexFragment = computed(() =>
    page.props.data.mode === 'index' ? page.props.contentFragment : null
  );

  const createBtn = ref<HTMLElement | null>(null);
  const creating = ref(false);

  // Mirrors the legacy manager's `canCreate()`: permission, plus room under
  // `maxElements`.
  const canCreateMore = computed(() => {
    const data = cardsData.value;

    if (!data?.canCreate) {
      return false;
    }

    return !data.maxElements || data.elements.length < data.maxElements;
  });

  /**
   * The legacy `NestedElementManager.createElement()` flow: create a fresh
   * nested element via `elements/create`, edit it in an element editor
   * slideout, and refresh the cards once it's saved.
   */
  const createNestedElement = async () => {
    const data = cardsData.value;

    if (!data || creating.value || !canCreateMore.value) {
      return;
    }

    creating.value = true;

    try {
      const requestData = {
        elementType: data.elementType,
        ownerId: data.ownerId,
        fieldId: data.fieldId,
        siteId: data.ownerSiteId,
      };
      if (
        data.createAttributes instanceof Object &&
        !Array.isArray(data.createAttributes)
      ) {
        Object.assign(requestData, data.createAttributes);
      }

      const {data: response} = await Craft.sendActionRequest(
        'POST',
        'elements/create',
        {
          data: requestData,
        }
      );

      // `elements/create` hands back where the new draft is edited — the
      // element's own CP edit URL, or an `elements/edit` action URL for
      // element types that don't have one. It's only omitted outside the CP,
      // but checking beats opening a panel on `/undefined`.
      if (!response.cpEditUrl) {
        throw new Error('The new address came back without an edit URL.');
      }

      // `fresh` is what tells the editor this draft has never been saved, so
      // cancelling discards it. The legacy editor passed it the same way.
      const editUrl = new URL(response.cpEditUrl, window.location.origin);
      editUrl.searchParams.set('fresh', '1');

      // Focus returns to the create button when the panel closes.
      void openSlideout(editUrl.toString(), {
        opener: createBtn.value,
        onSaved: (result) => {
          // Drafts autosave as the user types; the cards only need
          // re-rendering once the address is actually saved.
          if (!result.draft) {
            router.reload({only: ['data']});
          }
        },
      });
    } catch (error) {
      // Prefer the server's message; fall back to the thrown one so a local
      // failure doesn't surface as an empty notification.
      Craft.cp?.displayError?.(
        (axios.isAxiosError(error)
          ? error.response?.data?.message
          : undefined) ?? (error instanceof Error ? error.message : undefined)
      );
    } finally {
      creating.value = false;
    }
  };

  // Refresh the cards when an editor slideout saves an element.
  useEventListener(window, 'craft:element-saved', () => {
    router.reload({only: ['data']});
  });

  // The listener lives on the cards' stable wrapper (not the grid itself,
  // which is `v-if`-ed away when the last card is deleted).
  const cardsContainer = ref<HTMLElement | null>(null);

  // The cards' server-provided action items (e.g. Delete) run their HTTP
  // request themselves via the action system and announce progress through
  // bubbling `action:change-state` events — refresh the cards once one
  // succeeds.
  useEventListener(cardsContainer, 'action:change-state', (event: Event) => {
    if (!(event instanceof CustomEvent)) {
      return;
    }
    const detail = event.detail;

    if (detail?.state === 'success' && detail?.actionType === 'http') {
      router.reload({only: ['data']});
    }
  });
</script>

<template>
  <UserScreen>
    <craft-pane appearance="raised">
      <div ref="cardsContainer" class="grid gap-3">
        <h2 v-if="!props.showIndex" class="text-lg m-0!">
          {{ t('Addresses') }}
        </h2>

        <HtmlFragmentRenderer v-if="indexFragment" :fragment="indexFragment" />

        <craft-empty v-if="cardsData && !cardsData.elements.length">
          {{ t('Nothing yet.') }}
        </craft-empty>

        <div v-if="cardsData?.elements.length" class="card-grid">
          <template v-for="element in cardsData?.elements" :key="element.id">
            <craft-card
              v-bind="attrs(element.cardAttributes, {exclude: ['class']})"
              :thumb-alignment="element.thumbAlignment"
            >
              <div slot="label">
                <DynamicHtmlRenderer :html="element.cardLabelHtml" />
              </div>
              <div slot="actions">
                <DynamicHtmlRenderer :html="element.cardActionsHtml" />
              </div>
              <div v-if="element.cardThumbHtml" slot="thumbnail">
                <DynamicHtmlRenderer :html="element.cardThumbHtml" />
              </div>
              <DynamicHtmlRenderer :html="element.cardContentHtml" />
            </craft-card>
          </template>
        </div>

        <div v-if="cardsData?.canCreate" class="flex">
          <craft-button
            ref="createBtn"
            class="add-btn"
            icon="plus"
            appearance="outline"
            :loading="creating"
            :disabled="!canCreateMore"
            @click="createNestedElement"
          >
            {{ cardsData.createButtonLabel }}
          </craft-button>
        </div>
      </div>
    </craft-pane>
  </UserScreen>
</template>

<style scoped lang="scss">
  .card-grid {
    display: grid;
    gap: var(--c-spacing-md);
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  }

  /* The dashed "add" affordance the legacy `.btn.add.icon.dashed` styles gave
     the create button. */
  .add-btn {
    --c-button-border-style: dashed;
  }
</style>
