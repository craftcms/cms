<script setup lang="ts">
  import {computed, h, nextTick, ref} from 'vue';
  import {router, useHttp, usePage} from '@inertiajs/vue3';
  import {t} from '@craftcms/ui';
  import CraftCheckboxGroup from '@craftcms/ui/vue/CraftCheckboxGroup.vue';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
  import {useCraftTable} from '@/common/table/craftTable';
  import CraftDate from '@/common/components/Date.vue';
  import ModalForm from '@/common/components/ModalForm.vue';
  import Select from '@/common/form/Select.vue';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import {createCraftColumnHelper} from '@/common/table/createCraftColumnHelper';
  import {elevatedSessionManager} from '@/modules/auth/elevated-session';
  import UserScreen from '@/modules/user/components/UserScreen.vue';
  import {
    destroy as revokeAction,
    store as createAction,
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
  const newToken = ref<string | null>(null);
  const revoking = ref<string | null>(null);

  const createRequest = useHttp<
    {name: string; group: string; scopes: string[]; expiresIn: number},
    {token: string}
  >({
    name: '',
    group: '',
    scopes: [],
    expiresIn: 30,
  });
  const revokeRequest = useHttp<
    {group: string; clientId: string; tokenId: string | null},
    unknown
  >({group: '', clientId: '', tokenId: null});

  const groups = computed(() => page.props.groups);
  const selectedGroup = computed(
    () =>
      groups.value.find((group) => group.handle === createRequest.group) ??
      groups.value[0]
  );
  const groupOptions = computed(() =>
    groups.value.map((group) => ({label: group.label, value: group.handle}))
  );
  const expiryOptions = [
    {label: t('7 days'), value: '7'},
    {label: t('30 days'), value: '30'},
    {label: t('90 days'), value: '90'},
    {label: t('1 year'), value: '365'},
  ];

  function grantKey(grant: Grant): string {
    return `${grant.group}:${grant.clientId}:${grant.tokenId ?? ''}`;
  }

  function groupLabel(handle: string): string {
    return groups.value.find((group) => group.handle === handle)?.label ?? '';
  }

  async function openNewTokenModal() {
    const confirmed = await elevatedSessionManager.require({
      minimumRemainingSeconds: ELEVATED_SETUP_SECONDS,
    });

    if (!confirmed) {
      return;
    }

    createRequest.reset();
    createRequest.clearErrors();
    createRequest.group = groups.value[0]?.handle ?? '';
    modalActive.value = true;
  }

  function setGroup(handle: string | number) {
    createRequest.group = String(handle);
    createRequest.scopes = [];
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

    revoking.value = grantKey(grant);

    try {
      revokeRequest.group = grant.group;
      revokeRequest.clientId = grant.clientId;
      revokeRequest.tokenId = grant.tokenId;

      if (await revokeRequest.post(revokeAction().url)) {
        router.reload({only: ['grants']});
      }
    } finally {
      revoking.value = null;
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
          cell: ({row}) =>
            h('div', [
              h('span', {class: 'font-bold'}, row.original.name),
              h(
                'div',
                {class: 'text-sm text-quiet'},
                row.original.personal
                  ? t('Personal token')
                  : t('Authorized app')
              ),
            ]),
        }),
        columnHelper.display({
          id: 'access',
          header: t('Access'),
          cell: ({row}) =>
            (groups.value.length > 1
              ? `${groupLabel(row.original.group)}: `
              : '') + row.original.scopes.join(', '),
        }),
        columnHelper.display({
          id: 'expiresAt',
          header: t('Expires'),
          cell: ({row}) => h(CraftDate, {value: row.original.expiresAt}),
        }),
        columnHelper.actions(({row}) => [
          h(
            'craft-button',
            {
              type: 'button',
              size: 'small',
              'aria-label': t('Revoke {name}', {name: row.original.name}),
              loading: revoking.value === grantKey(row.original),
              onclick: () => revoke(row.original),
            },
            t('Revoke')
          ),
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
      :title="t('New token')"
      :is-active="modalActive"
      :loading="createRequest.processing"
      :submit-label="t('Create')"
      @submit="createToken"
      @close="modalActive = false"
    >
      <craft-field-group>
        <CraftInput
          id="token-name"
          v-model="createRequest.name"
          :label="t('Name')"
          :help-text="t('What this token will be used for.')"
          :error="createRequest.errors.name"
          maxlength="255"
          required
          autofocus
        />

        <Select
          v-if="groups.length > 1"
          id="token-group"
          :label="t('API')"
          :model-value="createRequest.group"
          :options="groupOptions"
          @update:model-value="setGroup"
        />

        <CraftCheckboxGroup
          v-if="selectedGroup"
          id="token-scopes"
          :key="selectedGroup.handle"
          v-model="createRequest.scopes"
          :error="createRequest.errors.scopes"
        >
          <label slot="label">
            {{
              groups.length > 1
                ? t('Scopes')
                : t('{api} Scopes', {api: selectedGroup.label})
            }}
          </label>
          <div slot="help-text">{{ t('Choose at least one.') }}</div>
          <craft-checkbox
            v-for="scope in selectedGroup.scopes"
            :key="scope.value"
            .choiceValue="scope.value"
          >
            <label slot="label">{{ scope.label }}</label>
          </craft-checkbox>
        </CraftCheckboxGroup>

        <Select
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
