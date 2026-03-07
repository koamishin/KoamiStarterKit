<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, toRefs } from 'vue';
import { toast } from 'vue-sonner';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type SharedData } from '@/types';
import { edit, update } from '@/routes/profile';

const page = usePage<SharedData>();
const user = page.props.auth.user as any;
const settingsFeatures = page.props.settingsFeatures || {};

const form = useForm({
    name: user.name,
    email: user.email,
});

const photoInput = ref<HTMLInputElement | null>(null);
const photoPreview = ref<string | null>(user.profile_photo_url || null);
const photoForm = useForm({
    photo: null as File | null,
});

const submit = () => {
    form.patch(update().url, {
        preserveScroll: true,
        onSuccess: () => toast.success('Profile information updated'),
        onError: () => toast.error('Failed to update profile information'),
    });
};

const resendVerification = () => {
    toast.promise(
        new Promise((resolve, reject) => {
            form.post(send().url, {
                onSuccess: () => resolve('Verification email sent'),
                onError: () => reject('Failed to resend verification email'),
            });
        }),
        {
            loading: 'Sending verification email...',
            success:
                'A new verification link has been sent to your email address.',
            error: 'Failed to send verification link. Please try again.',
        },
    );
};

const selectNewPhoto = () => {
    photoInput.value?.click();
};

const updatePhotoPreview = () => {
    const file = photoInput.value?.files?.[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            photoPreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const send = () => {
    return { url: '/email/verification-notification' };
};

const storePhoto = () => {
    if (photoInput.value?.files?.[0]) {
        const formData = new FormData();
        formData.append('photo', photoInput.value.files[0]);

        photoForm.post('/settings/profile/photo', {
            preserveScroll: true,
            onSuccess: () => {
                photoPreview.value = null;
                toast.success('Profile photo updated');
                window.location.reload();
            },
            onError: () => {
                toast.error('Failed to upload photo');
            },
        });
    }
};

const deletePhoto = () => {
    photoForm.delete('/settings/profile/photo', {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Profile photo removed');
            window.location.reload();
        },
        onError: () => {
            toast.error('Failed to remove photo');
        },
    });
};

const breadcrumbs = [{ title: 'Profile settings', href: edit().url }];

const props = defineProps<{
    mustVerifyEmail: boolean;
    status?: string;
}>();

const { mustVerifyEmail, status } = toRefs(props);
</script>

<template>
    <AppLayout title="Profile settings" :breadcrumbs="breadcrumbs">
        <Head title="Profile settings" />

        <SettingsLayout>
            <div v-if="!settingsFeatures.profile" class="py-12 text-center">
                <p class="text-muted-foreground">This page is not available.</p>
            </div>

            <div v-else class="space-y-6">
                <Heading
                    title="Profile information"
                    description="Update your profile photo and personal information"
                />

                <div class="flex items-center gap-x-6">
                    <img
                        v-if="photoPreview"
                        :src="photoPreview"
                        alt="Photo preview"
                        class="h-24 w-24 rounded-full object-cover"
                    />
                    <img
                        v-else-if="user.profile_photo_url"
                        :src="user.profile_photo_url"
                        alt="Current photo"
                        class="h-24 w-24 rounded-full object-cover"
                    />
                    <div
                        v-else
                        class="flex h-24 w-24 items-center justify-center rounded-full bg-muted"
                    >
                        <span
                            class="text-2xl font-medium text-muted-foreground"
                        >
                            {{ user.name?.charAt(0)?.toUpperCase() || 'U' }}
                        </span>
                    </div>

                    <div class="flex gap-x-4">
                        <button
                            type="button"
                            @click="selectNewPhoto"
                            class="rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-gray-300 ring-inset hover:bg-gray-50"
                        >
                            {{
                                user.profile_photo_url
                                    ? 'Change photo'
                                    : 'Upload photo'
                            }}
                        </button>
                        <button
                            v-if="user.profile_photo_url"
                            type="button"
                            @click="deletePhoto"
                            class="rounded-md px-2.5 py-1.5 text-sm font-semibold text-gray-900"
                        >
                            Remove
                        </button>
                    </div>

                    <input
                        ref="photoInput"
                        type="file"
                        class="hidden"
                        accept="image/*"
                        @change="updatePhotoPreview"
                    />
                </div>

                <div v-if="photoPreview" class="flex gap-x-3">
                    <Button @click="storePhoto">Save Photo</Button>
                    <Button variant="outline" @click="photoPreview = null"
                        >Cancel</Button
                    >
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            required
                            autocomplete="name"
                            placeholder="Full name"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            v-model="form.email"
                            required
                            autocomplete="username"
                            placeholder="Email address"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div
                        v-if="
                            mustVerifyEmail && user.email_verified_at === null
                        "
                    >
                        <p class="-mt-4 text-sm text-muted-foreground">
                            Your email address is unverified.
                            <button
                                type="button"
                                @click="resendVerification"
                                class="cursor-pointer text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            >
                                Click here to resend the verification email.
                            </button>
                        </p>

                        <div
                            v-if="status === 'verification-link-sent'"
                            class="mt-2 text-sm font-medium text-green-600"
                        >
                            A new verification link has been sent to your email
                            address.
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="form.processing">Save</Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-if="form.recentlySuccessful"
                                class="text-sm text-neutral-600"
                            >
                                Saved
                            </p>
                        </Transition>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
