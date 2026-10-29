<script setup lang="ts">
  import {computed, h, nextTick, ref} from 'vue';
  import {router, useHttp, usePage} from '@inertiajs/vue3';
  import {t} from '@craftcms/ui';
  import CraftCheckboxGroup from '@craftcms/ui/vue/CraftCheckboxGroup.vue';
  import CraftField from '@craftcms/ui/vue/CraftField.vue';
  import {useCraftTable} from '@/common/table/craftTable';
  import CraftDate from '@/common/components/Date.vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import ModalForm from '@/common/components/ModalForm.vue';
  import Select from '@/common/form/Select.vue';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import {createCraftColumnHelper} from '@/common/table/createCraftColumnHelper';
  import {elevatedSessionManager} from '@/modules/auth/elevated-session';
  import UserScreen from '@/modules/user/components/UserScreen.vue';
  import {
    destroy as revokeAction,
    store as createAction,
    update as updateAction,
  } from '@actions/Users/PersonalAccessTokensController';

  defineOptions({
    inheritAttrs: false,
  });

  const page = usePage<CraftCms.Cms.Http.ViewModels.UserTokensViewModel>();

  type Grant = (typeof page.props.grants)[number];

  // Re-request the full elevated-session window (capped at 5 minutes), so it
  // lasts while the user fills in the form.
  const ELEVATED_SETUP_SECONDS = 300;

  const modalActive = ref(false);
  // The token or app being viewed, or null when creating a token.
  const editing = ref<Grant | null>(null);
  const newToken = ref<string | null>(null);

  const createRequest = useHttp<
    {name: string; scopes: string[]; expiresIn: number},
    {token: string}
  >({
    name: '',
    scopes: [],
    expiresIn: 30,
  });
  const updateRequest = useHttp<{tokenId: string; name: string}, unknown>({
    tokenId: '',
    name: '',
  });
  const revokeRequest = useHttp<
    {clientId: string; tokenId: string | null},
    unknown
  >({clientId: '', tokenId: null});

  // The groups and scopes shown in the modal. Existing tokens show the scopes
  // they were granted, even ones the user can no longer grant.
  const modalGroups = computed(() => {
    const grant = editing.value;

    if (!grant) {
      return page.props.groups;
    }

    return Object.entries(grant.scopes).map(([handle, scopes]) => ({
      handle,
      label: page.props.scopeGroups[handle]?.label ?? handle,
      scopes: scopes.map((scope) => ({
        value: scope,
        label: page.props.scopeGroups[handle]?.scopes[scope] ?? scope,
      })),
    }));
  });
  const editingApp = computed(() => !!editing.value && !editing.value.personal);
  const modalTitle = computed(() => {
    if (!editing.value) {
      return t('New token');
    }

    return editing.value.personal ? t('Edit token') : editing.value.name;
  });
  const expiryOptions = [
    {label: t('7 days'), value: '7'},
    {label: t('30 days'), value: '30'},
    {label: t('90 days'), value: '90'},
    {label: t('1 year'), value: '365'},
  ];

  // Apps show the APIs they were authorized for.
  function grantType(grant: Grant): string {
    if (grant.personal) {
      return t('Personal');
    }

    const labels = Object.keys(grant.scopes)
      .map((handle) => page.props.scopeGroups[handle]?.label)
      .filter(Boolean);

    return labels.length ? labels.join(', ') : t('App');
  }

  function grantKey(grant: Grant): string {
    return `${grant.clientId}:${grant.tokenId ?? ''}`;
  }

  async function openNewTokenModal() {
    const confirmed = await elevatedSessionManager.require({
      minimumRemainingSeconds: ELEVATED_SETUP_SECONDS,
    });

    if (!confirmed) {
      return;
    }

    editing.value = null;
    createRequest.reset();
    createRequest.clearErrors();
    modalActive.value = true;
  }

  function openTokenModal(grant: Grant) {
    editing.value = grant;
    createRequest.clearErrors();
    createRequest.name = grant.name;
    createRequest.scopes = Object.values(grant.scopes).flat();
    modalActive.value = true;
  }

  async function submitModal() {
    if (!editing.value) {
      return createToken();
    }

    if (editingApp.value) {
      modalActive.value = false;
      return;
    }

    if (updateRequest.processing) {
      return;
    }

    updateRequest.tokenId = editing.value.tokenId ?? '';
    updateRequest.name = createRequest.name;

    const response = await updateRequest
      .post(updateAction().url, {
        onError: () => {
          createRequest.setError('name', updateRequest.errors.name ?? '');
          nextTick(focusFirstInvalid);
        },
      })
      .catch(() => null);

    if (response) {
      modalActive.value = false;
      router.reload({only: ['grants']});
    }
  }

  async function createToken() {
    if (createRequest.processing) {
      return;
    }

    // Validation errors reject the request after they're set on the form.
    const response = await createRequest
      .post(createAction().url, {onError: () => nextTick(focusFirstInvalid)})
      .catch(() => null);

    if (response) {
      modalActive.value = false;
      newToken.value = response.token;
      router.reload({only: ['grants']});
    }
  }

  function focusFirstInvalid() {
    const invalid = createRequest.errors.name
      ? document.getElementById('token-name')
      : document.querySelector<HTMLElement>('#token-scopes craft-checkbox');
    invalid?.focus();
  }

  async function revoke(grant: Grant) {
    if (
      !confirm(
        t(
          'Are you sure you want to revoke “{name}”? Anything using it will lose access.',
          {name: grant.name}
        )
      )
    ) {
      return;
    }

    revokeRequest.clientId = grant.clientId;
    revokeRequest.tokenId = grant.tokenId;

    if (await revokeRequest.post(revokeAction().url)) {
      router.reload({only: ['grants']});
    }
  }

  const columnHelper = createCraftColumnHelper<Grant>();
  const table = useCraftTable<Grant>({
    get data() {
      return page.props.grants;
    },
    get columns() {
      return [
        columnHelper.display({
          id: 'name',
          header: t('Name'),
          meta: {cellTag: 'th'},
          cell: ({row}) => row.original.name,
        }),
        columnHelper.display({
          id: 'type',
          header: t('Type'),
          cell: ({row}) => grantType(row.original),
        }),
        columnHelper.display({
          id: 'expiresAt',
          header: t('Expires on'),
          cell: ({row}) => h(CraftDate, {value: row.original.expiresAt}),
        }),
        columnHelper.actions(({row}) => [
          h(ActionMenu, {
            label: t('Actions for {name}', {name: row.original.name}),
            actions: [
              {
                icon: 'pencil',
                label: t('Edit'),
                onClick: () => openTokenModal(row.original),
              },
              {
                icon: 'xmark',
                label: t('Revoke'),
                variant: 'danger',
                onClick: () => revoke(row.original),
              },
            ],
          }),
        ]),
      ];
    },
  });
</script>

<template>
  <UserScreen>
    <div class="grid gap-lg">
      <div>
        <p>
          {{
            t(
              'Tokens let apps and scripts access Craft’s APIs as you, limited to what you have access to.'
            )
          }}
        </p>

        <div role="status" :class="{'mt-lg': newToken}">
          <craft-callout v-if="newToken" variant="success">
            <div class="grid gap-md">
              <craft-input-copy .modelValue="newToken" monospace>
                <label slot="label">{{ t('New token') }}</label>
              </craft-input-copy>
              <p>
                {{
                  t('Copy this token now. You won’t be able to see it again.')
                }}
                {{
                  t(
                    'Send it with API requests in an Authorization: Bearer header.'
                  )
                }}
              </p>
            </div>
          </craft-callout>
        </div>
      </div>

      <craft-pane :padding="0" appearance="raised">
        <AdminTable :table="table">
          <template #empty-row>
            <craft-empty
              :label="t('You haven’t created or authorized any tokens yet.')"
            ></craft-empty>
          </template>
        </AdminTable>
      </craft-pane>

      <div>
        <craft-button type="button" icon="plus" @click="openNewTokenModal">
          {{ t('New token') }}
        </craft-button>
      </div>
    </div>

    <ModalForm
      :title="modalTitle"
      :is-active="modalActive"
      :loading="createRequest.processing || updateRequest.processing"
      :submit-label="editing ? t('Save') : t('Create')"
      :submit-disabled="editingApp"
      @submit="submitModal"
      @close="modalActive = false"
    >
      <craft-field-group>
        <CraftField
          v-if="!editingApp"
          :label="t('Name')"
          :help-text="t('What this token will be used for.')"
          :error="createRequest.errors.name"
          .hasErrors="!!createRequest.errors.name"
          required
        >
          <craft-input slot="input">
            <input
              id="token-name"
              slot="input"
              v-model="createRequest.name"
              type="text"
              maxlength="255"
              autofocus
            />
          </craft-input>
        </CraftField>

        <CraftField
          id="token-scopes"
          :label="
            modalGroups.length > 1
              ? t('Scopes')
              : t('{api} Scopes', {api: modalGroups[0]?.label ?? ''})
          "
          :help-text="
            editing?.personal
              ? t('Scopes can’t be changed after a token is created.')
              : undefined
          "
          :error="createRequest.errors.scopes"
          .hasErrors="!!createRequest.errors.scopes"
          fieldset
          :required="!editing"
        >
          <CraftCheckboxGroup
            slot="input"
            :key="editing ? grantKey(editing) : 'new'"
            v-model="createRequest.scopes"
          >
            <template v-for="(group, index) in modalGroups" :key="group.handle">
              <div v-if="modalGroups.length > 1" :class="{'mt-sm': index > 0}">
                {{ group.label }}
              </div>
              <craft-checkbox
                v-for="scope in group.scopes"
                :key="scope.value"
                .choiceValue="scope.value"
                :disabled="!!editing"
              >
                <label slot="label">{{ scope.label }}</label>
              </craft-checkbox>
            </template>
          </CraftCheckboxGroup>
        </CraftField>

        <CraftField v-if="editing" :label="t('Expires on')">
          <div slot="input">
            <CraftDate :value="editing.expiresAt" />
          </div>
        </CraftField>

        <Select
          v-else
          id="token-expires-in"
          :label="t('Expiration')"
          :model-value="createRequest.expiresIn"
          :options="expiryOptions"
          @update:model-value="createRequest.expiresIn = Number($event)"
        />
      </craft-field-group>
    </ModalForm>
  </UserScreen>
</template>
