<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import ThemeSwitcher from '@/components/ThemeSwitcher.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/appearance';
import { type BreadcrumbItem, type SharedData } from '@/types';

const page = usePage<SharedData>();
const settingsFeatures = page.props.settingsFeatures || {};

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Appearance settings',
        href: edit().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Appearance settings" />

        <h1 class="sr-only">Appearance Settings</h1>

        <SettingsLayout>
            <div v-if="!settingsFeatures.appearance" class="py-12 text-center">
                <p class="text-muted-foreground">This page is not available.</p>
            </div>

            <template v-else>
                <div class="space-y-6">
                    <Heading
                        variant="small"
                        title="Appearance settings"
                        description="Update your account's appearance settings"
                    />
                    <AppearanceTabs />
                </div>

                <div class="space-y-6">
                    <Heading
                        variant="small"
                        title="Personalization"
                        description="Choose a color theme that matches your style"
                    />
                    <ThemeSwitcher />
                </div>
            </template>
        </SettingsLayout>
    </AppLayout>
</template>
