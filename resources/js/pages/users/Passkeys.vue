<script setup lang="ts">
  import {h, onMounted, ref} from 'vue';
  import {router, useHttp, usePage} from '@inertiajs/vue3';
  import {t} from '@craftcms/ui';
  import {
    browserSupportsWebAuthn,
    platformAuthenticatorIsAvailable,
    startRegistration,
  } from '@simplewebauthn/browser';
  import {getCoreRowModel, useVueTable} from '@tanstack/vue-table';
  import CraftDate from '@/common/components/Date.vue';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
  import {elevatedSessionManager} from '@/modules/auth/elevated-session';
  import UserScreen from '@/modules/user/components/UserScreen.vue';
  import {
    creationOptions,
    verifyCreation,
    deleteMethod as deletePasskeyAction,
  } from '@actions/Users/PasskeysController';

  defineOptions({
    inheritAttrs: false,
  });

  const page = usePage<CraftCms.Cms.Http.ViewModels.UserPasskeysViewModel>();

  type Passkey = (typeof page.props.passkeys)[number];

  // Re-request the full elevated-session window (capped at 5 minutes) so it
  // survives the browser's WebAuthn prompt during registration.
  const ELEVATED_SETUP_SECONDS = 300;

  const craft = window.Craft;

  const supported = ref(true);
  const adding = ref(false);
  const processingUid = ref<string | null>(null);

  const optionsRequest = useHttp<Record<string, never>, {options: string}>({});
  const verifyRequest = useHttp<
    {credentials: string; credentialName: string | null},
    unknown
  >({credentials: '', credentialName: null});
  const deleteRequest = useHttp<{uid: string}, unknown>({uid: ''});

  onMounted(async () => {
    supported.value =
      browserSupportsWebAuthn() && (await platformAuthenticatorIsAvailable());
  });

  function refresh() {
    router.reload({only: ['passkeys']});
  }

  // Suggest a sensible default passkey name, e.g. "Chrome on Mac".
  function browserName(): string {
    const ua = navigator.userAgent;
    if (/edg/i.test(ua)) return 'Edge';
    if (/opr\//i.test(ua)) return 'Opera';
    if (/chrome|chromium|crios/i.test(ua)) return 'Chrome';
    if (/firefox|fxios/i.test(ua)) return 'Firefox';
    if (/safari/i.test(ua)) return 'Safari';
    return 'Browser';
  }

  function platformName(): string {
    const platform = navigator.platform;
    const known = ['Mac', 'iPhone', 'iPad', 'iPod', 'Linux', 'Win'];
    const match = known.find((name) => platform.includes(name));
    if (match === 'Win') return 'Windows';
    return match ?? platform;
  }

  async function addPasskey() {
    if (adding.value) {
      return;
    }

    adding.value = true;

    try {
      const confirmed = await elevatedSessionManager.require({
        minimumRemainingSeconds: ELEVATED_SETUP_SECONDS,
      });

      if (!confirmed) {
        return;
      }

      const optionsResponse = await optionsRequest.post(creationOptions().url);

      if (!optionsResponse) {
        return;
      }

      const credentialName = window.prompt(
        t('Enter a name for the passkey.'),
        `${browserName()} on ${platformName()}`
      );

      if (credentialName === null) {
        return;
      }

      let registration;

      try {
        registration = await startRegistration({
          optionsJSON: JSON.parse(optionsResponse.options),
        });
      } catch (e: any) {
        craft?.cp?.displayError?.(e?.message);
        return;
      }

      verifyRequest.credentials = JSON.stringify(registration);
      verifyRequest.credentialName = credentialName;

      const verified = await verifyRequest.post(verifyCreation().url);

      if (verified) {
        refresh();
      }
    } finally {
      adding.value = false;
    }
  }

  async function removePasskey(passkey: Passkey) {
    if (
      !confirm(
        t('Are you sure you want to delete the “{name}” passkey?', {
          name: passkey.name,
        })
      )
    ) {
      return;
    }

    processingUid.value = passkey.uid;

    try {
      deleteRequest.uid = passkey.uid;

      const response = await deleteRequest.post(deletePasskeyAction().url);

      if (response) {
        refresh();
      }
    } finally {
      processingUid.value = null;
    }
  }

  const columnHelper = createCraftColumnHelper<Passkey>();
  const table = useVueTable<Passkey>({
    get data() {
      return page.props.passkeys;
    },
    get columns() {
      return [
        columnHelper.display({
          id: 'name',
          header: t('Name'),
          cell: ({row}) => h('span', {class: 'font-bold'}, row.original.name),
        }),
        columnHelper.display({
          id: 'dateLastUsed',
          header: t('Last Used'),
          cell: ({row}) =>
            row.original.dateLastUsed
              ? h(CraftDate, {value: row.original.dateLastUsed})
              : t('Never'),
        }),
        columnHelper.actions(({row}) => [
          h(
            'craft-button',
            {
              type: 'button',
              size: 'small',
              icon: 'trash',
              'aria-label': t('Delete {name}', {name: row.original.name}),
              loading: processingUid.value === row.original.uid,
              onclick: () => removePasskey(row.original),
            },
            t('Delete')
          ),
        ]),
      ];
    },
    getCoreRowModel: getCoreRowModel<Passkey>(),
    enableSorting: false,
  });
</script>

<template>
  <UserScreen>
    <craft-pane>
      <div class="grid gap-4">
        <div>
          <h2>{{ t('Passkeys') }}</h2>
          <p>
            {{
              t(
                'Passkeys are an easy and secure way to identify yourself, using your fingerprint or facial recognition.'
              )
            }}
          </p>
        </div>

        <craft-callout v-if="!supported" variant="warning">
          {{ t('This browser doesn’t support passkeys.') }}
        </craft-callout>

        <craft-pane :padding="0" appearance="raised">
          <AdminTable :table="table" />
        </craft-pane>

        <div v-if="supported">
          <craft-button
            type="button"
            icon="plus"
            :loading="adding"
            @click="addPasskey"
          >
            {{ t('Add a passkey') }}
          </craft-button>
        </div>
      </div>
    </craft-pane>
  </UserScreen>
</template>
