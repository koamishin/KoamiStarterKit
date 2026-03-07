<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { ref, toRefs } from 'vue';
import FilamentMfaAppAuthentication from '@/components/FilamentMfaAppAuthentication.vue';
import FilamentMfaEmailAuthentication from '@/components/FilamentMfaEmailAuthentication.vue';
import Heading from '@/components/Heading.vue';
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
}>();

const { filamentMfa } = toRefs(props);

const appMfaEnabled = ref(filamentMfa.value.state.app);
const emailMfaEnabled = ref(filamentMfa.value.state.email);

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

            <div v-else class="space-y-6">
                <Heading
                    variant="small"
                    title="Security settings"
                    description="Manage multi-factor authentication and other security options"
                />

                <div
                    v-if="
                        filamentMfa.providers.app || filamentMfa.providers.email
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

                <div v-else class="rounded-lg border border-muted p-4">
                    <p class="text-sm text-muted-foreground">
                        No multi-factor authentication options are available.
                    </p>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
