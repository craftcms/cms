<script setup lang="ts">
  import type {EntryType} from '@/types';
  import {computed, ref} from 'vue';
  import {appendBodyHtml, appendHeadHtml, t} from '@craftcms/cp';
  import ReorderButton from '@/components/ReorderButton.vue';
  import ActionMenu from '@/components/ActionMenu.vue';
  import CraftInput from '@craftcms/cp/vue/CraftInput.vue';
  import Text from '@/components/Text.vue';
  import {
    applyOverrideSettings,
    create,
    renderOverrideSettings,
  } from '@actions/Settings/EntryTypesController';
  import type {SlideoutInstance} from '@/types/globals';

  const emit = defineEmits<{
    (e: 'update:modelValue', value: Array<number>): void;
  }>();
  const props = defineProps<{
    modelValue: Array<number>;
    types: Array<EntryType>;
    actions?: Array<any>;
  }>();

  const selectedTypes = computed(() => {
    return props.modelValue
      .map((id) => {
        return props.types?.find((type) => type.id === id) ?? null;
      })
      .filter(Boolean);
  });

  const entryTypeQuery = ref('');

  const selectableTypes = computed(() => {
    return props.types?.filter(
      (type) =>
        type.name.includes(entryTypeQuery.value) ||
        type.handle.includes(entryTypeQuery.value)
    );
  });

  function handleTypeSelect(type: EntryType) {
    let newValue = [...props.modelValue];
    if (newValue.includes(type.id)) {
      newValue.splice(newValue.indexOf(type.id), 1);
    } else {
      newValue.push(type.id);
    }

    emit('update:modelValue', newValue);
  }

  function removeItem(itemId: number) {
    let newValue = [...props.modelValue];
    if (newValue.includes(itemId)) {
      newValue.splice(newValue.indexOf(itemId), 1);
    }
    emit('update:modelValue', newValue);
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
          // case 'apply':
          //   form.requestSubmit();
          //   break;
        }
      });
    });

    slideout.on('open', () => {
      console.log('opened');
      // Focus first text field
    });

    slideout.on('close', () => {
      slideout.destroy();
    });

    return slideout;
  }

  async function openSlideout(id: number) {
    try {
      const {data} = await Craft.sendActionRequest(
        'POST',
        renderOverrideSettings().url,
        {
          data: {id},
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
  <div>
    <template v-for="type in selectedTypes">
      <craft-chip
        v-if="type"
        :icon="type.icon"
        :data-color="type.color?.value ?? 'white'"
      >
        <div :data-id="type.id">
          <div class="font-bold">
            {{ overrides[type.id]?.name ?? type.name }}
          </div>
          <code>{{ overrides[type.id]?.handle ?? type.handle }}</code>
        </div>

        <div slot="suffix" class="flex gap-1 items-center">
          <ActionMenu
            :actions="[
              {
                label: t('Settings'),
                icon: 'gear',
                onClick: () => openSlideout(type.id),
              },
              {
                label: t('Remove'),
                variant: 'danger',
                icon: 'x',
                onClick: () => removeItem(type.id),
              },
            ]"
          />
          <ReorderButton variant="inherit"></ReorderButton>
        </div>
      </craft-chip>
    </template>
  </div>

  <div class="flex gap-2 mt-3 items-center">
    <craft-action-menu v-if="types?.length">
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
          <template v-for="type in selectableTypes" :key="type.id">
            <craft-action-item
              @click="handleTypeSelect(type)"
              type="checkbox"
              :icon="type.icon ?? 'empty'"
              :checked="modelValue.includes(type.id)"
              :data-color="type.color?.value ?? 'white'"
            >
              <div>
                {{ type.name }}
                <pre>{{ type.handle }}</pre>
              </div>
            </craft-action-item>
          </template>
        </template>
      </div>
    </craft-action-menu>
    <a :href="create['/admin/settings/entry-types/new']().url" class="">
      <craft-icon name="plus" slot="prefix"></craft-icon>
      {{ t('Create') }}
    </a>
  </div>

  <!--<Teleport to="body">-->
  <!--  <div class="slideout right-0" v-if="slideoutHtml" ref="slideoutTemplate">-->
  <!--    <div class="entry-type-override-settings-body">-->
  <!--      <div class="fields" v-html="slideoutHtml"></div>-->
  <!--    </div>-->
  <!--    <div class="entry-type-override-settings-footer">-->
  <!--      <craft-button type="button" @click="() => slideout.close()">{{-->
  <!--        t('Close')-->
  <!--      }}</craft-button>-->
  <!--      <craft-button type="submit">{{ t('Apply') }}</craft-button>-->
  <!--    </div>-->
  <!--  </div>-->
  <!--</Teleport>-->
</template>

<style scoped lang="scss">
  craft-chip::part(chip) {
    min-width: 200px;
  }

  // Some special styles for nice icon alignment. We might want to move this
  // into chips, but for right now this is the only spot
  craft-chip::part(prefix) {
    align-self: start;
    height: 1lh;
    justify-content: center;
  }
</style>
