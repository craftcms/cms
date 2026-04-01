<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import CraftInput from '@craftcms/cp/vue/CraftInput.vue';
  import {computed, ref} from 'vue';
  import {create} from '@actions/Settings/EntryTypesController';
  import Text from '@/components/Text.vue';
  import ReorderButton from '@/components/ReorderButton.vue';
  import ActionMenu from '@/components/ActionMenu.vue';

  export interface ComponentSelectProps<T = any> {
    modelValue?: T;
    id?: string;
    options?: Array<any>;
    limit?: number | null;
    selectable?: boolean;
    showHandles?: boolean;
    showIndicators?: boolean;
    showDescription?: boolean;
    allowOverrides?: boolean;
    sortable?: boolean;
    showActionMenus?: boolean;
    hyperLinks?: boolean;
    createAction?: string | null;
    disabled?: boolean;
    registerJs?: boolean;
    jsClass?: string;
    name?: string;
    inputName?: string;
    renderDefaultInput?: boolean;
    errors?: any;
  }

  export interface ComponentSelectEmits {
    (e: 'update:modelValue', value: Array<any>): void;
  }

  const emit = defineEmits<ComponentSelectEmits>();
  const props = withDefaults(defineProps<ComponentSelectProps>(), {
    modelValue: false,
    id: () => `componentselect`,
    options: () => [],
    limit: null,
    allowOverrides: false, // Entry types only
    showHandles: false,
    showIndicators: false,
    showDescription: false,
    sortable: true,
    showActionMenus: true,
    hyperLinks: false,
    createAction: null,
    disabled: false,
    registerJs: true,
    jsClass: 'Craft.ComponentSelectInput',
    renderDefaultInput: true,
    selectable: true,
  });

  const searchQuery = ref('');

  const isSortable = computed(
    () => props.limit && props.limit > 1 && props.sortable
  );
  const showSearchInput = computed(() => props.options.length > 5);
  const values = computed(() => props.modelValue ?? []);
  const selectedIds = computed(() =>
    props.modelValue.map((value: any) => value.id)
  );

  // @TODO sort options
  /**
   {# sort the options by handle, then label #}
   {% if showHandles %}
   {% set options = options|sort((a, b) => a is instance of('craft\\base\\Grippable') ? a.getHandle() <=> b is instance of('craft\\base\\Grippable') ? b.getHandle()) %}
   {% endif %}
   {% set options = options|sort((a, b) => a.getUiLabel() <=> b.getUiLabel()) %}
   */

  const inputName = computed(() => props.inputName ?? props.name ?? null);

  const selectableOptions = computed(() => {
    return props.options?.filter(
      (option) =>
        option.name.includes(searchQuery.value) ||
        option.handle.includes(searchQuery.value)
    );
  });

  function handleOptionSelect(option: any) {
    let newValue = [...props.modelValue];
    const itemIndex = newValue.findIndex((item) => item.id === option.id);
    if (itemIndex !== -1) {
      newValue.splice(itemIndex, 1);
    } else {
      newValue.push(option);
    }

    emit('update:modelValue', newValue);
  }

  function removeItem(itemId: number) {
    let newValue = [...props.modelValue];
    const itemIndex = newValue.findIndex((item) => item.id === itemId);
    if (itemIndex !== -1) {
      newValue.splice(itemIndex, 1);
    }

    emit('update:modelValue', newValue);
  }
</script>

<template>
  <template v-if="renderDefaultInput">
    <input type="hidden" :name="name" value="" />
  </template>
  <div>
    <ul class="grid gap-1">
      <slot name="components" :components="values">
        <li v-for="(component, idx) in values" :key="component.id ?? idx">
          <slot
            name="component"
            :component="component"
            :remove="() => removeItem(component.id)"
          >
            <craft-chip
              v-if="component"
              :icon="component.icon"
              :data-color="component.color?.value ?? component.color ?? 'white'"
            >
              <div :data-id="component.id">
                <div class="font-bold">
                  {{ component.name }}
                </div>
                <code>{{ component.handle }}</code>
              </div>

              <div slot="suffix" class="flex gap-1 items-center">
                <ActionMenu
                  :actions="[
                    {
                      label: t('Settings'),
                      icon: 'gear',
                      onClick: () => {
                        console.log('open settings slideout');
                      },
                    },
                    {
                      label: t('Remove'),
                      variant: 'danger',
                      icon: 'x',
                      onClick: () => {
                        console.log('clicked');
                        removeItem(component.id);
                      },
                    },
                  ]"
                />
                <ReorderButton variant="inherit"></ReorderButton>
              </div>
            </craft-chip>
          </slot>
        </li>
      </slot>
    </ul>

    <div class="flex gap-2 mt-3 items-center">
      <craft-action-menu v-if="options?.length">
        <craft-button type="button" slot="invoker" appearance="filled">
          <craft-icon name="chevron-down" slot="prefix"></craft-icon>
          {{ t('Choose') }}
        </craft-button>

        <div slot="content">
          <div class="p-2" v-if="showSearchInput">
            <CraftInput
              :label="t('Search')"
              v-model="searchQuery"
              label-sr-only
            >
              <craft-icon name="search" slot="prefix"></craft-icon>
            </CraftInput>
          </div>
          <hr class="m-0" />
          <template v-if="selectableOptions.length < 1">
            <div class="p-2">
              <Text
                template="No entry types match “{query}”"
                :params="{query: searchQuery}"
              />
            </div>
          </template>
          <template v-else>
            <template v-for="type in selectableOptions" :key="type.id">
              <craft-action-item
                @click="handleOptionSelect(type)"
                type="checkbox"
                :icon="type.icon ?? 'empty'"
                :checked="selectedIds.includes(type.id)"
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
  </div>
</template>

<style scoped lang="scss"></style>
