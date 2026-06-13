<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

type Post = {
    id: number;
    title: string;
    slug: string;
    created_at: string | null;
};

defineProps<{
    posts: Post[];
}>();
</script>

<template>
    <Head title="Posts" />
    <div class="mx-auto max-w-3xl px-4 py-8">
        <h1 class="mb-6 text-2xl font-semibold">Posts</h1>
        <ul v-if="posts.length" class="divide-y divide-gray-200 rounded-md border border-gray-200 bg-white">
            <li
                v-for="post in posts"
                :key="post.id"
                class="flex items-center justify-between px-4 py-3"
            >
                <div>
                    <p class="font-medium text-gray-900">{{ post.title }}</p>
                    <p class="text-sm text-gray-500">/{{ post.slug }}</p>
                </div>
                <time
                    v-if="post.created_at"
                    :datetime="post.created_at"
                    class="text-xs text-gray-400"
                >
                    {{ new Date(post.created_at).toLocaleDateString() }}
                </time>
            </li>
        </ul>
        <p v-else class="text-sm text-gray-500">No posts yet.</p>
    </div>
</template>
