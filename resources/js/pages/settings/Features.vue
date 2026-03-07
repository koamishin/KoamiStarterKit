<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { edit, update } from '@/routes/features';

const props = defineProps<{
    features: Array<{
        key: string;
        name: string;
        description: string;
        value: boolean;
    }>;
}>();

const breadcrumbs = [{ title: 'Feature settings', href: edit().url }];

const form = useForm(
    Object.fromEntries(props.features.map((f) => [f.key, f.value])),
);

const toggleFeature = (key: string, active: boolean) => {
    form.patch(
        update().url,
        {
            feature: key,
            active: active,
        },
        {
            preserveScroll: true,
            onSuccess: () =>
                toast.success(
                    `${props.features.find((f) => f.key === key)?.name || key} ${active ? 'enabled' : 'disabled'}`,
                ),
            onError: () => toast.error('Failed to update feature setting'),
        },
    );
};
</script>

<template>
    <AppLayout title="Feature settings" :breadcrumbs="breadcrumbs">
        <Head title="Feature settings" />

        <SettingsLayout>
            <div class="space-y-6">
                <Heading
                    title="Feature Flags"
                    description="Manage your feature preferences and experimental features"
                />

                <div class="grid gap-4 md:grid-cols-2">
                    <div
                        v-for="feature in features"
                        :key="feature.key"
                        class="rounded-lg border bg-card p-4 transition-all duration-200"
                        :class="[
                            feature.value
                                ? 'border-green-500/30 dark:border-green-500/20'
                                : 'border-border',
                        ]"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="space-y-1">
                                <p class="leading-none font-medium">
                                    {{ feature.name }}
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    {{ feature.description }}
                                </p>
                                <div class="flex items-center gap-2 pt-2">
                                    <span
                                        class="flex h-2 w-2 rounded-full"
                                        :class="[
                                            feature.value
                                                ? 'bg-green-500'
                                                : 'bg-muted-foreground/30',
                                        ]"
                                    />
                                    <span
                                        class="text-xs font-medium"
                                        :class="[
                                            feature.value
                                                ? 'text-green-600 dark:text-green-400'
                                                : 'text-muted-foreground',
                                        ]"
                                    >
                                        {{
                                            feature.value
                                                ? 'Active'
                                                : 'Inactive'
                                        }}
                                    </span>
                                </div>
                            </div>
                            <Switch
                                :modelValue="feature.value"
                                @update:modelValue="
                                    (active: boolean) =>
                                        toggleFeature(feature.key, active)
                                "
                                class="mt-1"
                            />
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border bg-muted/50 p-4">
                    <div class="flex items-start gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5 text-primary"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm font-medium">
                                About Feature Flags
                            </p>
                            <p class="text-sm text-muted-foreground">
                                Feature flags allow you to control which
                                features are enabled for your account. Toggle
                                features on or off to customize your experience.
                                Some features may require a page refresh to take
                                effect.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
