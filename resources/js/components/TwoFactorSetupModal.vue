<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { useClipboard } from '@vueuse/core';
import { Check, Copy, ScanLine } from 'lucide-vue-next';
import { computed, nextTick, ref, useTemplateRef, watch } from 'vue';
import AlertError from '@/components/AlertError.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Spinner } from '@/components/ui/spinner';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import { enable as enableApp } from '@/routes/security/mfa/app';
import type { TwoFactorConfigContent } from '@/types';

type Props = {
    requiresConfirmation: boolean;
    twoFactorEnabled: boolean;
};

const props = defineProps<Props>();
const isOpen = defineModel<boolean>('isOpen');

const { copy, copied } = useClipboard();
const {
    qrCodeDataUri,
    secret,
    encrypted,
    clearSetupData,
    fetchSetupData,
    errors,
} = useTwoFactorAuth();

const showVerificationStep = ref(false);
const code = ref<string>('');

const pinInputContainerRef = useTemplateRef('pinInputContainerRef');

const modalConfig = computed<TwoFactorConfigContent>(() => {
    if (props.twoFactorEnabled) {
        return {
            title: 'Two-Factor Authentication Enabled',
            description:
                'Two-factor authentication is now enabled. Store these recovery codes in a secure location.',
            buttonText: 'Close',
        };
    }

    if (showVerificationStep.value) {
        return {
            title: 'Verify Authentication Code',
            description: 'Enter the 6-digit code from your authenticator app',
            buttonText: 'Continue',
        };
    }

    return {
        title: 'Enable Two-Factor Authentication',
        description:
            'To finish enabling two-factor authentication, scan the QR code or enter the setup key in your authenticator app',
        buttonText: 'Continue',
    };
});

const handleModalNextStep = () => {
    if (props.requiresConfirmation) {
        showVerificationStep.value = true;

        nextTick(() => {
            pinInputContainerRef.value?.querySelector('input')?.focus();
        });

        return;
    }

    clearSetupData();
    isOpen.value = false;
};

const resetModalState = () => {
    if (props.twoFactorEnabled) {
        clearSetupData();
    }

    showVerificationStep.value = false;
    code.value = '';
};

watch(
    () => isOpen.value,
    async (isOpen) => {
        if (!isOpen) {
            resetModalState();
            return;
        }

        if (!qrCodeDataUri.value) {
            await fetchSetupData();
        }
    },
);
</script>

<template>
    <Dialog :open="isOpen" @update:open="isOpen = $event">
        <DialogContent class="sm:max-w-md">
            <DialogHeader class="flex items-center justify-center">
                <div
                    class="mb-3 w-auto rounded-full border border-border bg-card p-0.5 shadow-sm"
                >
                    <div
                        class="relative overflow-hidden rounded-full border border-border bg-muted p-2.5"
                    >
                        <div
                            class="absolute inset-0 grid grid-cols-5 opacity-50"
                        >
                            <div
                                v-for="i in 5"
                                :key="`col-${i}`"
                                class="border-r border-border last:border-r-0"
                            />
                        </div>
                        <div
                            class="absolute inset-0 grid grid-rows-5 opacity-50"
                        >
                            <div
                                v-for="i in 5"
                                :key="`row-${i}`"
                                class="border-b border-border last:border-b-0"
                            />
                        </div>
                        <ScanLine
                            class="relative z-20 size-6 text-foreground"
                        />
                    </div>
                </div>
                <DialogTitle>{{ modalConfig.title }}</DialogTitle>
                <DialogDescription class="text-center">
                    {{ modalConfig.description }}
                </DialogDescription>
            </DialogHeader>

            <div
                class="relative flex w-auto flex-col items-center justify-center space-y-5"
            >
                <template v-if="!showVerificationStep">
                    <AlertError v-if="errors?.length" :errors="errors" />
                    <template v-else>
                        <Card class="w-full">
                            <CardHeader
                                class="flex flex-col items-center justify-center"
                            >
                                <CardTitle class="text-lg"
                                    >Scan QR Code</CardTitle
                                >
                            </CardHeader>
                            <CardContent
                                class="flex flex-col items-center justify-center space-y-4"
                            >
                                <div
                                    class="relative mx-auto flex max-w-md items-center overflow-hidden"
                                >
                                    <div
                                        class="relative mx-auto aspect-square w-64 overflow-hidden rounded-lg border border-border bg-white p-4"
                                    >
                                        <div
                                            v-if="!qrCodeDataUri"
                                            class="absolute inset-0 z-10 flex aspect-square h-auto w-full animate-pulse items-center justify-center bg-background"
                                        >
                                            <Spinner class="size-6" />
                                        </div>
                                        <img
                                            v-else
                                            :src="qrCodeDataUri"
                                            class="flex aspect-square size-full items-center justify-center"
                                        />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <div
                            class="relative flex w-full items-center justify-center"
                        >
                            <div
                                class="absolute inset-0 top-1/2 h-px w-full bg-border"
                            />
                            <span class="relative bg-card px-2 py-1"
                                >Or, enter manually</span
                            >
                        </div>

                        <div
                            class="flex w-full items-center justify-center space-x-2"
                        >
                            <div
                                class="flex w-full items-stretch overflow-hidden rounded-xl border border-border"
                            >
                                <div
                                    v-if="!secret"
                                    class="flex h-full w-full items-center justify-center bg-muted p-3"
                                >
                                    <Spinner />
                                </div>
                                <template v-else>
                                    <input
                                        type="text"
                                        readonly
                                        :value="secret"
                                        class="h-full w-full bg-background p-3 text-foreground"
                                    />
                                    <button
                                        @click="copy(secret || '')"
                                        class="relative block h-auto border-l border-border px-3 hover:bg-muted"
                                    >
                                        <Check
                                            v-if="copied"
                                            class="w-4 text-green-500"
                                        />
                                        <Copy v-else class="w-4" />
                                    </button>
                                </template>
                            </div>
                        </div>
                        <Button class="w-full" @click="handleModalNextStep">
                            {{ modalConfig.buttonText }}
                        </Button>
                    </template>
                </template>

                <template v-else>
                    <Form
                        v-bind="
                            enableApp.form({ code: code, encrypted: encrypted })
                        "
                        reset-on-error
                        @finish="code = ''"
                        @success="isOpen = false"
                        v-slot="{ errors, processing }"
                    >
                        <div
                            ref="pinInputContainerRef"
                            class="relative w-full space-y-3"
                        >
                            <div
                                class="flex w-full flex-col items-center justify-center space-y-3 py-2"
                            >
                                <InputOTP
                                    id="otp"
                                    v-model="code"
                                    :maxlength="6"
                                    :disabled="processing"
                                >
                                    <InputOTPGroup>
                                        <InputOTPSlot
                                            v-for="index in 6"
                                            :key="index"
                                            :index="index - 1"
                                        />
                                    </InputOTPGroup>
                                </InputOTP>
                                <InputError :message="errors.code" />
                            </div>

                            <div class="flex w-full items-center space-x-5">
                                <Button
                                    type="button"
                                    variant="outline"
                                    class="w-auto flex-1"
                                    @click="showVerificationStep = false"
                                    :disabled="processing"
                                >
                                    Back
                                </Button>
                                <Button
                                    type="submit"
                                    class="w-auto flex-1"
                                    :disabled="processing || code.length < 6"
                                >
                                    Confirm
                                </Button>
                            </div>
                        </div>
                    </Form>
                </template>
            </div>
        </DialogContent>
    </Dialog>
</template>
