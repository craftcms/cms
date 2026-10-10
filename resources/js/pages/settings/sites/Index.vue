<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import type {TextExpanderTriggers} from '@craftcms/ui/components/text-expander/text-expander';
  import CalloutReadOnly from '@/common/components/CalloutReadOnly.vue';
  import AdminTableNode from '@/modules/ui/AdminTableNode.vue';
  import type {TableNodePayload} from '@/modules/ui/table-types';
  import type {UiPayload} from '@/modules/ui/types';
  import {computed, ref} from 'vue';
  import type {SiteGroup} from '@/common/types';
  import ModalForm from '@/common/components/ModalForm.vue';
  import {Deferred, router, useForm} from '@inertiajs/vue3';
  import {destroy, store} from '@actions/Settings/SiteGroupsController.js';
  import {create} from '@actions/Settings/SitesController';
  import DeleteSiteModal from '@/modules/sites/components/DeleteSiteModal.vue';
  import CpButtonLink from '@/common/components/CpButtonLink.vue';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
  import useCraftData from '@/common/composables/useCraftData';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';

  const props = defineProps<{
    title: string;
    group: SiteGroup | null;
    ui: Omit<UiPayload, 'nodes'> & {nodes: [TableNodePayload]};
    nameTextExpanderTriggers?: TextExpanderTriggers;
    flash: {
      success: string | null;
      error: string | null;
    };
  }>();

  const modalActive = ref(false);
  const tableNode = computed(() => props.ui.nodes[0]);
  const {readOnly} = useCraftData();

  const form = useForm({
    id: props.group?.id ?? null,
    name: props.group?.name ?? '',
  });

  function saveGroup() {
    if (form.processing) return;

    form.clearErrors().submit(store(), {
      onSuccess: () => {
        modalActive.value = false;
        form.reset();
      },
    });
  }

  function openModal(mode: 'create' | 'update') {
    if (mode === 'create') {
      form.name = '';
      form.id = null;
    } else if (mode === 'update') {
      form.name = props.group?.rawName ?? props.group?.name ?? '';
      form.id = props.group?.id ?? null;
    }

    modalActive.value = true;
  }

  function handleDeleteClick() {
    if (
      props.group?.id &&
      tableNode.value.props.rows.length === 0 &&
      // @TODO custom confirmation dialog?
      confirm(t('Are you sure you want to delete this group?'))
    ) {
      router.delete(destroy({groupId: props.group.id}));
    }
  }

  useAppLayout(() => ({
    title: props.title,
    // Described rather than slotted so the secondary nav can render it as a
    // button when it's expanded and as a menu item once it collapses.
    subnavActions: readOnly.value
      ? []
      : [
          {
            label: t('New Group'),
            icon: 'plus',
            onClick: () => openModal('create'),
          },
        ],
  }));
</script>

<template>
  <div class="contents">
    <LayoutSlot name="title">
      <div class="flex gap-md items-center">
        <h1 class="title text-xl">
          {{ title }}
        </h1>

        <craft-action-menu v-if="group?.id && !readOnly">
          <craft-button type="button" icon size="small" slot="invoker">
            <craft-icon
              name="gear"
              :label="t('Site group Actions')"
            ></craft-icon>
          </craft-button>

          <div slot="content">
            <craft-action-item @click.prevent="openModal('update')">
              {{ t('Rename Group') }}
            </craft-action-item>
            <craft-action-item
              variant="danger"
              .disabled="tableNode.props.rows.length > 0"
              @click.prevent="handleDeleteClick"
            >
              {{ t('Delete Group') }}
            </craft-action-item>
          </div>
        </craft-action-menu>
      </div>
    </LayoutSlot>
    <div class="@container">
      <template v-if="readOnly">
        <CalloutReadOnly />
      </template>

      <AdminTableNode padded :node="tableNode">
        <template #delete-modal="{row, label, close}">
          <DeleteSiteModal
            open
            :site="{id: Number(row.id), name: label}"
            @close="close"
          />
        </template>
        <template #empty-row>
          <craft-empty
            icon="light/earth-americas"
            :label="t('No sites exist yet.')"
          >
            <CpButtonLink
              v-if="!readOnly"
              :href="create({}, {query: {groupId: group?.id}}).url"
            >
              <craft-icon name="plus" slot="prefix"></craft-icon>
              {{ t('New Site') }}
            </CpButtonLink>
          </craft-empty>
        </template>
      </AdminTableNode>
    </div>

    <ModalForm
      :title="form.id ? t('Rename Group') : t('New Group')"
      :is-active="modalActive"
      :loading="form.processing"
      @submit="saveGroup"
      @close="
        modalActive = false;
        form.reset();
      "
    >
      <Deferred data="nameTextExpanderTriggers">
        <template #fallback>
          <craft-input
            readonly
            name="readonly-name"
            :label="t('Group Name')"
            :help-text="
              t('What this group will be called in the control panel.')
            "
          >
            <div slot="after">
              <craft-callout
                variant="info"
                appearance="plain"
                class="p-0"
                icon="lightbulb"
              >
                {{ t('Type `$` to choose an environment variable.') }}
                <a
                  href="https://craftcms.com/docs/5.x/configure.html#control-panel-settings"
                  >{{ t('Learn more') }}</a
                >
              </craft-callout>
            </div>
          </craft-input>
        </template>
        <CraftInput
          :label="t('Group Name')"
          id="name"
          name="name"
          required
          :help-text="t('What this group will be called in the control panel.')"
          :text-expander-triggers="nameTextExpanderTriggers"
          v-model="form.name"
          :error="form.errors?.name"
        >
          <craft-callout
            slot="after"
            variant="info"
            appearance="plain"
            class="p-0"
            icon="lightbulb"
          >
            {{ t('Type `$` to choose an environment variable.') }}
            <a
              href="https://craftcms.com/docs/5.x/configure.html#control-panel-settings"
              >{{ t('Learn more') }}</a
            >
          </craft-callout>
        </CraftInput>
      </Deferred>
    </ModalForm>
  </div>
</template>

<style scoped lang="scss">
  .title {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-md);
  }
</style>
