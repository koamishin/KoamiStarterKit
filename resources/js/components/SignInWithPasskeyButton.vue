<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Passkeys, UserCancelledError } from '@laravel/passkeys';
import { Fingerprint } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

const supported = ref(true);
const loading = ref(false);
const error = ref<string | null>(null);

onMounted(() => {
    supported.value = Passkeys.isSupported();
});

const signIn = async () => {
    if (!supported.value || loading.value) {
        return;
    }

    loading.value = true;
    error.value = null;

    try {
        const result = await Passkeys.verify();
        const redirect = (result as { redirect?: string } | null)?.redirect;

        if (redirect) {
            router.visit(redirect);

            return;
        }

        router.reload();
    } catch (err: any) {
        if (err instanceof UserCancelledError) {
            return;
        }

        error.value = err?.message ?? 'Passkey sign in failed.';
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <div class="space-y-3">
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

        <Button
            type="button"
            variant="outline"
            class="w-full"
            :disabled="!supported || loading"
            data-test="sign-in-with-passkey-button"
            @click="signIn"
        >
            <Spinner v-if="loading" class="mr-2" />
            <Fingerprint v-else class="mr-2 h-4 w-4" />
            {{
                loading
                    ? 'Signing in…'
                    : supported
                      ? 'Sign in with passkey'
                      : 'Passkeys not supported'
            }}
        </Button>

        <p
            v-if="error"
            role="alert"
            class="text-center text-sm text-destructive"
        >
            {{ error }}
        </p>
    </div>
</template>
