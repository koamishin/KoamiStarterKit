<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';

type SocialProvider = {
    slug: 'github' | 'google' | 'facebook';
    label: string;
    url: string;
};

const page = usePage<{ socialProviders?: SocialProvider[] }>();

const providers = computed<SocialProvider[]>(
    () => page.props.socialProviders ?? [],
);
</script>

<template>
    <div v-if="providers.length > 0" class="space-y-3">
        <div class="relative">
            <div class="absolute inset-0 flex items-center">
                <span
                    class="w-full border-t border-border"
                    aria-hidden="true"
                />
            </div>
            <div class="relative flex justify-center text-xs uppercase">
                <span class="bg-background px-2 text-muted-foreground">
                    Or continue with
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-2">
            <a
                v-for="provider in providers"
                :key="provider.slug"
                :href="provider.url"
                class="block"
                :data-test="`social-login-${provider.slug}`"
            >
                <Button type="button" variant="outline" class="w-full">
                    <template v-if="provider.slug === 'github'">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            class="mr-2 h-4 w-4"
                            aria-hidden="true"
                        >
                            <path
                                fill="currentColor"
                                d="M12 .5C5.65.5.5 5.65.5 12c0 5.08 3.29 9.39 7.86 10.91.58.11.79-.25.79-.56v-2.16c-3.2.7-3.87-1.36-3.87-1.36-.52-1.33-1.28-1.69-1.28-1.69-1.05-.72.08-.7.08-.7 1.16.08 1.77 1.19 1.77 1.19 1.03 1.77 2.71 1.26 3.37.96.1-.75.4-1.26.74-1.55-2.55-.29-5.24-1.28-5.24-5.69 0-1.26.45-2.29 1.18-3.1-.12-.29-.51-1.46.11-3.04 0 0 .97-.31 3.18 1.18a11.1 11.1 0 0 1 5.79 0c2.21-1.49 3.18-1.18 3.18-1.18.62 1.58.23 2.75.11 3.04.74.81 1.18 1.84 1.18 3.1 0 4.42-2.69 5.39-5.26 5.68.41.36.78 1.05.78 2.12v3.14c0 .31.21.68.8.56C20.21 21.39 23.5 17.08 23.5 12 23.5 5.65 18.35.5 12 .5Z"
                            />
                        </svg>
                    </template>

                    <template v-else-if="provider.slug === 'google'">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            class="mr-2 h-4 w-4"
                            aria-hidden="true"
                        >
                            <path
                                fill="#4285F4"
                                d="M23.49 12.27c0-.78-.07-1.54-.2-2.27H12v4.29h6.47c-.28 1.4-1.07 2.59-2.27 3.4v2.81h3.66c2.15-1.98 3.36-4.89 3.36-8.23Z"
                            />
                            <path
                                fill="#34A853"
                                d="M12 24c3.07 0 5.64-1.02 7.52-2.76l-3.66-2.81c-1.02.68-2.32 1.09-3.86 1.09-2.97 0-5.48-2-6.38-4.69H1.85v2.95A11.99 11.99 0 0 0 12 24Z"
                            />
                            <path
                                fill="#FBBC05"
                                d="M5.62 14.83c-.23-.68-.36-1.41-.36-2.16s.13-1.48.36-2.16V7.56H1.85A11.99 11.99 0 0 0 .75 12c0 1.94.46 3.77 1.1 5.44l3.77-2.61Z"
                            />
                            <path
                                fill="#EA4335"
                                d="M12 4.77c1.67 0 3.17.57 4.36 1.7l3.25-3.25C17.64 1.16 15.07 0 12 0 7.31 0 3.26 2.69 1.85 6.6l3.77 2.95C6.52 6.8 9.03 4.77 12 4.77Z"
                            />
                        </svg>
                    </template>

                    <template v-else-if="provider.slug === 'facebook'">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            class="mr-2 h-4 w-4"
                            aria-hidden="true"
                        >
                            <path
                                fill="#1877F2"
                                d="M24 12c0-6.63-5.37-12-12-12S0 5.37 0 12c0 5.99 4.39 10.95 10.13 11.85v-8.38H7.08V12h3.05V9.36c0-3.01 1.79-4.67 4.53-4.67 1.31 0 2.69.23 2.69.23v2.95h-1.51c-1.49 0-1.95.93-1.95 1.87V12h3.32l-.53 3.47h-2.79v8.38C19.61 22.95 24 17.99 24 12Z"
                            />
                        </svg>
                    </template>

                    Continue with {{ provider.label }}
                </Button>
            </a>
        </div>
    </div>
</template>
