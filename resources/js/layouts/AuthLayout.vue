<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AuthSimpleLayout from '@/layouts/auth/AuthSimpleLayout.vue';
import AuthCardLayout from '@/layouts/auth/AuthCardLayout.vue';
import AuthSplitLayout from '@/layouts/auth/AuthSplitLayout.vue';

defineProps<{
    title?: string;
    description?: string;
}>();

const page = usePage();

const layoutComponent = computed(() => {
    const layout = page.props.authLayout ?? 'simple';

    switch (layout) {
        case 'card':
            return AuthCardLayout;
        case 'split':
            return AuthSplitLayout;
        default:
            return AuthSimpleLayout;
    }
});
</script>

<template>
    <component :is="layoutComponent" :title="title" :description="description">
        <slot />
    </component>
</template>
