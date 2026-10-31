<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    Check,
    CheckCircle2,
    Loader2,
    Server,
    Shield,
    Sliders,
    XCircle,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Requirement {
    name: string;
    current: string;
    satisfied: boolean;
}

const props = defineProps<{
    requirements: Record<string, Requirement>;
    defaultSiteName: string;
    timezones: string[];
    systemTimezone: string;
}>();

const step = ref<1 | 2 | 3>(1);

const form = useForm({
    site_name: props.defaultSiteName,
    site_description: 'Self-hosted application instance',
    timezone: props.systemTimezone || 'UTC',
    admin_name: '',
    admin_email: '',
    admin_password: '',
    admin_password_confirmation: '',
});

const allSatisfied = computed(() =>
    Object.values(props.requirements).every((req) => req.satisfied),
);

const submit = () => {
    form.post('/setup');
};
</script>

<template>
    <Head title="Self-Hosted Setup Wizard" />
    <div
        class="flex min-h-screen flex-col items-center justify-center bg-background p-4 text-foreground sm:p-6 lg:p-8"
    >
        <div class="w-full max-w-2xl">
            <!-- Header -->
            <div class="mb-8 text-center">
                <div
                    class="mb-3 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary"
                >
                    <Server class="h-6 w-6" />
                </div>
                <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">
                    Self-Hosted Setup Wizard
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Configure your instance and initialize the first Super Admin
                    account.
                </p>
            </div>

            <!-- Step Tracker -->
            <div
                class="mb-8 flex items-center justify-between border-b border-border pb-4"
            >
                <div class="flex items-center gap-2">
                    <span
                        class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-semibold"
                        :class="
                            step >= 1
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-muted text-muted-foreground'
                        "
                    >
                        1
                    </span>
                    <span class="text-sm font-medium">Readiness</span>
                </div>
                <div class="mx-4 h-0.5 flex-1 bg-border" />
                <div class="flex items-center gap-2">
                    <span
                        class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-semibold"
                        :class="
                            step >= 2
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-muted text-muted-foreground'
                        "
                    >
                        2
                    </span>
                    <span class="text-sm font-medium">Instance</span>
                </div>
                <div class="mx-4 h-0.5 flex-1 bg-border" />
                <div class="flex items-center gap-2">
                    <span
                        class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-semibold"
                        :class="
                            step === 3
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-muted text-muted-foreground'
                        "
                    >
                        3
                    </span>
                    <span class="text-sm font-medium">Super Admin</span>
                </div>
            </div>

            <!-- Step 1: System Readiness -->
            <div
                v-if="step === 1"
                class="rounded-xl border border-border bg-card p-6 shadow-sm"
            >
                <div class="mb-4 flex items-center gap-2">
                    <Sliders class="h-5 w-5 text-primary" />
                    <h2 class="text-lg font-semibold">
                        Environment & System Checks
                    </h2>
                </div>
                <p class="mb-4 text-xs text-muted-foreground">
                    Verify that your server meets the operational requirements
                    before initializing the instance.
                </p>

                <div class="space-y-3">
                    <div
                        v-for="(req, key) in requirements"
                        :key="key"
                        class="flex items-center justify-between rounded-lg border border-border bg-muted/30 p-3 text-sm"
                    >
                        <div class="flex items-center gap-3">
                            <CheckCircle2
                                v-if="req.satisfied"
                                class="h-5 w-5 shrink-0 text-emerald-500"
                            />
                            <XCircle
                                v-else
                                class="h-5 w-5 shrink-0 text-rose-500"
                            />
                            <span class="font-medium">{{ req.name }}</span>
                        </div>
                        <span class="font-mono text-xs text-muted-foreground">
                            {{ req.current }}
                        </span>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <Button
                        type="button"
                        :disabled="!allSatisfied"
                        @click="step = 2"
                        class="gap-2"
                    >
                        Continue to Instance Setup
                        <ArrowRight class="h-4 w-4" />
                    </Button>
                </div>
            </div>

            <!-- Step 2 & 3 Form -->
            <form
                v-else
                @submit.prevent="submit"
                class="rounded-xl border border-border bg-card p-6 shadow-sm"
            >
                <!-- Step 2: Instance Details -->
                <div v-if="step === 2" class="space-y-4">
                    <div class="mb-2 flex items-center gap-2">
                        <Sliders class="h-5 w-5 text-primary" />
                        <h2 class="text-lg font-semibold">Instance Details</h2>
                    </div>
                    <p class="mb-4 text-xs text-muted-foreground">
                        Configure public identification and time display.
                    </p>

                    <div>
                        <Label for="site_name">Site Name</Label>
                        <Input
                            id="site_name"
                            v-model="form.site_name"
                            required
                            class="mt-1"
                            placeholder="My Awesome App"
                        />
                        <InputError
                            :message="form.errors.site_name"
                            class="mt-1"
                        />
                    </div>

                    <div>
                        <Label for="site_description">Site Description</Label>
                        <Input
                            id="site_description"
                            v-model="form.site_description"
                            class="mt-1"
                            placeholder="Describe your self-hosted instance"
                        />
                        <InputError
                            :message="form.errors.site_description"
                            class="mt-1"
                        />
                    </div>

                    <div>
                        <Label for="timezone">Default Timezone</Label>
                        <select
                            id="timezone"
                            v-model="form.timezone"
                            class="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        >
                            <option
                                v-for="tz in timezones"
                                :key="tz"
                                :value="tz"
                                class="bg-popover text-popover-foreground"
                            >
                                {{ tz }}
                            </option>
                        </select>
                        <InputError
                            :message="form.errors.timezone"
                            class="mt-1"
                        />
                    </div>

                    <div class="mt-6 flex justify-between pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="step = 1"
                            class="gap-2"
                        >
                            <ArrowLeft class="h-4 w-4" />
                            Back
                        </Button>
                        <Button type="button" @click="step = 3" class="gap-2">
                            Next: Create Super Admin
                            <ArrowRight class="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                <!-- Step 3: Super Admin Account -->
                <div v-if="step === 3" class="space-y-4">
                    <div class="mb-2 flex items-center gap-2">
                        <Shield class="h-5 w-5 text-primary" />
                        <h2 class="text-lg font-semibold">
                            Super Admin Account
                        </h2>
                    </div>
                    <p class="mb-4 text-xs text-muted-foreground">
                        This account will hold absolute administrative control
                        over the instance.
                    </p>

                    <div>
                        <Label for="admin_name">Full Name</Label>
                        <Input
                            id="admin_name"
                            v-model="form.admin_name"
                            required
                            class="mt-1"
                            placeholder="Admin Operator"
                        />
                        <InputError
                            :message="form.errors.admin_name"
                            class="mt-1"
                        />
                    </div>

                    <div>
                        <Label for="admin_email">Email Address</Label>
                        <Input
                            id="admin_email"
                            type="email"
                            v-model="form.admin_email"
                            required
                            class="mt-1"
                            placeholder="admin@example.com"
                        />
                        <InputError
                            :message="form.errors.admin_email"
                            class="mt-1"
                        />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <Label for="admin_password">Password</Label>
                            <Input
                                id="admin_password"
                                type="password"
                                v-model="form.admin_password"
                                required
                                class="mt-1"
                                placeholder="Minimum 8 characters"
                            />
                            <InputError
                                :message="form.errors.admin_password"
                                class="mt-1"
                            />
                        </div>

                        <div>
                            <Label for="admin_password_confirmation">
                                Confirm Password
                            </Label>
                            <Input
                                id="admin_password_confirmation"
                                type="password"
                                v-model="form.admin_password_confirmation"
                                required
                                class="mt-1"
                                placeholder="Re-enter password"
                            />
                        </div>
                    </div>

                    <div class="mt-6 flex justify-between pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="step = 2"
                            :disabled="form.processing"
                            class="gap-2"
                        >
                            <ArrowLeft class="h-4 w-4" />
                            Back
                        </Button>
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="gap-2"
                        >
                            <Loader2
                                v-if="form.processing"
                                class="h-4 w-4 animate-spin"
                            />
                            <Check v-else class="h-4 w-4" />
                            {{
                                form.processing
                                    ? 'Initializing Instance...'
                                    : 'Complete Setup & Launch'
                            }}
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>
