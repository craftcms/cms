<script setup lang="ts">
    import {t} from '@craftcms/ui';
    import {useForm, usePage} from '@inertiajs/vue3';
    import CraftInputPassword from '@craftcms/ui/vue/CraftInputPassword.vue';
    import {useAppLayout} from '@/common/composables/useAppLayout';
    import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave';
    import TwoStepVerification from '@/modules/two-step-verification/TwoStepVerification.vue';
    import type {AuthMethod} from '@/modules/two-step-verification/types';
    import {store} from '@actions/Users/PasswordController';
    import UserScreen from '@/modules/user/components/UserScreen.vue';

    defineOptions({
        inheritAttrs: false,
    });

    const page = usePage<{
        authMethods: AuthMethod[];
    }>();

    interface PasswordForm {
        newPassword: string;
    }

    const form = useForm<PasswordForm>({
        newPassword: '',
    });

    const {save} = useSettingsSave(form, store, {
        passwordConfirmation: {
            // Changing a password always requires an elevated session (the save
            // action is behind `password.confirm`), but only when there's actually a
            // new password to save.
            required: (data) => Boolean(data.newPassword),
            // Re-request the full elevated-session window (capped at 5 minutes) so
            // there's time to finish before it lapses.
            minimumRemainingSeconds: 300,
        },
    });

    useAppLayout({form, onSave: save});
</script>

<template>
    <UserScreen>
        <craft-pane appearance="raised" :padding="0">
            <div class="grid gap-6 p-4 min-w-0">
                <section class="grid gap-3 min-w-0">
                    <h2 class="text-base">{{ t('Change your Password') }}</h2>

                    <craft-field-group>
                        <CraftInputPassword
                            v-model="form.newPassword"
                            :label="t('New Password')"
                            id="newPassword"
                            name="newPassword"
                            autocomplete="new-password"
                            :error="form.errors.newPassword"
                        />
                    </craft-field-group>
                </section>

                <hr />

                <section class="grid gap-3 min-w-0">
                    <div>
                        <h2 class="text-base">
                            {{ t('Two-Step Verification') }}
                        </h2>
                        <p>
                            {{
                                t(
                                    'Improve your account’s security by adding a second verification step when signing in.'
                                )
                            }}
                        </p>
                    </div>

                    <TwoStepVerification :methods="page.props.authMethods" />
                </section>
            </div>
        </craft-pane>
    </UserScreen>
</template>
