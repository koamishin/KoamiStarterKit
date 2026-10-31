<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ArrowRight, Code2 } from '@lucide/vue';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Spinner } from '@/components/ui/spinner';
import { developerLogin } from '@/routes';

export type DeveloperUser = {
    id: number;
    name: string;
    email: string;
    role: string;
};

const props = defineProps<{
    users?: DeveloperUser[];
}>();

const submittingEmail = ref<string | null>(null);

const handleQuickLogin = (email: string) => {
    submittingEmail.value = email;

    router.post(
        developerLogin.url(),
        { email },
        {
            onFinish: () => (submittingEmail.value = null),
        },
    );
};
</script>

<template>
    <div v-if="props.users && props.users.length > 0" class="space-y-3">
        <div class="relative">
            <div class="absolute inset-0 flex items-center">
                <span
                    class="w-full border-t border-dashed border-border"
                    aria-hidden="true"
                />
            </div>
            <div class="relative flex justify-center text-xs uppercase">
                <span
                    class="flex items-center gap-1.5 bg-background px-2 font-mono text-[11px] text-muted-foreground"
                >
                    <Code2 class="h-3.5 w-3.5 text-amber-500" />
                    Quick Developer Login
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-2">
            <button
                v-for="user in props.users"
                :key="user.id"
                type="button"
                @click="handleQuickLogin(user.email)"
                :disabled="submittingEmail !== null"
                class="flex items-center justify-between rounded-lg border border-dashed border-amber-500/30 bg-amber-500/5 px-3 py-2 text-left text-sm transition-colors hover:border-amber-500/50 hover:bg-amber-500/10 disabled:pointer-events-none disabled:opacity-50 dark:border-amber-500/20 dark:bg-amber-500/5 dark:hover:bg-amber-500/10"
                :data-test="`developer-login-${user.id}`"
            >
                <div class="flex min-w-0 flex-col pr-2">
                    <span class="truncate font-medium text-foreground">
                        {{ user.name }}
                    </span>
                    <span
                        class="truncate font-mono text-xs text-muted-foreground"
                    >
                        {{ user.email }}
                    </span>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <Badge
                        variant="outline"
                        class="border-amber-500/40 font-mono text-[10px] text-amber-600 dark:text-amber-400"
                    >
                        {{ user.role }}
                    </Badge>
                    <Spinner
                        v-if="submittingEmail === user.email"
                        class="h-4 w-4"
                    />
                    <ArrowRight
                        v-else
                        class="h-3.5 w-3.5 text-muted-foreground"
                    />
                </div>
            </button>
        </div>
    </div>
</template>
