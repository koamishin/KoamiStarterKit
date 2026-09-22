<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { ref, toRefs } from 'vue';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import FilamentMfaAppAuthentication from '@/components/FilamentMfaAppAuthentication.vue';
import FilamentMfaEmailAuthentication from '@/components/FilamentMfaEmailAuthentication.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
/* @chisel-2fa */
import ManageTwoFactor from '@/components/ManageTwoFactor.vue';
/* @end-chisel-2fa */
/* @chisel-passkeys */
import PasskeyManager from '@/components/PasskeyManager.vue';
/* @end-chisel-passkeys */
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/security';
import { type BreadcrumbItem, type SharedData } from '@/types';

const page = usePage<SharedData>();
const settingsFeatures = page.props.settingsFeatures || {};

const props = defineProps<{
    mustVerifyEmail: boolean;
    emailVerified: boolean;
    filamentMfa: {
        providers: {
            app: boolean;
            email: boolean;
        };
        state: {
            app: boolean;
            email: boolean;
        };
        options: {
            appRecoveryCodes: boolean;
        };
    };
    canManageTwoFactor?: boolean;
    twoFactorEnabled?: boolean;
    requiresConfirmation?: boolean;
    canManagePasskeys?: boolean;
    passkeys?: Array<{
        id: string;
        name: string;
        authenticator: string | null;
        last_used_at: string | null;
        created_at: string | null;
    }>;
    passwordRules?: string;
}>();

const { filamentMfa } = toRefs(props);

const appMfaEnabled = ref(filamentMfa.value.state.app);
const emailMfaEnabled = ref(filamentMfa.value.state.email);
const fortifyTwoFactorEnabled = ref(props.twoFactorEnabled ?? false);

const showPasswordForm = settingsFeatures.password !== false;

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Security settings',
        href: edit().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Security settings" />

        <h1 class="sr-only">Security Settings</h1>

        <SettingsLayout>
            <div v-if="!settingsFeatures.security" class="py-12 text-center">
                <p class="text-muted-foreground">This page is not available.</p>
            </div>

            <div v-else class="space-y-8">
                <Heading
                    variant="small"
                    title="Security settings"
                    description="Manage your password, multi-factor authentication, and passkeys"
                />

                <section v-if="showPasswordForm" class="space-y-4">
                    <Heading
                        variant="small"
                        title="Update password"
                        description="Ensure your account is using a long, random password to stay secure"
                    />

                    <Form
                        v-bind="SecurityController.update.form()"
                        :options="{
                            preserveScroll: true,
                        }"
                        reset-on-success
                        :reset-on-error="[
                            'password',
                            'password_confirmation',
                            'current_password',
                        ]"
                        class="space-y-6"
                        v-slot="{ errors, processing, recentlySuccessful }"
                    >
                        <div class="grid gap-2">
                            <Label for="current_password"
                                >Current password</Label
                            >
                            <Input
                                id="current_password"
                                name="current_password"
                                type="password"
                                class="mt-1 block w-full"
                                autocomplete="current-password"
                                placeholder="Current password"
                            />
                            <InputError :message="errors.current_password" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="password">New password</Label>
                            <Input
                                id="password"
                                name="password"
                                type="password"
                                class="mt-1 block w-full"
                                autocomplete="new-password"
                                placeholder="New password"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="password_confirmation"
                                >Confirm password</Label
                            >
                            <Input
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                class="mt-1 block w-full"
                                autocomplete="new-password"
                                placeholder="Confirm password"
                            />
                            <InputError
                                :message="errors.password_confirmation"
                            />
                        </div>

                        <div class="flex items-center gap-4">
                            <Button
                                :disabled="processing"
                                data-test="update-password-button"
                            >
                                Save password
                            </Button>

                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p
                                    v-show="recentlySuccessful"
                                    class="text-sm text-neutral-600"
                                >
                                    Saved.
                                </p>
                            </Transition>
                        </div>
                    </Form>
                </section>

                <Separator v-if="showPasswordForm" />

                <!-- @chisel-2fa-or-passkeys -->
                <!-- @chisel-2fa -->
                <section class="space-y-4">
                    <Heading
                        variant="small"
                        title="Multi-factor authentication"
                        description="Add an extra layer of security to your account"
                    />

                    <ManageTwoFactor
                        v-if="canManageTwoFactor"
                        :enabled="fortifyTwoFactorEnabled"
                        :requires-confirmation="requiresConfirmation ?? false"
                        @update:enabled="fortifyTwoFactorEnabled = $event"
                    />

                    <div
                        v-if="
                            filamentMfa.providers.app ||
                            filamentMfa.providers.email
                        "
                        class="space-y-4"
                    >
                        <FilamentMfaAppAuthentication
                            v-if="filamentMfa.providers.app"
                            :enabled="appMfaEnabled"
                            :recoverable="filamentMfa.options.appRecoveryCodes"
                            @update:enabled="appMfaEnabled = $event"
                        />

                        <FilamentMfaEmailAuthentication
                            v-if="filamentMfa.providers.email"
                            :enabled="emailMfaEnabled"
                            @update:enabled="emailMfaEnabled = $event"
                        />
                    </div>

                    <div
                        v-if="
                            !canManageTwoFactor &&
                            !filamentMfa.providers.app &&
                            !filamentMfa.providers.email
                        "
                        class="rounded-lg border border-muted p-4"
                    >
                        <p class="text-sm text-muted-foreground">
                            No multi-factor authentication options are
                            available.
                        </p>
                    </div>
                </section>
                <!-- @end-chisel-2fa -->

                <Separator />

                <!-- @chisel-passkeys -->
                <section
                    v-if="canManagePasskeys && settingsFeatures.passkeys"
                    class="space-y-4"
                >
                    <Heading
                        variant="small"
                        title="Passkeys"
                        description="Sign in faster and more securely with a passkey"
                    />

                    <PasskeyManager :passkeys="passkeys ?? []" />
                </section>
                <!-- @end-chisel-passkeys -->
                <!-- @end-chisel-2fa-or-passkeys -->
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
