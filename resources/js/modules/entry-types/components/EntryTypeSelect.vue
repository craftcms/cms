<script setup lang="ts">
  import type {EntryType} from '@/common/types';
  import {computed, ref} from 'vue';
  import {
    appendBodyHtml,
    appendHeadHtml,
    ButtonVariant,
    serializeFormInputs,
    t,
  } from '@craftcms/ui';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
  import Text from '@/common/components/Text.vue';
  import {
    applyOverrideSettings,
    create,
    renderOverrideSettings,
  } from '@actions/Settings/EntryTypesController';
  import type {SlideoutInstance} from '@/common/types/globals';
  import EntryTypeChip from '@/modules/entry-types/components/EntryTypeChip.vue';
  import SlideoutButton from '@/common/components/SlideoutButton.vue';
  import type {SlideoutSaveResult} from '@/common/slideouts';
  import {router} from '@inertiajs/vue3';
  import DragShadow from '@/common/components/DragShadow.vue';
  import {
    useReorderableItems,
    type DropState,
  } from '@/common/composables/useReorderableItems';
  import useCraftData from '@/common/composables/useCraftData';

  const emit = defineEmits<{
    (e: 'update:modelValue', value: Array<EntryType>): void;
  }>();
  const props = defineProps<{
    modelValue: Array<EntryType>;
    entryTypes: Array<EntryType>;
    actions?: Array<any>;
  }>();

  const {readOnly} = useCraftData();
  const entryTypeQuery = ref('');

  const selectableTypes = computed(() => {
    return props.entryTypes?.filter(
      (entryType) =>
        entryType.name.includes(entryTypeQuery.value) ||
        entryType.handle.includes(entryTypeQuery.value)
    );
  });

  function reorder(startIndex: number, finishIndex: number) {
    const items = [...props.modelValue];
    const [removed] = items.splice(startIndex, 1);
    if (removed === undefined) return;
    items.splice(finishIndex, 0, removed);
    emit('update:modelValue', items);
  }

  function getRowPosition(index: number) {
    if (index === 0) {
      return 'first';
    }

    if (index === props.modelValue.length - 1) {
      return 'last';
    }

    return 'middle';
  }

  const {setItemRef, setHandleRef, getDragState, getDropState} =
    useReorderableItems({
      getItemIds: () => props.modelValue.map((et) => et.id),
      onReorder: reorder,
      enabled: () => props.modelValue.length > 1,
    });

  function getOverDropState(
    id: number | string
  ): Extract<DropState, {type: 'is-over'}> | null {
    const state = getDropState(id);
    return state.type === 'is-over' ? state : null;
  }

  function handleTypeSelect(type: EntryType) {
    if (props.modelValue.find((item) => item.id === type.id)) {
      removeItem(type.id);
    } else {
      emit('update:modelValue', [...props.modelValue, type]);
    }
  }

  function removeItem(itemId: number) {
    emit(
      'update:modelValue',
      props.modelValue.filter((item) => item.id !== itemId)
    );
  }

  function selectCreatedEntryType({data}: SlideoutSaveResult) {
    // SAFETY: the create-entry-type slideout returns its saved EntryType under data.entryType.
    const entryType = data?.entryType as EntryType | undefined;

    if (!entryType) {
      throw new Error('The created entry type was not returned.');
    }

    emit('update:modelValue', [...props.modelValue, entryType]);
    router.reload({only: ['entryTypes']});
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
      <div class="entry-type-override-settings-footer cp:justify-end cp:gap-2">
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
    if (!(form instanceof HTMLFormElement)) {
      throw new Error('The entry type slideout form was not created.');
    }

    form.addEventListener('submit', async (event: SubmitEvent) => {
      event.preventDefault();
      if (!(event.target instanceof HTMLFormElement)) {
        throw new Error('The entry type form submission has no form target.');
      }
      const target = event.target;
      const body = new FormData(target);

      // We need to massage our form data into the format the server is expecting
      const postData = {
        id: body.get('id'),
        settingsNamespace: body.get('settingsNamespace'),
        settings: serializeFormInputs(target),
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
    form.querySelectorAll<HTMLElement>('[data-action]').forEach((el) => {
      el.addEventListener('click', (e: Event) => {
        if (!(e.target instanceof HTMLElement)) {
          return;
        }
        const target = e.target;

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
    <template v-for="(entryType, index) in modelValue" :key="entryType.id">
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
          v-if="getOverDropState(entryType.id)?.closestEdge === 'top'"
          :height="getOverDropState(entryType.id)?.draggingRect?.height"
        />

        <EntryTypeChip
          :name="entryType.name"
          :id="entryType.id"
          :handle="entryType.handle"
          :color="entryType.color"
          :icon="entryType.icon"
          :description="entryType.description"
          :draggable="modelValue.length > 1"
          :indicators="entryType.indicators"
          :actions="
            !readOnly
              ? [
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
                ]
              : []
          "
          @handle-ref="(el) => setHandleRef(el, entryType.id)"
        >
          <template #drag-handle>
            <craft-reorder-button
              v-if="!readOnly"
              variant="inherit"
              :position="getRowPosition(index)"
              @reorder="
                (e: CustomEvent<{direction: 'up' | 'down'}>) =>
                  reorder(
                    index,
                    e.detail.direction === 'up' ? index - 1 : index + 1
                  )
              "
            ></craft-reorder-button>
          </template>
        </EntryTypeChip>

        <!-- Shadow after item when dragged over bottom edge -->
        <DragShadow
          v-if="getOverDropState(entryType.id)?.closestEdge === 'bottom'"
          :height="getOverDropState(entryType.id)?.draggingRect?.height"
        />
      </div>
    </template>
  </div>

  <div class="cp:flex cp:gap-2 cp:mt-3 cp:items-center">
    <craft-action-menu v-if="entryTypes?.length">
      <craft-button
        type="button"
        slot="invoker"
        :variant="ButtonVariant.Dashed"
        v-if="!readOnly"
      >
        <craft-icon name="chevron-down" slot="prefix"></craft-icon>
        {{ t('Choose') }}
      </craft-button>

      <div slot="content">
        <div class="cp:p-2">
          <CraftInput
            :label="t('Search')"
            v-model="entryTypeQuery"
            label-sr-only
          >
            <craft-icon name="search" slot="prefix"></craft-icon>
          </CraftInput>
        </div>
        <hr class="cp:m-0" />
        <template v-if="selectableTypes.length < 1">
          <div class="cp:p-2">
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
              :data-color="
                (entryType.color && typeof entryType.color !== 'string'
                  ? entryType.color.value
                  : entryType.color) ?? 'white'
              "
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
    <SlideoutButton
      v-if="!readOnly"
      :url="create().url"
      @success="selectCreatedEntryType"
    >
      <craft-icon name="plus" slot="prefix"></craft-icon>
      {{ t('Create') }}
    </SlideoutButton>
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
