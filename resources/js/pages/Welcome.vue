<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import ImpersonateBanner from '@/components/ImpersonateBanner.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import KoamishinLogo from '@/components/KoamishinLogo.vue';

defineProps<{
    canRegister?: boolean;
    laravelVersion?: string;
    phpVersion?: string;
}>();

const features = [
    {
        title: 'Laravel 12',
        description:
            'The PHP framework for web artisans, providing a robust backend foundation.',
        icon: 'i-logos-laravel',
    },
    {
        title: 'Vue 3 + Inertia',
        description:
            'Build modern single-page apps using classic server-side routing patterns.',
        icon: 'i-logos-vue',
    },
    {
        title: 'Tailwind CSS 4',
        description:
            'A utility-first CSS framework for rapidly building custom user interfaces.',
        icon: 'i-logos-tailwindcss-icon',
    },
    {
        title: 'Shadcn Vue',
        description:
            'Beautifully designed components built with Radix Vue and Tailwind CSS.',
        icon: 'i-lucide-component',
    },
    {
        title: 'Fortify Auth',
        description:
            'Headless authentication backend with secure features out of the box.',
        icon: 'i-lucide-shield-check',
    },
    {
        title: 'Wayfinder + Ziggy',
        description:
            'Type-safe routing and full Ziggy compatibility for seamless navigation.',
        icon: 'i-lucide-map',
    },
];
</script>

<template>
    <Head title="Welcome" />
    <div
        class="flex min-h-screen flex-col bg-background text-foreground selection:bg-primary selection:text-primary-foreground"
    >
        <ImpersonateBanner />
        <!-- Navbar -->
        <header
            class="sticky top-0 z-50 w-full border-b border-border/40 bg-background/95 backdrop-blur-sm supports-backdrop-filter:bg-background/60"
        >
            <div
                class="container mx-auto flex h-14 max-w-7xl items-center justify-between px-6"
            >
                <div class="flex items-center gap-2">
                    <KoamishinLogo class="h-8 w-8" />
                    <span class="hidden text-lg font-bold sm:inline-block"
                        >Koamishin</span
                    >
                </div>

                <nav v-if="canRegister" class="flex items-center gap-4">
                    <template v-if="$page.props.auth.user">
                        <Button as-child variant="ghost" size="sm">
                            <Link :href="route('dashboard')">Dashboard</Link>
                        </Button>
                    </template>
                    <template v-else>
                        <Button as-child variant="ghost" size="sm">
                            <Link :href="route('login')">Log in</Link>
                        </Button>
                        <Button as-child size="sm">
                            <Link :href="route('register')">Register</Link>
                        </Button>
                    </template>
                </nav>
            </div>
        </header>

        <main class="flex-1">
            <!-- Hero Section -->
            <section class="space-y-6 pt-6 pb-8 md:pt-10 md:pb-12 lg:py-32">
                <div
                    class="container mx-auto flex max-w-7xl flex-col items-center gap-4 px-6 text-center"
                >
                    <Badge
                        variant="secondary"
                        class="rounded-full px-4 py-1 text-sm"
                    >
                        v1.0.0-beta
                    </Badge>

                    <div class="flex flex-col items-center gap-2">
                        <KoamishinLogo
                            class="mb-6 h-24 w-24 rounded-[20px] shadow-2xl shadow-primary/20"
                        />
                        <h1
                            class="bg-gradient-to-br from-foreground to-muted-foreground bg-clip-text pb-2 text-3xl font-bold tracking-tighter text-transparent sm:text-5xl md:text-6xl lg:text-7xl"
                        >
                            Koamishin Starterkit
                        </h1>
                    </div>

                    <p
                        class="max-w-[42rem] leading-normal text-muted-foreground sm:text-xl sm:leading-8"
                    >
                        The ultimate Laravel starter kit. Pre-configured with
                        the best tools in the ecosystem: Laravel 12, Vue 3,
                        Inertia, and Shadcn UI.
                    </p>

                    <div class="flex gap-4 pt-4">
                        <Button as-child size="lg" class="h-11 px-8">
                            <a href="https://laravel.com/docs" target="_blank"
                                >Get Started</a
                            >
                        </Button>
                        <Button
                            as-child
                            variant="outline"
                            size="lg"
                            class="h-11 px-8"
                        >
                            <a
                                href="https://github.com/koamishin/KoamiStarterKit"
                                target="_blank"
                                >GitHub</a
                            >
                        </Button>
                    </div>
                </div>
            </section>

            <!-- Features Grid -->
            <section
                class="container mx-auto max-w-7xl px-6 py-8 md:py-12 lg:py-24"
            >
                <div
                    class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3"
                >
                    <Card
                        v-for="feature in features"
                        :key="feature.title"
                        class="border-border bg-card transition-colors hover:bg-accent/50"
                    >
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                {{ feature.title }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <CardDescription>
                                {{ feature.description }}
                            </CardDescription>
                        </CardContent>
                    </Card>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="border-t border-border py-6 md:px-8 md:py-0">
            <div
                class="container mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-6 md:h-24 md:flex-row"
            >
                <p
                    class="text-center text-sm leading-loose text-balance text-muted-foreground md:text-left"
                >
                    Built by
                    <a
                        href="https://github.com/koamishin"
                        target="_blank"
                        class="font-medium underline underline-offset-4"
                        >Koamishin</a
                    >. The source code is available on
                    <a
                        href="https://github.com/koamishin/KoamiStarterKit"
                        target="_blank"
                        class="font-medium underline underline-offset-4"
                        >GitHub</a
                    >.
                </p>
            </div>
        </footer>
    </div>
</template>
