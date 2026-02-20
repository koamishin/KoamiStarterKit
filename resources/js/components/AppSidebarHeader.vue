<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import {
    Popover,
    PopoverTrigger,
    PopoverContent,
} from '@/components/ui/popover';
import {
    index as getNotifications,
    markAsRead,
    markAllRead,
} from '@/routes/notifications';
import type { BreadcrumbItem } from '@/types';
import { ref, onMounted } from 'vue';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

interface Notification {
    id: string;
    type: string;
    data: Record<string, unknown>;
    read_at: string | null;
    created_at: string;
}

const notifications = ref<Notification[]>([]);
const unreadCount = ref(0);
const isOpen = ref(false);

const fetchNotifications = async () => {
    try {
        const response = await fetch(getNotifications().url, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (response.ok) {
            const data = await response.json();
            notifications.value = data.notifications;
            unreadCount.value = data.unread_count;
        }
    } catch (error) {
        console.error('Failed to fetch notifications:', error);
    }
};

const handleMarkAsRead = async (id: string) => {
    try {
        await fetch(markAsRead({ id }).url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const notification = notifications.value.find((n) => n.id === id);
        if (notification) {
            notification.read_at = new Date().toISOString();
        }
        unreadCount.value = Math.max(0, unreadCount.value - 1);
    } catch (error) {
        console.error('Failed to mark notification as read:', error);
    }
};

const handleMarkAllAsRead = async () => {
    try {
        await fetch(markAllRead().url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        notifications.value.forEach((n) => {
            if (!n.read_at) {
                n.read_at = new Date().toISOString();
            }
        });
        unreadCount.value = 0;
    } catch (error) {
        console.error('Failed to mark all notifications as read:', error);
    }
};

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays < 7) return `${diffDays}d ago`;

    return date.toLocaleDateString();
};

const getNotificationTitle = (notification: Notification) => {
    if (notification.data?.title) {
        return notification.data.title;
    }
    if (notification.data?.message) {
        return notification.data.message;
    }
    return 'Notification';
};

const getNotificationDescription = (notification: Notification) => {
    if (notification.data?.message) {
        return notification.data.message;
    }
    return '';
};

onMounted(() => {
    fetchNotifications();
});
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <Popover v-model:open="isOpen">
            <PopoverTrigger as-child>
                <button
                    class="relative flex h-9 w-9 items-center justify-center rounded-md text-sidebar-foreground transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" />
                        <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" />
                    </svg>
                    <span
                        v-if="unreadCount > 0"
                        class="absolute -top-0.5 -right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-medium text-white"
                    >
                        {{ unreadCount > 9 ? '9+' : unreadCount }}
                    </span>
                </button>
            </PopoverTrigger>
            <PopoverContent align="end" class="w-80 p-0" :side-offset="4">
                <div
                    class="flex items-center justify-between border-b px-4 py-3"
                >
                    <h4 class="text-sm font-semibold">Notifications</h4>
                    <button
                        v-if="unreadCount > 0"
                        class="text-xs text-primary hover:underline"
                        @click="handleMarkAllAsRead"
                    >
                        Mark all as read
                    </button>
                </div>

                <div
                    v-if="notifications.length === 0"
                    class="px-4 py-8 text-center text-sm text-muted-foreground"
                >
                    No notifications yet
                </div>

                <div v-else class="max-h-80 overflow-y-auto">
                    <button
                        v-for="notification in notifications"
                        :key="notification.id"
                        class="flex w-full items-start gap-3 px-4 py-3 text-left transition-colors hover:bg-accent"
                        :class="{ 'bg-accent/50': !notification.read_at }"
                        @click="handleMarkAsRead(notification.id)"
                    >
                        <div class="mt-0.5">
                            <div
                                class="h-2 w-2 rounded-full"
                                :class="
                                    notification.read_at
                                        ? 'bg-muted-foreground/30'
                                        : 'bg-primary'
                                "
                            />
                        </div>
                        <div class="flex-1 space-y-1">
                            <p class="text-sm leading-none font-medium">
                                {{ getNotificationTitle(notification) }}
                            </p>
                            <p
                                v-if="getNotificationDescription(notification)"
                                class="text-xs text-muted-foreground"
                            >
                                {{ getNotificationDescription(notification) }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ formatDate(notification.created_at) }}
                            </p>
                        </div>
                    </button>
                </div>
            </PopoverContent>
        </Popover>
    </header>
</template>
