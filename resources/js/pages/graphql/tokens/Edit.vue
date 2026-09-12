<script setup lang="ts">
    import {computed, nextTick, ref} from 'vue';
    import {t} from '@craftcms/ui';
    import LayoutSlot from '@/common/components/LayoutSlot.vue';
    import Select from '@/common/form/Select.vue';
    import type {BaseOption} from '@/common/types';
    import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
    import CraftSwitch from '@craftcms/ui/vue/CraftSwitch.vue';
    import {useAppLayout} from '@/common/composables/useAppLayout';
    import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave';
    import {
        accessToken as revealAccessToken,
        generate,
        store,
        update,
    } from '@actions/Gql/TokensController';
    import {useForm, useHttp} from '@inertiajs/vue3';
    import {elevatedSessionManager} from '@/modules/auth/elevated-session';

    type TokenData = Pick<
        CraftCms.Cms.Gql.Data.GqlToken,
        'id' | 'uid' | 'name' | 'schemaId' | 'enabled' | 'expiryDate'
    >;

    type CopyButtonElement = HTMLElement & {copyValue: () => Promise<void>};

    type TokenResponse = {
        accessToken: string;
    };

    const maskedToken = '••••••••••••••••••••••••••••••••';

    const props = defineProps<{
        token: TokenData;
        accessToken: string | null;
        schemaOptions: Array<BaseOption>;
        readOnly?: boolean;
    }>();

    const copyButton = ref<CopyButtonElement | null>(null);
    const visibleAccessToken = ref(props.accessToken);
    const tokenRequest = useHttp<Record<string, never>, TokenResponse>({});
    const loadingToken = computed(() => tokenRequest.processing);

    const form = useForm({
        name: props.token.name ?? '',
        schema: props.token.schemaId?.toString() ?? '',
        enabled: props.token.enabled,
        expiryDate: props.token.expiryDate ?? '',
        accessToken: props.accessToken ?? '',
    });

    const authorizationHeader = computed(
        () => `Authorization: Bearer ${visibleAccessToken.value ?? maskedToken}`
    );

    const routeAction = () =>
        props.token.id ? update({tokenId: props.token.id}) : store();

    const {save} = useSettingsSave(form, routeAction, {
        passwordConfirmation: {required: () => true},
    });

    useAppLayout({form, onSave: save});

    async function revealToken(tokenId: number) {
        const data = await tokenRequest.post(revealAccessToken({tokenId}).url);
        visibleAccessToken.value = data.accessToken;

        return data.accessToken;
    }

    async function copyHeader() {
        const tokenId = props.token.id;

        if (!visibleAccessToken.value && tokenId) {
            await elevatedSessionManager.run(async () => {
                await revealToken(tokenId);
                await nextTick();
                await copyButton.value?.copyValue();
            });
        }
    }

    async function regenerateToken() {
        const data = await tokenRequest.post(generate().url);
        visibleAccessToken.value = data.accessToken;
        form.accessToken = data.accessToken;
    }
</script>

<template>
    <craft-pane appearance="raised">
        <div class="grid gap-3">
            <CraftInput
                :label="t('Name')"
                :help-text="
                    t('What this token will be called in the control panel.')
                "
                id="name"
                name="name"
                v-model="form.name"
                :error="form.errors.name"
                :disabled="readOnly"
                required
                autofocus
            />

            <template v-if="schemaOptions.length">
                <Select
                    :label="t('GraphQL Schema')"
                    :help-text="
                        t(
                            'Choose which GraphQL schema this token has access to.'
                        )
                    "
                    id="schema"
                    name="schema"
                    v-model="form.schema"
                    :options="schemaOptions"
                    :error="form.errors.schema"
                    :disabled="readOnly"
                />
            </template>

            <craft-callout v-else data-color="warning">
                {{ t('No schemas exist yet to assign to this token.') }}
            </craft-callout>

            <hr />

            <div class="flex items-end gap-1">
                <CraftInput
                    :label="t('Authorization Header')"
                    :help-text="
                        t(
                            'The `Authorization` header that should be sent with GraphQL API requests to use this token.'
                        )
                    "
                    id="auth-header"
                    class="code ltr flex-1"
                    :model-value="authorizationHeader"
                    readonly
                    :error="form.errors.accessToken"
                >
                    <craft-button
                        v-if="!visibleAccessToken"
                        slot="suffix"
                        type="button"
                        size="small"
                        variant="plain"
                        :disabled="loadingToken"
                        @click="copyHeader"
                    >
                        <craft-icon name="copy" slot="prefix"></craft-icon>
                        {{ t('Copy') }}
                    </craft-button>

                    <craft-copy-button
                        v-else
                        slot="suffix"
                        ref="copyButton"
                        :value="authorizationHeader"
                        :disabled="loadingToken"
                    />
                </CraftInput>

                <craft-button
                    type="button"
                    variant="neutral"
                    :loading="loadingToken"
                    :disabled="readOnly || loadingToken"
                    @click="regenerateToken"
                >
                    {{ t('Regenerate') }}
                </craft-button>
            </div>
        </div>
    </craft-pane>

    <LayoutSlot name="details">
        <CraftSwitch
            :label="t('Enabled')"
            id="enabled"
            name="enabled"
            v-model="form.enabled"
            :disabled="readOnly"
            :error="form.errors.enabled"
        />

        <CraftInput
            :label="t('Expiry Date')"
            id="expiryDate"
            name="expiryDate"
            type="datetime-local"
            v-model="form.expiryDate"
            :disabled="readOnly"
            :error="form.errors.expiryDate"
        />
    </LayoutSlot>
</template>
