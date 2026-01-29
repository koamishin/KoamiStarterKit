<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const isImpersonating = computed(() => page.props.auth.impersonating);
const user = computed(() => page.props.auth.user);
</script>

<template>
    <div
        v-if="isImpersonating"
        class="relative isolate flex items-center gap-x-6 overflow-hidden border-b border-amber-200 bg-amber-50 px-6 py-2.5 sm:px-3.5 sm:before:flex-1 dark:border-amber-900/50 dark:bg-amber-950/20"
    >
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
            <p class="text-sm leading-6 text-amber-900 dark:text-amber-200">
                <strong class="font-semibold">Impersonating Mode</strong>
                <svg
                    viewBox="0 0 2 2"
                    class="mx-2 inline h-0.5 w-0.5 fill-current"
                    aria-hidden="true"
                >
                    <circle cx="1" cy="1" r="1" />
                </svg>
                You are currently logged in as
                <span class="font-bold underline">{{ user?.name }}</span> ({{
                    user?.email
                }}).
            </p>
            <a
                :href="route('impersonate.leave')"
                class="flex-none rounded-full bg-amber-900 px-3.5 py-1 text-sm font-semibold text-white shadow-sm hover:bg-amber-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-900 dark:bg-amber-700 dark:hover:bg-amber-600"
            >
                Leave Impersonation <span aria-hidden="true">&rarr;</span>
            </a>
        </div>
        <div class="flex flex-1 justify-end"></div>
    </div>
</template>
