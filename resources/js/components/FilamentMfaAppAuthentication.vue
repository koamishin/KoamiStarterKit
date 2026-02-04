<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useClipboard } from '@vueuse/core';
import { toast } from 'vue-sonner';
import { Copy } from 'lucide-vue-next';
import AlertError from '@/components/AlertError.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Spinner } from '@/components/ui/spinner';
import http from '@/lib/http';
import {
    disable as disableRoute,
    enable as enableRoute,
    recoveryCodes,
    setup as setupRoute,
} from '@/routes/security/mfa/app';

type SetupResponse = {
    encrypted: string;
    secret: string;
    qrCodeDataUri: string;
    recoveryCodes: string[] | null;
};

type EnableResponse = {
    recoveryCodes: string[] | null;
};

const props = defineProps<{
    enabled: boolean;
    recoverable: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:enabled', value: boolean): void;
}>();

const isEnabled = ref(props.enabled);
watch(
    () => props.enabled,
    (value) => {
        isEnabled.value = value;
    },
);

const setupModalOpen = ref(false);
const recoveryCodesModalOpen = ref(false);

const isLoadingSetup = ref(false);
const setup = ref<SetupResponse | null>(null);
const code = ref('');
const enableError = ref<string | null>(null);
const enableFieldError = ref<string | null>(null);
const enabledRecoveryCodes = ref<string[] | null>(null);

const { copy, copied } = useClipboard();

const canSubmit = computed(() => code.value.length === 6 && setup.value !== null);

const openSetup = async () => {
    setupModalOpen.value = true;
    enableError.value = null;
    enableFieldError.value = null;
    enabledRecoveryCodes.value = null;
    code.value = '';

    if (setup.value) {
        return;
    }

    isLoadingSetup.value = true;

    try {
        const response = await http.post<SetupResponse>(setupRoute.url());
        setup.value = response.data;
    } catch (error: any) {
        enableError.value = error.response?.data?.message ?? 'Failed to start setup';
        setup.value = null;
        toast.error(enableError.value);
    } finally {
        isLoadingSetup.value = false;
    }
};

const closeSetup = () => {
    setupModalOpen.value = false;
    setup.value = null;
    code.value = '';
    enableError.value = null;
    enableFieldError.value = null;
    enabledRecoveryCodes.value = null;
};

const enable = async () => {
    if (!setup.value) {
        return;
    }

    enableError.value = null;
    enableFieldError.value = null;

    try {
        const response = await http.post<EnableResponse>(
            enableRoute.url(),
            {
                encrypted: setup.value.encrypted,
                code: code.value,
            },
        );

        isEnabled.value = true;
        emit('update:enabled', true);
        enabledRecoveryCodes.value = response.data.recoveryCodes ?? null;

        if (enabledRecoveryCodes.value?.length) {
            recoveryCodesModalOpen.value = true;
        }

        closeSetup();
        toast.success('Authenticator app enabled successfully');
    } catch (error: any) {
        enableError.value = error.response?.data?.message ?? 'Failed to enable';
        enableFieldError.value = error.response?.data?.errors?.code?.[0] ?? null;
        toast.error(enableError.value);
    }
};

const disable = async () => {
    enableError.value = null;

    try {
        await http.delete(disableRoute.url());
        isEnabled.value = false;
        emit('update:enabled', false);
        toast.success('Authenticator app disabled successfully');
    } catch (error: any) {
        enableError.value = error.response?.data?.message ?? 'Failed to disable';
        toast.error(enableError.value);
    }
};

const regenerateRecoveryCodes = async () => {
    enableError.value = null;

    try {
        const response = await http.post<{ recoveryCodes: string[] }>(
            recoveryCodes.url(),
        );

        enabledRecoveryCodes.value = response.data.recoveryCodes;
        recoveryCodesModalOpen.value = true;
        toast.success('Recovery codes regenerated successfully');
    } catch (error: any) {
        enableError.value = error.response?.data?.message ?? 'Failed to regenerate codes';
        toast.error(enableError.value);
    }
};

const recoveryCodesText = computed(() =>
    enabledRecoveryCodes.value ? enabledRecoveryCodes.value.join('\n') : '',
);
</script>

<template>
    <Card>
        <CardHeader>
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <CardTitle>Authenticator app</CardTitle>
                    <CardDescription>
                        Use an authenticator app to generate one-time codes.
                    </CardDescription>
                </div>
                <Badge variant="secondary">
                    {{ isEnabled ? 'Enabled' : 'Disabled' }}
                </Badge>
            </div>
        </CardHeader>

        <CardContent>
            <AlertError v-if="enableError" :errors="[enableError]" />
        </CardContent>

        <CardFooter class="flex items-center gap-2">
            <Button v-if="!isEnabled" @click="openSetup">Enable</Button>

            <Button v-else variant="destructive" @click="disable">Disable</Button>

            <Button
                v-if="isEnabled && recoverable"
                variant="secondary"
                @click="regenerateRecoveryCodes"
            >
                Regenerate recovery codes
            </Button>
        </CardFooter>
    </Card>

    <Dialog :open="setupModalOpen" @update:open="setupModalOpen = $event">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Enable authenticator app</DialogTitle>
                <DialogDescription>
                    Scan the QR code or enter the setup key, then confirm using a
                    code from your app.
                </DialogDescription>
            </DialogHeader>

            <div v-if="isLoadingSetup" class="flex justify-center py-8">
                <Spinner />
            </div>

            <div v-else-if="setup" class="space-y-6">
                <div class="flex justify-center">
                    <img
                        :src="setup.qrCodeDataUri"
                        alt="Authenticator QR code"
                        class="h-48 w-48 rounded-md border border-border bg-white p-2"
                    />
                </div>

                <div class="rounded-lg border border-border bg-muted/30 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div class="space-y-1">
                            <p class="text-sm font-medium">Setup key</p>
                            <p class="font-mono text-sm break-all">
                                {{ setup.secret }}
                            </p>
                        </div>
                        <Button
                            type="button"
                            variant="secondary"
                            size="sm"
                            @click="copy(setup.secret)"
                        >
                            <Copy class="h-4 w-4" />
                            <span class="sr-only">Copy setup key</span>
                        </Button>
                    </div>
                    <p v-if="copied" class="mt-2 text-sm text-green-600">
                        Copied
                    </p>
                </div>

                <div class="space-y-2">
                    <p class="text-sm font-medium">Authentication code</p>
                    <div class="flex justify-center">
                        <InputOTP v-model="code" :maxLength="6">
                            <InputOTPGroup>
                                <InputOTPSlot
                                    v-for="slot in 6"
                                    :key="slot"
                                    :index="slot - 1"
                                />
                            </InputOTPGroup>
                        </InputOTP>
                    </div>
                    <InputError :message="enableFieldError ?? undefined" />
                </div>
            </div>

            <DialogFooter>
                <Button type="button" variant="secondary" @click="closeSetup">
                    Cancel
                </Button>
                <Button type="button" :disabled="!canSubmit" @click="enable">
                    Enable
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog
        :open="recoveryCodesModalOpen"
        @update:open="recoveryCodesModalOpen = $event"
    >
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Recovery codes</DialogTitle>
                <DialogDescription>
                    Save these recovery codes somewhere safe. Each code can be
                    used once.
                </DialogDescription>
            </DialogHeader>

            <div v-if="enabledRecoveryCodes?.length" class="space-y-4">
                <div class="grid grid-cols-2 gap-2">
                    <div
                        v-for="recoveryCode in enabledRecoveryCodes"
                        :key="recoveryCode"
                        class="rounded-md border border-border bg-muted/30 px-3 py-2 font-mono text-xs"
                    >
                        {{ recoveryCode }}
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Button
                        type="button"
                        variant="secondary"
                        size="sm"
                        @click="copy(recoveryCodesText)"
                    >
                        <Copy class="h-4 w-4" />
                        Copy all
                    </Button>
                    <a
                        :href="`data:application/octet-stream,${encodeURIComponent(recoveryCodesText)}`"
                        download
                    >
                        <Button type="button" variant="secondary" size="sm">
                            Download
                        </Button>
                    </a>
                </div>
            </div>

            <DialogFooter>
                <Button type="button" @click="recoveryCodesModalOpen = false">
                    Close
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
