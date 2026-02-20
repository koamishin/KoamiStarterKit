<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
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
    resend as resendRoute,
    start,
} from '@/routes/security/mfa/email';

const props = defineProps<{
    enabled: boolean;
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

const modalOpen = ref(false);
const isSending = ref(false);
const isSubmitting = ref(false);
const error = ref<string | null>(null);
const fieldError = ref<string | null>(null);
const code = ref('');

const canSubmit = computed(() => code.value.length === 6);

const openEnable = async () => {
    modalOpen.value = true;
    error.value = null;
    fieldError.value = null;
    code.value = '';

    isSending.value = true;

    try {
        await http.post(start.url());
        toast.success('A new code has been sent to your email address.');
    } catch (e: any) {
        const defaultMessage = 'Failed to send code';
        const validationMessage = e.response?.data?.errors?.email?.[0];
        error.value =
            validationMessage ?? e.response?.data?.message ?? defaultMessage;
        toast.error(error.value);
    } finally {
        isSending.value = false;
    }
};

const resend = async () => {
    error.value = null;
    fieldError.value = null;
    isSending.value = true;

    try {
        await http.post(resendRoute.url());
        toast.success('A new code has been sent to your email address.');
    } catch (e: any) {
        error.value = e.response?.data?.message ?? 'Failed to resend code';
        toast.error(error.value);
    } finally {
        isSending.value = false;
    }
};

const enable = async () => {
    error.value = null;
    fieldError.value = null;
    isSubmitting.value = true;

    try {
        await http.post(enableRoute.url(), { code: code.value });

        isEnabled.value = true;
        emit('update:enabled', true);
        modalOpen.value = false;
        toast.success('Email authentication has been enabled.');
    } catch (e: any) {
        error.value = e.response?.data?.message ?? 'Failed to enable';
        fieldError.value = e.response?.data?.errors?.code?.[0] ?? null;
        toast.error(error.value);
    } finally {
        isSubmitting.value = false;
    }
};

const disable = async () => {
    error.value = null;

    try {
        await http.delete(disableRoute.url());
        isEnabled.value = false;
        emit('update:enabled', false);
        toast.success('Email authentication has been disabled.');
    } catch (e: any) {
        error.value = e.response?.data?.message ?? 'Failed to disable';
        toast.error(error.value);
    }
};
</script>

<template>
    <Card>
        <CardHeader>
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <CardTitle>Email codes</CardTitle>
                    <CardDescription>
                        Receive a one-time code by email during sign-in.
                    </CardDescription>
                </div>
                <Badge variant="secondary">
                    {{ isEnabled ? 'Enabled' : 'Disabled' }}
                </Badge>
            </div>
        </CardHeader>

        <CardContent>
            <AlertError v-if="error" :errors="[error]" />
        </CardContent>

        <CardFooter class="flex items-center gap-2">
            <Button v-if="!isEnabled" @click="openEnable">Enable</Button>
            <Button v-else variant="destructive" @click="disable"
                >Disable</Button
            >
        </CardFooter>
    </Card>

    <Dialog :open="modalOpen" @update:open="modalOpen = $event">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Enable email codes</DialogTitle>
                <DialogDescription>
                    Enter the 6-digit code sent to your email address.
                </DialogDescription>
            </DialogHeader>

            <div v-if="isSending" class="flex justify-center py-6">
                <Spinner />
            </div>

            <div v-else class="space-y-4">
                <div class="space-y-2">
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
                    <InputError :message="fieldError ?? undefined" />
                </div>

                <div class="flex justify-center">
                    <Button type="button" variant="link" @click="resend">
                        Resend code
                    </Button>
                </div>
            </div>

            <DialogFooter>
                <Button
                    type="button"
                    variant="secondary"
                    @click="modalOpen = false"
                >
                    Cancel
                </Button>
                <Button
                    type="button"
                    :disabled="!canSubmit || isSubmitting"
                    @click="enable"
                >
                    Confirm
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
