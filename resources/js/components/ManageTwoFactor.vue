<script setup lang="ts">
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
import AlertError from '@/components/AlertError.vue';
import InputError from '@/components/InputError.vue';
import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.vue';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import http from '@/lib/http';
import {
    confirm as confirmRoute,
    disable as disableRoute,
    enable as enableRoute,
    qrCode as qrCodeRoute,
    secretKey as secretKeyRoute,
} from '@/routes/two-factor';
import { Check, Copy, ScanLine } from '@lucide/vue';
import { useClipboard } from '@vueuse/core';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';

type Props = {
    enabled: boolean;
    requiresConfirmation: boolean;
};

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'update:enabled', value: boolean): void;
}>();

const { copy, copied } = useClipboard();
const { errors, clearErrors, clearSetupData } = useTwoFactorAuth();

const isEnabled = ref(props.enabled);
const setupOpen = ref(false);
const loading = ref(false);
const confirming = ref(false);
const svg = ref<string | null>(null);
const secret = ref<string | null>(null);
const code = ref('');
const fieldError = ref<string | null>(null);
const recoveryCodes = ref<string[]>([]);
const showRecoveryCodesDialog = ref(false);

const showConfirmStep = computed(
    () => props.requiresConfirmation && !isEnabled.value,
);

const openSetup = async () => {
    setupOpen.value = true;
    clearErrors();
    fieldError.value = null;
    code.value = '';

    loading.value = true;

    try {
        if (!svg.value || !secret.value) {
            await http.post(enableRoute.url(), {}, {
                headers: { Accept: 'application/json' },
            });

            const [qrResponse, secretResponse] = await Promise.all([
                http.get<{ svg: string }>(qrCodeRoute.url(), {
                    headers: { Accept: 'application/json' },
                }),
                http.get<{ secretKey: string }>(secretKeyRoute.url(), {
                    headers: { Accept: 'application/json' },
                }),
            ]);

            svg.value = qrResponse.data.svg;
            secret.value = secretResponse.data.secretKey;
        }
    } catch (error: any) {
        toast.error(
            error.response?.data?.message ?? 'Failed to start 2FA setup',
        );
        setupOpen.value = false;
    } finally {
        loading.value = false;
    }
};

const closeSetup = () => {
    setupOpen.value = false;
    code.value = '';
    fieldError.value = null;

    if (!isEnabled.value) {
        svg.value = null;
        secret.value = null;
        clearSetupData();
    }
};

const completeSetup = async () => {
    if (props.requiresConfirmation && code.value.length < 6) {
        return;
    }

    confirming.value = true;
    fieldError.value = null;

    try {
        if (props.requiresConfirmation) {
            await http.post(
                confirmRoute.url(),
                { code: code.value },
                { headers: { Accept: 'application/json' } },
            );
        }

        isEnabled.value = true;
        emit('update:enabled', true);
        setupOpen.value = false;
        code.value = '';
        svg.value = null;
        secret.value = null;
        clearSetupData();
        toast.success('Two-factor authentication enabled');
        await loadRecoveryCodes();
        showRecoveryCodesDialog.value = true;
    } catch (error: any) {
        fieldError.value =
            error.response?.data?.errors?.code?.[0] ??
            error.response?.data?.message ??
            'Invalid authentication code.';
    } finally {
        confirming.value = false;
    }
};

const disable = async () => {
    loading.value = true;

    try {
        await http.delete(disableRoute.url(), {
            headers: { Accept: 'application/json' },
        });
        isEnabled.value = false;
        emit('update:enabled', false);
        recoveryCodes.value = [];
        toast.success('Two-factor authentication disabled');
    } catch (error: any) {
        toast.error(
            error.response?.data?.message ?? 'Failed to disable 2FA',
        );
    } finally {
        loading.value = false;
    }
};

const loadRecoveryCodes = async () => {
    try {
        const response = await http.get<string[]>(
            '/user/two-factor-recovery-codes',
            { headers: { Accept: 'application/json' } },
        );
        recoveryCodes.value = Array.isArray(response.data)
            ? response.data
            : [];
    } catch {
        recoveryCodes.value = [];
    }
};

const recoveryCodesText = computed(() => recoveryCodes.value.join('\n'));
</script>

<template>
    <Card>
        <CardHeader>
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <CardTitle class="flex items-center gap-2">
                        <ScanLine class="h-4 w-4" />
                        Two-factor authentication
                    </CardTitle>
                    <CardDescription>
                        Require a one-time code from an authenticator app when
                        you sign in.
                    </CardDescription>
                </div>
                <Badge :variant="isEnabled ? 'default' : 'secondary'">
                    {{ isEnabled ? 'Enabled' : 'Disabled' }}
                </Badge>
            </div>
        </CardHeader>

        <CardContent>
            <AlertError v-if="errors?.length" :errors="errors" />
        </CardContent>

        <CardFooter class="flex flex-wrap items-center gap-2">
            <Button
                v-if="!isEnabled"
                :disabled="loading"
                @click="openSetup"
            >
                <Spinner v-if="loading" class="mr-2" />
                Enable 2FA
            </Button>

            <Button
                v-else
                variant="destructive"
                :disabled="loading"
                @click="disable"
            >
                <Spinner v-if="loading" class="mr-2" />
                Disable 2FA
            </Button>

            <Button
                v-if="isEnabled"
                variant="secondary"
                @click="
                    async () => {
                        await loadRecoveryCodes();
                        showRecoveryCodesDialog = true;
                    }
                "
            >
                View recovery codes
            </Button>
        </CardFooter>
    </Card>

    <Dialog :open="setupOpen" @update:open="setupOpen = $event">
        <DialogContent class="sm:max-w-md">
            <DialogHeader class="flex items-center justify-center">
                <div
                    class="mb-3 rounded-full border border-border bg-card p-0.5 shadow-sm"
                >
                    <div
                        class="relative overflow-hidden rounded-full border border-border bg-muted p-2.5"
                    >
                        <ScanLine class="relative z-20 size-6 text-foreground" />
                    </div>
                </div>
                <DialogTitle>
                    {{
                        showConfirmStep
                            ? 'Verify authentication code'
                            : 'Enable two-factor authentication'
                    }}
                </DialogTitle>
                <DialogDescription class="text-center">
                    {{
                        showConfirmStep
                            ? 'Enter the 6-digit code from your authenticator app'
                            : 'Scan the QR code or enter the setup key in your authenticator app'
                    }}
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-5">
                <template v-if="!showConfirmStep">
                    <div v-if="loading" class="flex justify-center py-8">
                        <Spinner class="size-6" />
                    </div>

                    <template v-else-if="svg && secret">
                        <div class="flex justify-center">
                            <div
                                class="relative aspect-square w-64 overflow-hidden rounded-lg border border-border bg-white p-4"
                            >
                                <div
                                    class="flex aspect-square size-full items-center justify-center"
                                    v-html="svg"
                                />
                            </div>
                        </div>

                        <div
                            class="flex w-full items-stretch overflow-hidden rounded-xl border border-border"
                        >
                            <input
                                type="text"
                                readonly
                                :value="secret"
                                class="h-full w-full bg-background p-3 font-mono text-foreground"
                            />
                            <button
                                type="button"
                                class="relative block h-auto border-l border-border px-3 hover:bg-muted"
                                @click="copy(secret || '')"
                            >
                                <Check
                                    v-if="copied"
                                    class="w-4 text-green-500"
                                />
                                <Copy v-else class="w-4" />
                            </button>
                        </div>

                        <Button
                            class="w-full"
                            :disabled="!requiresConfirmation"
                            @click="completeSetup"
                        >
                            {{
                                requiresConfirmation
                                    ? 'Continue'
                                    : 'Enabled — close this dialog'
                            }}
                        </Button>
                        <p
                            v-if="!requiresConfirmation"
                            class="text-center text-xs text-muted-foreground"
                        >
                            Two-factor authentication is active. Save your
                            recovery codes after closing.
                        </p>
                    </template>
                </template>

                <template v-else>
                    <div class="space-y-3">
                        <div class="flex justify-center">
                            <InputOTP
                                id="fortify-otp"
                                v-model="code"
                                :maxlength="6"
                                :disabled="confirming"
                            >
                                <InputOTPGroup>
                                    <InputOTPSlot
                                        v-for="index in 6"
                                        :key="index"
                                        :index="index - 1"
                                    />
                                </InputOTPGroup>
                            </InputOTP>
                        </div>
                        <InputError :message="fieldError ?? undefined" />
                    </div>
                </template>
            </div>

            <DialogFooter>
                <Button
                    type="button"
                    variant="secondary"
                    :disabled="confirming"
                    @click="closeSetup"
                >
                    Cancel
                </Button>
                <Button
                    v-if="showConfirmStep"
                    type="button"
                    :disabled="confirming || code.length < 6"
                    @click="completeSetup"
                >
                    <Spinner v-if="confirming" class="mr-2" />
                    Confirm
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog
        :open="showRecoveryCodesDialog"
        @update:open="showRecoveryCodesDialog = $event"
    >
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Recovery codes</DialogTitle>
                <DialogDescription>
                    Save these recovery codes somewhere safe. Each code can be
                    used once if you lose access to your authenticator app.
                </DialogDescription>
            </DialogHeader>

            <div v-if="recoveryCodes.length" class="space-y-4">
                <div class="grid grid-cols-2 gap-2">
                    <div
                        v-for="recoveryCode in recoveryCodes"
                        :key="recoveryCode"
                        class="rounded-md border border-border bg-muted/30 px-3 py-2 font-mono text-xs"
                    >
                        {{ recoveryCode }}
                    </div>
                </div>

                <div class="flex gap-2">
                    <Button
                        type="button"
                        variant="secondary"
                        size="sm"
                        @click="copy(recoveryCodesText)"
                    >
                        <Copy class="mr-1 h-4 w-4" />
                        Copy all
                    </Button>
                </div>
            </div>
            <div v-else class="py-4">
                <TwoFactorRecoveryCodes />
            </div>

            <DialogFooter>
                <Button
                    type="button"
                    @click="showRecoveryCodesDialog = false"
                >
                    Close
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
