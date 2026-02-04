export type TwoFactorSetupResponse = {
    encrypted: string;
    secret: string;
    qrCodeDataUri: string;
    recoveryCodes: string[] | null;
};
