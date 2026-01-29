<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppHeader from '@/components/AppHeader.vue';
import AppShell from '@/components/AppShell.vue';
import ImpersonateBanner from '@/components/ImpersonateBanner.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell class="flex-col">
        <ImpersonateBanner />
        <AppHeader :breadcrumbs="breadcrumbs" />
        <AppContent>
            <slot />
        </AppContent>
    </AppShell>
</template>
