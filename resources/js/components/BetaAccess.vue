<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { FlaskConical } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Switch } from '@/components/ui/switch';

const props = defineProps<{
    enabled: boolean;
}>();

const form = useForm({
    enabled: props.enabled,
});

const onToggle = (value: boolean) => {
    form.transform(() => ({ enabled: value }));
    form.patch(route('profile.beta.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <section
        class="rounded-xl border border-border bg-card p-5"
        aria-labelledby="beta-access-title"
    >
        <div class="flex items-start justify-between gap-6">
            <div class="space-y-2">
                <div class="flex items-center gap-2">
                    <FlaskConical
                        class="size-4 text-amber-600 dark:text-amber-400"
                        aria-hidden="true"
                    />
                    <h2 id="beta-access-title" class="font-medium">
                        Early access program
                    </h2>
                    <span
                        class="rounded border px-1.5 py-0.5 font-mono text-[10px] uppercase tracking-wide"
                    >
                        Beta
                    </span>
                </div>
                <p class="max-w-lg text-sm text-muted-foreground">
                    Try upcoming features before their stable release.
                    Experimental features may change. You can leave anytime;
                    stable features stay available.
                </p>
                <p class="text-xs text-muted-foreground">
                    {{
                        enabled
                            ? 'Enrolled in beta · Eligible features appear automatically'
                            : 'Stable experience · Beta features remain off'
                    }}
                </p>
            </div>
            <Switch
                aria-label="Opt in to beta access"
                :checked="enabled"
                :disabled="form.processing"
                @update:checked="onToggle"
            />
        </div>
        <InputError :message="form.errors.enabled" class="mt-2" />
    </section>
</template>
