<script setup lang="ts">
  import type {EntryType} from '@/types';
  import {computed, ref} from 'vue';
  import {appendBodyHtml, appendHeadHtml, t} from '@craftcms/cp';
  import CraftInput from '@craftcms/cp/vue/CraftInput.vue';
  import Text from '@/components/Text.vue';
  import {
    applyOverrideSettings,
    renderOverrideSettings,
  } from '@actions/Settings/EntryTypesController';
  import type {SlideoutInstance} from '@/types/globals';
  import EntryTypeChip from '@/components/EntryType/EntryTypeChip.vue';
  import CreateEntryTypeButton from '@/components/EntryType/CreateEntryTypeButton.vue';
  import {router} from '@inertiajs/vue3';
  import DragShadow from '@/components/DragShadow.vue';
  import {useReorderableItems} from '@/composables/useReorderableItems';

  const emit = defineEmits<{
    (e: 'update:modelValue', value: Array<EntryType>): void;
  }>();
  const props = defineProps<{
    modelValue: Array<EntryType>;
    entryTypes: Array<EntryType>;
    actions?: Array<any>;
  }>();

  const entryTypeQuery = ref('');

  const selectableTypes = computed(() => {
    return props.entryTypes?.filter(
      (entryType) =>
        entryType.name.includes(entryTypeQuery.value) ||
        entryType.handle.includes(entryTypeQuery.value)
    );
  });

  const {setItemRef, setHandleRef, getDragState, getDropState} =
    useReorderableItems({
      getItemIds: () => props.modelValue.map((et) => et.id),
      onReorder: (startIndex, finishIndex) => {
        const items = [...props.modelValue];
        const [removed] = items.splice(startIndex, 1);
        items.splice(finishIndex, 0, removed);
        emit('update:modelValue', items);
      },
      enabled: () => props.modelValue.length > 1,
    });

  function handleTypeSelect(type: EntryType) {
    if (props.modelValue.find((item) => item.id === type.id)) {
      removeItem(type.id);
    } else {
      emit('update:modelValue', [...props.modelValue, type]);
    }
  }

  function removeItem(itemId: number) {
    emit('update:modelValue', [
      ...props.modelValue.filter((item) => item.id !== itemId),
    ]);
  }

  const slideout = ref<SlideoutInstance | undefined>(undefined);
  const overrides = ref({});

  function createSlideout(
    innerHtml: string,
    {namespace = '', id = null}: {namespace: string; id: null | number}
  ) {
    const template = `
      <div class="entry-type-override-settings-body">
        <div class="fields">
          ${namespace ? `<input type="hidden" name="settingsNamespace" value="${namespace}" />` : ''}
          ${id ? `<input type="hidden" name="id" value="${id}" />` : ''}
          ${innerHtml}
        </div>
      </div>
      <div class="entry-type-override-settings-footer justify-end gap-2">
        <craft-button type="button" data-action="close">
          ${t('Close')}
        </craft-button>
        <craft-button type="submit">${t('Apply')}</craft-button>
      </div>
    `;

    const slideout = new Craft.Slideout(template, {
      containerElement: 'form',
      containerAttributes: {
        action: applyOverrideSettings().url,
        method: 'post',
        novalidate: '',
        class: 'entry-type-override-settings',
      },
    });

    const form = slideout.$container[0];
    if (!form) {
      return;
    }

    form.addEventListener('submit', async (event: SubmitEvent) => {
      event.preventDefault();
      const target = event.target as HTMLFormElement;
      const body = new FormData(target);

      // We need to massage our form data into the format the server is expecting
      const postData = {
        id: body.get('id'),
        settingsNamespace: body.get('settingsNamespace'),
        settings: new URLSearchParams(body as any).toString(),
      };

      try {
        const {data} = await Craft.sendActionRequest(
          'POST',
          applyOverrideSettings().url,
          {
            data: postData,
          }
        );

        // set the data to some state
        // The data comes back with `chipHtml` and `config`
        overrides.value = {
          ...overrides.value,
          [data.config.id]: data.config,
        };

        // Update the modelValue
        emit(
          'update:modelValue',
          props.modelValue.map((entryType) => {
            if (entryType.id === data.entryType.id) {
              return {
                ...entryType,
                ...data.entryType,
              };
            }

            return entryType;
          })
        );
        slideout.close();
      } catch (e) {
        console.error(e);
      }
    });

    // Bind up the buttons
    form.querySelectorAll('[data-action]').forEach((el: HTMLElement) => {
      el.addEventListener('click', (e: Event) => {
        const target = e.target as HTMLElement;
        if (!target) {
          return;
        }

        const action = target.dataset.action;
        switch (action) {
          case 'close':
            slideout.close();
            break;
        }
      });
    });

    slideout.on('close', () => {
      slideout.destroy();
    });

    return slideout;
  }

  async function openSlideout(id: number) {
    try {
      const entryType = props.modelValue.find((item) => item.id === id);
      const {data} = await Craft.sendActionRequest(
        'POST',
        renderOverrideSettings().url,
        {
          data: {
            id,
            name: entryType?.name,
            handle: entryType?.handle,
            description: entryType?.description,
          },
        }
      );

      const {settingsHtml, headHtml, bodyHtml, namespace} = data;
      slideout.value = createSlideout(settingsHtml, {namespace, id});

      if (headHtml) {
        await appendHeadHtml(headHtml);
      }

      if (bodyHtml) {
        await appendBodyHtml(bodyHtml);
      }

      Craft?.initUiElements(slideout.value?.$container);
    } catch (e: any) {
      Craft.cp?.displayError?.(e?.response?.data?.message);
      throw e;
    }
  }
</script>

<template>
  <div class="entry-type-list">
    <template v-for="entryType in modelValue" :key="entryType.id">
      <div
        :ref="(el) => setItemRef(el as HTMLElement, entryType.id)"
        class="entry-type-item"
        :class="{
          'entry-type-item--dragging':
            getDragState(entryType.id).type === 'is-dragging',
          'entry-type-item--hidden':
            getDragState(entryType.id).type === 'is-dragging-and-left-self',
        }"
      >
        <!-- Shadow before item when dragged over top edge -->
        <DragShadow
          v-if="
            getDropState(entryType.id).type === 'is-over' &&
            getDropState(entryType.id).closestEdge === 'top'
          "
          :height="getDropState(entryType.id).draggingRect?.height"
        />

        <EntryTypeChip
          :name="entryType.name"
          :id="entryType.id"
          :handle="entryType.handle"
          :color="entryType.color"
          :description="entryType.description"
          :draggable="modelValue.length > 1"
          :actions="[
            {
              label: t('Settings'),
              icon: 'gear',
              onClick: () => openSlideout(entryType.id),
            },
            {
              label: t('Remove'),
              variant: 'danger',
              icon: 'x',
              onClick: () => removeItem(entryType.id),
            },
          ]"
          @handle-ref="(el) => setHandleRef(el, entryType.id)"
        />

        <!-- Shadow after item when dragged over bottom edge -->
        <DragShadow
          v-if="
            getDropState(entryType.id).type === 'is-over' &&
            getDropState(entryType.id).closestEdge === 'bottom'
          "
          :height="getDropState(entryType.id).draggingRect?.height"
        />
      </div>
    </template>
  </div>

  <div class="flex gap-2 mt-3 items-center">
    <craft-action-menu v-if="entryTypes?.length">
      <craft-button type="button" slot="invoker" appearance="filled">
        <craft-icon name="chevron-down" slot="prefix"></craft-icon>
        {{ t('Choose') }}
      </craft-button>

      <div slot="content">
        <div class="p-2">
          <CraftInput
            :label="t('Search')"
            v-model="entryTypeQuery"
            label-sr-only
          >
            <craft-icon name="search" slot="prefix"></craft-icon>
          </CraftInput>
        </div>
        <hr class="m-0" />
        <template v-if="selectableTypes.length < 1">
          <div class="p-2">
            <Text
              template="No entry types match “{query}”"
              :params="{query: entryTypeQuery}"
            />
          </div>
        </template>
        <template v-else>
          <template v-for="entryType in selectableTypes" :key="entryType.id">
            <craft-action-item
              @click="handleTypeSelect(entryType)"
              type="checkbox"
              :icon="entryType.icon ?? 'empty'"
              :checked="
                modelValue.find((valueType) => valueType.id === entryType.id)
              "
              :data-color="entryType.color?.value ?? 'white'"
            >
              <div>
                {{ entryType.name }}
                <pre>{{ entryType.handle }}</pre>
              </div>
            </craft-action-item>
          </template>
        </template>
      </div>
    </craft-action-menu>
    <CreateEntryTypeButton @success="router.reload({only: ['entryTypes']})" />
  </div>
</template>

<style scoped lang="scss">
  .entry-type-list {
    display: inline-flex;
    flex-direction: column;
    gap: var(--c-spacing-sm);
  }

  .entry-type-item {
    display: flex;
    flex-direction: column;
    align-items: stretch;
  }

  // Item is being dragged but still over itself - show with reduced opacity
  .entry-type-item--dragging {
    opacity: 0.4;
  }

  // Item has been dragged away from itself - collapse vertically but maintain width
  // This ensures the container doesn't shrink when the widest item is dragged
  .entry-type-item--hidden {
    visibility: hidden;
    height: 0;
    overflow: hidden;
    margin: 0;
    padding: 0;
  }
</style>
