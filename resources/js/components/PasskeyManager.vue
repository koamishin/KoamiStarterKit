<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    InvalidDomainError,
    NotSupportedError,
    PasskeyExistsError,
    Passkeys,
    UserCancelledError,
} from '@laravel/passkeys';
import { KeyRound, Loader2, Plus, ShieldX, Trash2 } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import http from '@/lib/http';

type Passkey = {
    id: string;
    name: string;
    authenticator: string | null;
    last_used_at: string | null;
    created_at: string | null;
};

const props = defineProps<{
    passkeys: Passkey[];
}>();

const csrfToken =
    document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content') ?? '';

const passkeys = ref<Passkey[]>([...props.passkeys]);
const supported = ref(true);
const addDialogOpen = ref(false);
const deleteDialogOpen = ref(false);
const confirmDialogOpen = ref(false);

const newName = ref('');
const pendingAction = ref<(() => Promise<void>) | null>(null);
const password = ref('');
const passwordError = ref<string | null>(null);
const confirming = ref(false);

const loading = ref(false);
const error = ref<string | null>(null);
const passkeyToDelete = ref<Passkey | null>(null);

onMounted(() => {
    supported.value = Passkeys.isSupported();
});

const lastUsedLabel = (passkey: Passkey): string => {
    if (!passkey.last_used_at) {
        return 'Never used';
    }

    const date = new Date(passkey.last_used_at);

    return `Last used ${date.toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    })}`;
};

const canSubmitAdd = computed(
    () => newName.value.trim().length > 0 && !loading.value,
);

const canSubmitConfirm = computed(
    () => password.value.length > 0 && !confirming.value,
);

const openAddDialog = () => {
    if (!supported.value) {
        return;
    }

    newName.value = '';
    error.value = null;
    addDialogOpen.value = true;
};

const closeAddDialog = () => {
    addDialogOpen.value = false;
    newName.value = '';
    error.value = null;
};

const askForPassword = async (next: () => Promise<void>) => {
    if (await isPasswordConfirmed()) {
        await next();

        return;
    }

    password.value = '';
    passwordError.value = null;
    pendingAction.value = next;
    confirmDialogOpen.value = true;
};

const isPasswordConfirmed = async (): Promise<boolean> => {
    try {
        const response = await http.get('/user/confirmed-password-status', {
            headers: { Accept: 'application/json' },
        });

        return Boolean(response.data?.confirmed);
    } catch {
        return false;
    }
};

const confirmPassword = async () => {
    if (!canSubmitConfirm.value) {
        return;
    }

    confirming.value = true;
    passwordError.value = null;

    try {
        await http.post(
            '/user/confirm-password',
            { password: password.value },
            {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            },
        );

        password.value = '';
        confirmDialogOpen.value = false;
        const next = pendingAction.value;
        pendingAction.value = null;

        if (next) {
            await next();
        }
    } catch (err: any) {
        if (err?.response?.status === 422) {
            passwordError.value =
                err.response.data?.errors?.password?.[0] ??
                'The password is incorrect.';
        } else {
            passwordError.value =
                err?.response?.data?.message ??
                err?.message ??
                'Could not verify password. Please try again.';
        }
    } finally {
        confirming.value = false;
    }
};

const addPasskey = async () => {
    if (!canSubmitAdd.value) {
        return;
    }

    const name = newName.value.trim();

    closeAddDialog();
    loading.value = true;
    error.value = null;

    try {
        const result = await Passkeys.register({ name });

        passkeys.value.unshift({
            id: String(result?.id ?? ''),
            name: result?.name ?? name,
            authenticator: null,
            last_used_at: null,
            created_at: new Date().toISOString(),
        });

        router.reload({ only: ['passkeys'] });
    } catch (err: any) {
        if (err instanceof UserCancelledError) {
            return;
        }

        if (err instanceof PasskeyExistsError) {
            error.value = 'This device already has a passkey for this account.';

            return;
        }

        if (err instanceof NotSupportedError) {
            error.value =
                'Your browser does not support passkeys. Try a recent version of Chrome, Safari, Edge, or Firefox.';

            return;
        }

        if (err instanceof InvalidDomainError) {
            error.value =
                'Passkeys cannot be used on this domain. Make sure you are on the same origin as the app.';

            return;
        }

        error.value = err?.message ?? 'Could not add passkey.';
    } finally {
        loading.value = false;
    }
};

const submitAdd = async () => {
    await askForPassword(addPasskey);
};

const askDelete = (passkey: Passkey) => {
    passkeyToDelete.value = passkey;
    error.value = null;
    deleteDialogOpen.value = true;
};

const cancelDelete = () => {
    deleteDialogOpen.value = false;
    passkeyToDelete.value = null;
};

const performDelete = async (passkey: Passkey) => {
    if (!passkey.id) {
        return;
    }

    loading.value = true;
    error.value = null;

    try {
        await http.delete(`/user/passkeys/${passkey.id}`, {
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
        });

        passkeys.value = passkeys.value.filter(
            (p) => String(p.id) !== String(passkey.id),
        );

        router.reload({ only: ['passkeys'] });
    } catch (err: any) {
        error.value =
            err?.response?.data?.message ?? 'Could not remove passkey.';
    } finally {
        loading.value = false;
        passkeyToDelete.value = null;
    }
};

const confirmDelete = async () => {
    const passkey = passkeyToDelete.value;

    deleteDialogOpen.value = false;

    if (!passkey) {
        return;
    }

    await askForPassword(() => performDelete(passkey));
};

const cancelConfirm = () => {
    confirmDialogOpen.value = false;
    pendingAction.value = null;
};
</script>

<template>
    <Card>
        <CardHeader>
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <CardTitle class="flex items-center gap-2">
                        <KeyRound class="h-4 w-4" />
                        Passkeys
                    </CardTitle>
                    <CardDescription>
                        Sign in faster and more securely with a passkey stored
                        on this device.
                    </CardDescription>
                </div>
                <Badge variant="secondary">
                    {{ passkeys.length }} registered
                </Badge>
            </div>
        </CardHeader>

        <CardContent class="space-y-4">
            <AlertError v-if="error" :errors="[error]" />

            <div
                v-if="passkeys.length === 0"
                class="rounded-md border border-dashed border-border p-4 text-sm text-muted-foreground"
            >
                No passkeys yet. Add one to enable passwordless sign in.
            </div>

            <ul v-else class="divide-y divide-border">
                <li
                    v-for="passkey in passkeys"
                    :key="passkey.id"
                    class="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0"
                >
                    <div class="min-w-0 space-y-0.5">
                        <p class="truncate text-sm font-medium">
                            {{ passkey.name }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            <span v-if="passkey.authenticator">
                                {{ passkey.authenticator }} ·
                            </span>
                            {{ lastUsedLabel(passkey) }}
                        </p>
                    </div>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        :disabled="loading"
                        @click="askDelete(passkey)"
                    >
                        <Trash2 class="h-4 w-4" />
                        <span class="sr-only">Remove passkey</span>
                    </Button>
                </li>
            </ul>
        </CardContent>

        <CardFooter>
            <Button
                type="button"
                :disabled="!supported || loading"
                @click="openAddDialog"
            >
                <Plus v-if="!loading" class="h-4 w-4" />
                <Spinner v-else />
                Add passkey
            </Button>
            <p v-if="!supported" class="text-xs text-muted-foreground">
                Passkeys are not supported by your browser.
            </p>
        </CardFooter>
    </Card>

    <Dialog :open="addDialogOpen" @update:open="addDialogOpen = $event">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Add a passkey</DialogTitle>
                <DialogDescription>
                    Give this passkey a name so you can recognise it later, for
                    example "MacBook Touch ID" or "iPhone 15".
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-2">
                <Label for="passkey-name">Passkey name</Label>
                <Input
                    id="passkey-name"
                    v-model="newName"
                    type="text"
                    placeholder="e.g. MacBook Touch ID"
                    :disabled="loading"
                    @keyup.enter="canSubmitAdd && submitAdd()"
                />
            </div>

            <DialogFooter>
                <Button
                    type="button"
                    variant="secondary"
                    :disabled="loading"
                    @click="closeAddDialog"
                >
                    Cancel
                </Button>
                <Button
                    type="button"
                    :disabled="!canSubmitAdd"
                    @click="submitAdd"
                >
                    <Loader2 v-if="loading" class="h-4 w-4 animate-spin" />
                    Continue
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog :open="confirmDialogOpen" @update:open="confirmDialogOpen = $event">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Confirm your password</DialogTitle>
                <DialogDescription>
                    For your security, please confirm your password to continue.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-2">
                <Label for="passkey-confirm-password">Password</Label>
                <Input
                    id="passkey-confirm-password"
                    v-model="password"
                    type="password"
                    autocomplete="current-password"
                    :disabled="confirming"
                    @keyup.enter="canSubmitConfirm && confirmPassword()"
                />
                <InputError :message="passwordError ?? undefined" />
            </div>

            <DialogFooter>
                <Button
                    type="button"
                    variant="secondary"
                    :disabled="confirming"
                    @click="cancelConfirm"
                >
                    Cancel
                </Button>
                <Button
                    type="button"
                    :disabled="!canSubmitConfirm"
                    @click="confirmPassword"
                >
                    <Loader2 v-if="confirming" class="h-4 w-4 animate-spin" />
                    Confirm
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog :open="deleteDialogOpen" @update:open="deleteDialogOpen = $event">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <ShieldX class="h-4 w-4" />
                    Remove passkey?
                </DialogTitle>
                <DialogDescription>
                    You will no longer be able to sign in with
                    <span class="font-medium text-foreground">
                        {{ passkeyToDelete?.name }}
                    </span>
                    on this device.
                </DialogDescription>
            </DialogHeader>

            <DialogFooter>
                <Button
                    type="button"
                    variant="secondary"
                    :disabled="loading"
                    @click="cancelDelete"
                >
                    Cancel
                </Button>
                <Button
                    type="button"
                    variant="destructive"
                    :disabled="loading"
                    @click="confirmDelete"
                >
                    <Loader2 v-if="loading" class="h-4 w-4 animate-spin" />
                    Remove
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
