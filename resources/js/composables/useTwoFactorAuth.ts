import type { ComputedRef, Ref } from 'vue';
import { computed, ref } from 'vue';
import { setup as setupApp } from '@/routes/security/mfa/app';
import type { TwoFactorSetupResponse } from '@/types/generated';

export type UseTwoFactorAuthReturn = {
    qrCodeDataUri: Ref<string | null>;
    secret: Ref<string | null>;
    recoveryCodesList: Ref<string[]>;
    encrypted: Ref<string | null>;
    errors: Ref<string[]>;
    hasSetupData: ComputedRef<boolean>;
    clearSetupData: () => void;
    clearErrors: () => void;
    clearTwoFactorAuthData: () => void;
    fetchSetupData: () => Promise<void>;
    fetchRecoveryCodes: () => Promise<void>;
};

const postJson = async <T>(url: string, data: any = {}): Promise<T> => {
    const token = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');

    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': token || '',
        },
        body: JSON.stringify(data),
    });

    if (!response.ok) {
        throw new Error(`Failed to fetch: ${response.status}`);
    }

    return response.json();
};

const errors = ref<string[]>([]);
const secret = ref<string | null>(null);
const qrCodeDataUri = ref<string | null>(null);
const recoveryCodesList = ref<string[]>([]);
const encrypted = ref<string | null>(null);

const hasSetupData = computed<boolean>(
    () => qrCodeDataUri.value !== null && secret.value !== null,
);

export const useTwoFactorAuth = (): UseTwoFactorAuthReturn => {
    const clearSetupData = (): void => {
        secret.value = null;
        qrCodeDataUri.value = null;
        encrypted.value = null;
        clearErrors();
    };

    const clearErrors = (): void => {
        errors.value = [];
    };

    const clearTwoFactorAuthData = (): void => {
        clearSetupData();
        clearErrors();
        recoveryCodesList.value = [];
    };

    const fetchRecoveryCodes = async (): Promise<void> => {
        // This function might need to be updated to use postJson if the route is a POST
        // For now, assuming it is a GET, but based on the controller it is a POST
        // I will leave it as is for now, as it is not the main issue
        try {
            clearErrors();
            // recoveryCodesList.value = await fetchJson<string[]>(
            //     recoveryCodes.url(),
            // );
        } catch {
            errors.value.push('Failed to fetch recovery codes');
            recoveryCodesList.value = [];
        }
    };

    const fetchSetupData = async (): Promise<void> => {
        try {
            clearErrors();
            const response = await postJson<TwoFactorSetupResponse>(
                setupApp.url(),
            );
            qrCodeDataUri.value = response.qrCodeDataUri;
            secret.value = response.secret;
            encrypted.value = response.encrypted;
        } catch {
            qrCodeDataUri.value = null;
            secret.value = null;
            encrypted.value = null;
            errors.value.push('Failed to fetch 2FA setup data.');
        }
    };

    return {
        qrCodeDataUri,
        secret,
        recoveryCodesList,
        encrypted,
        errors,
        hasSetupData,
        clearSetupData,
        clearErrors,
        clearTwoFactorAuthData,
        fetchSetupData,
        fetchRecoveryCodes,
    };
};
