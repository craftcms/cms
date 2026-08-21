<script setup lang="ts">
    import {h, ref} from 'vue';
    import {router, useHttp} from '@inertiajs/vue3';
    import {t} from '@craftcms/ui';
    import {getCoreRowModel, useVueTable} from '@tanstack/vue-table';
    import Badge from '@/common/components/Badge.vue';
    import ActionMenu from '@/common/components/ActionMenu.vue';
    import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
    import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
    import {elevatedSessionManager} from '@/modules/auth/elevated-session';
    import {destroy as removeMethodAction} from '@actions/Users/AuthMethodController';
    import type {ActionItems} from '@/common/types';
    import type {AuthMethod, AuthMethodAction} from './types';
    import '@/modules/auth-method-setup/recovery-codes-setup';

    const props = defineProps<{
        methods: AuthMethod[];
    }>();

    const craft = (window as any).Craft;

    // Re-request the full elevated-session window (capped at 5 minutes) so there's
    // time to complete a setup flow before it lapses.
    const ELEVATED_SETUP_SECONDS = 300;

    const processing = ref<string | null>(null);

    const removeRequest = useHttp<{method: string}, unknown>({method: ''});

    function refresh() {
        router.reload({only: ['authMethods']});
    }

    async function startSetup(method: AuthMethod) {
        if (processing.value) {
            return;
        }

        processing.value = method.type;

        try {
            const confirmed = await elevatedSessionManager.require({
                minimumRemainingSeconds: ELEVATED_SETUP_SECONDS,
            });

            if (!confirmed) {
                return;
            }

            await openSetup(method);
        } finally {
            processing.value = null;
        }
    }

    async function removeMethod(method: AuthMethod) {
        if (
            !confirm(
                t('Are you sure you want to remove {name} verification?', {
                    name: method.name,
                })
            )
        ) {
            return;
        }

        removeRequest.method = method.type;

        const response = await elevatedSessionManager.run(() =>
            removeRequest.post(removeMethodAction().url)
        );

        if (response !== undefined) {
            refresh();
        }
    }

    function runAction(action: AuthMethodAction) {
        if (!action.action) {
            return;
        }

        const run = () => {
            if (action.download) {
                return craft
                    .downloadFromUrl(
                        'post',
                        craft.getActionUrl(action.action),
                        {
                            [craft.csrfTokenName]: craft.csrfTokenValue,
                        }
                    )
                    .catch((e: any) =>
                        craft.cp?.displayError?.(e?.response?.data?.message)
                    );
            }

            // Generic POST action (plugin-provided).
            return craft.sendActionRequest('post', action.action).then(refresh);
        };

        if (action.requireElevatedSession) {
            void elevatedSessionManager.run(run);
        } else {
            void run();
        }
    }

    // Setup for every method is rendered by the server (`getSetupHtml()`) and
    // hosted in the legacy setup slideout. Its `refresh()` hook is shimmed onto
    // our Inertia partial reload so the listing updates once a method is added.
    async function openSetup(method: AuthMethod) {
        craft.authMethodSetup = {refresh};

        const {data} = await craft.sendActionRequest(
            'post',
            'auth/method-setup-html',
            {data: {method: method.type}}
        );

        const slideout = new craft.AuthMethodSetup.Slideout(data);
        await craft.appendHeadHtml(data.headHtml);
        await craft.appendBodyHtml(data.bodyHtml);
        slideout.$container
            .find('.auth-method-close-btn')
            .on('click', () => slideout.close());
    }

    const columnHelper = createCraftColumnHelper<AuthMethod>();
    const table = useVueTable<AuthMethod>({
        get data() {
            return props.methods;
        },
        get columns() {
            return [
                columnHelper.display({
                    id: 'method',
                    header: t('Method'),
                    cell: ({row}) =>
                        h('div', {class: 'grid py-2'}, [
                            h('div', {class: 'font-bold'}, row.original.name),
                            h(
                                'div',
                                {class: 'text-sm', style: 'opacity: 0.75'},
                                row.original.description
                            ),
                        ]),
                }),
                columnHelper.display({
                    id: 'status',
                    header: t('Status'),
                    cell: ({row}) =>
                        h(
                            Badge,
                            {
                                variant: row.original.isActive
                                    ? 'success'
                                    : 'default',
                            },
                            () =>
                                row.original.isActive
                                    ? t('Active')
                                    : t('Not active')
                        ),
                }),
                columnHelper.actions(({row}) => {
                    const method = row.original;

                    if (!method.isActive) {
                        return [
                            h(
                                'craft-button',
                                {
                                    type: 'button',
                                    size: 'small',
                                    'aria-label': t('Set up {name}', {
                                        name: method.name,
                                    }),
                                    loading: processing.value === method.type,
                                    onclick: () => startSetup(method),
                                },
                                t('Set up')
                            ),
                        ];
                    }

                    const items: ActionItems = [
                        ...method.actions.map(
                            (action): ActionItems[number] => ({
                                type: 'button',
                                label: action.label,
                                icon: action.icon ?? undefined,
                                onClick: () => runAction(action),
                            })
                        ),
                        ...(method.actions.length
                            ? [{type: 'hr'} as ActionItems[number]]
                            : []),
                        {
                            type: 'button',
                            label: t('Remove'),
                            icon: 'remove',
                            variant: 'danger',
                            onClick: () => removeMethod(method),
                        },
                    ];

                    return [h(ActionMenu, {actions: items})];
                }),
            ];
        },
        getCoreRowModel: getCoreRowModel<AuthMethod>(),
        enableSorting: false,
    });
</script>

<template>
    <craft-pane :padding="0" appearance="raised">
        <AdminTable :table="table" spacing="spacious" />
    </craft-pane>
</template>
