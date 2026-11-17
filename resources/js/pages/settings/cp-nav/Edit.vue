<script setup lang="ts">
  import {computed, ref} from 'vue';
  import {ButtonVariant, t} from '@craftcms/ui';
  import {router, useForm} from '@inertiajs/vue3';
  import {useAnnouncer} from '@/common/composables/useAnnouncer';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave';
  import CpContainer from '@/common/components/CpContainer.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import AdminTableNode from '@/modules/ui/AdminTableNode.vue';
  import CreateActionButton from '@/modules/admin-table/components/CreateActionButton.vue';
  import type {TableNodePayload} from '@/modules/ui/table-types';
  import type {UiPayload} from '@/modules/ui/types';
  import NavBuilder from '@/modules/cp-nav/components/NavBuilder.vue';
  import type {NavNode} from '@/modules/cp-nav/tree';

  const props = defineProps<{
    isDefault: boolean;
    tree: NavNode[];
    /** The nav with nothing customized, for resetting the builder to. */
    defaultTree: NavNode[];
    readOnly: boolean;
    saveUrl: string;
    /**
     * The user group navigations, for a tab beside the default navigation's
     * builder. Only the default navigation has it, on editions with group
     * navigations.
     */
    groupsTable?: UiPayload | null;
  }>();

  const groupsTableNode = computed(
    () => (props.groupsTable?.nodes[0] as TableNodePayload | undefined) ?? null
  );

  /**
   * The table without its create menu, which is shown below it instead, the
   * way the builder's Add button is.
   */
  const groupsTableWithoutCreate = computed((): TableNodePayload | null =>
    groupsTableNode.value
      ? {
          ...groupsTableNode.value,
          props: {
            ...groupsTableNode.value.props,
            createLabel: null,
            createUrl: null,
            createMenuItems: null,
          },
        }
      : null
  );

  /**
   * Whether the User Groups tab is showing, which has nothing to save, so the
   * builder's Save and Reset buttons are left out of the footer. The strip
   * picks its tab from the hash on load without announcing it.
   */
  const showingGroups = ref(
    Boolean(props.groupsTable) && window.location.hash === '#cp-nav-groups'
  );

  function onTabShow(event: Event): void {
    showingGroups.value =
      (event.target as HTMLElement & {selectedIndex: number}).selectedIndex ===
      1;
  }

  function refreshGroupsTable(): void {
    router.reload({only: ['groupsTable']});
  }

  const form = useForm<{nav: NavNode[]}>({nav: props.tree});
  const {announce} = useAnnouncer();

  const {save} = useSettingsSave(
    form,
    () => ({
      url: props.saveUrl,
      method: 'put',
    }),
    // The User Groups tab has nothing of its own to save.
    {keyboardShortcutEnabled: () => !showingGroups.value}
  );

  const isCustomized = computed(
    () => JSON.stringify(form.nav) !== JSON.stringify(props.defaultTree)
  );

  useAppLayout(() => ({
    form,
    hideFooter: showingGroups.value,
    onSave: save,
    // Kept when there's nothing to reset, so it never vanishes while focused.
    formAdditionalButtons: props.readOnly
      ? []
      : [
          {
            label: t('Reset'),
            variant: ButtonVariant.Plain,
            disabled: !isCustomized.value,
            disabledReason: isCustomized.value
              ? null
              : t('Nothing has been customized.'),
            onClick: reset,
          },
        ],
  }));

  /** Only resets the builder; nothing changes until the navigation is saved. */
  function reset(): void {
    if (
      form.isDirty &&
      !window.confirm(
        t('Reset the navigation? Your unsaved changes will be lost.')
      )
    ) {
      return;
    }

    form.nav = props.defaultTree;
    announce(t('Navigation reset. Save to keep it.'));
  }
</script>

<template>
  <div class="py-3">
    <LayoutSlot v-if="groupsTableNode" name="content-tabs">
      <craft-tabs :label="t('CP Navigation')" @craft-tab-show="onTabShow">
        <craft-tab slot="tab" controls="cp-nav-default">{{
          t('Default')
        }}</craft-tab>
        <craft-tab slot="tab" controls="cp-nav-groups">{{
          t('User Groups')
        }}</craft-tab>
      </craft-tabs>
    </LayoutSlot>

    <CpContainer>
      <section id="cp-nav-default">
        <p class="mb-lg">
          {{
            isDefault
              ? t(
                  'The navigation everyone gets. User group navigations build on it.'
                )
              : t(
                  'Starts from the default navigation. Only what you change here differs for this group.'
                )
          }}
        </p>

        <NavBuilder v-model="form.nav" :read-only="readOnly" />
      </section>

      <section v-if="groupsTableNode" id="cp-nav-groups" class="hidden">
        <p class="mb-lg">
          {{
            t(
              'People in more than one user group get the first of their groups’ navigations below. Everyone else gets the default navigation.'
            )
          }}
        </p>

        <AdminTableNode
          :node="groupsTableWithoutCreate!"
          :on-refresh="refreshGroupsTable"
        />

        <div v-if="groupsTableNode.props.createMenuItems?.length" class="mt-lg">
          <CreateActionButton
            :label="groupsTableNode.props.createLabel"
            :menu-items="groupsTableNode.props.createMenuItems"
            variant="dashed"
          />
        </div>
      </section>
    </CpContainer>
  </div>
</template>
