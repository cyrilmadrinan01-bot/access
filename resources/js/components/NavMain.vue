<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import type { NavItem } from '@/types';

const props = defineProps<{
    items: NavItem[];
    collapsed?: boolean; // sidebar collapsed state
}>();

const page = usePage();
const openSubmenus = ref<Record<string, boolean>>({});

// Check if route is active
const isActive = (href?: string) => {
    if (!href) return false;
    const current = page.url.split('?')[0];
    return current === String(href) || current.startsWith(String(href) + '/');
};

// Toggle submenu manually
const toggleSubmenu = (title: string) => {
    openSubmenus.value[title] = !openSubmenus.value[title];
};

// Initialize submenus for active child routes
const initializeSubmenus = () => {
    props.items.forEach(item => {
        if (item.children) {
            openSubmenus.value[item.title] = item.children.some(c =>
                isActive(String(c.href))
            );
        }
    });
};

onMounted(() => initializeSubmenus());
watch(() => page.url, () => initializeSubmenus());
</script>

<template>
    <nav class="space-y-2">
        <template v-for="item in props.items" :key="item.title">

            <!-- Parent item -->
            <div v-if="item.children" class="relative group">
                <button type="button"
                    class="flex items-center w-full p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                    @click="toggleSubmenu(item.title)"
                    :class="props.collapsed ? 'justify-center' : 'justify-between gap-2'">
                    <!-- Icon always visible -->
                    <component :is="item.icon" class="w-6 h-6 flex-shrink-0" />

                    <!-- Text and badge only when expanded -->
                    <span v-show="!props.collapsed" class="ml-3 flex-1 flex justify-between items-center">
                        {{ item.title }}
                        <span v-if="item.count"
                            class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium bg-red-500 text-white rounded-full">
                            {{ item.count }}
                        </span>
                    </span>

                    <!-- Tooltip -->
                    <span v-show="props.collapsed"
                        class="absolute left-full ml-2 whitespace-nowrap px-2 py-1 text-sm font-medium rounded bg-gray-800 text-white opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                        {{ item.title }}
                    </span>


                    <!-- Arrow for submenu (hidden when collapsed) -->
                    <svg v-show="!props.collapsed" :class="{ 'rotate-90': openSubmenus[item.title] }"
                        class="w-3 h-3 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>

                <!-- Submenu items -->
                <transition name="slide-fade">
                    <div v-show="openSubmenus[item.title]" class="space-y-1 transition-all duration-300">
                        <Link v-for="child in item.children" :key="child.title" :href="String(child.href)"
                            class="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 relative group transition-colors"
                            :class="props.collapsed ? 'justify-center' : 'gap-2'">
                            <!-- Child icon always visible -->
                            <component :is="child.icon" class="w-5 h-5 flex-shrink-0" />

                            <!-- Child text hidden when collapsed -->
                            <span v-show="!props.collapsed" class="ml-4 flex-1">
                                {{ child.title }}
                            </span>

                            <!-- Badge for child -->
                            <span v-if="child.count && !props.collapsed"
                                class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium bg-red-500 text-white rounded-full">
                                {{ child.count }}
                            </span>

                            <!-- Tooltip for collapsed state -->
                            <span v-show="props.collapsed"
                                class="absolute left-full ml-2 whitespace-nowrap px-2 py-1 text-sm font-medium rounded bg-gray-800 text-white opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                                {{ item.title }}
                            </span>

                        </Link>
                    </div>
                </transition>
            </div>

            <!-- Single item (no submenu) -->
            <Link v-else :href="String(item.href)"
                class="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors relative group"
                :class="props.collapsed ? 'justify-center' : 'gap-2'">
                <component :is="item.icon" class="w-6 h-6 flex-shrink-0" />
                <span v-show="!props.collapsed" class="ml-3 flex-1">{{ item.title }}</span>

                <!-- Badge -->
                <span v-if="item.count && !props.collapsed"
                    class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium bg-red-500 text-white rounded-full">
                    {{ item.count }}
                </span>

                <!-- Tooltip -->
                <span v-show="props.collapsed"
                    class="absolute left-full ml-2 whitespace-nowrap px-2 py-1 text-sm font-medium rounded bg-gray-800 text-white opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                    {{ item.title }}
                </span>

            </Link>
        </template>
    </nav>
</template>

<style>
/* Submenu expand/collapse animation */
.slide-fade-enter-active,
.slide-fade-leave-active {
    transition: all 0.3s ease;
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    max-height: 0;
    opacity: 0;
    overflow: hidden;
}

.slide-fade-enter-to,
.slide-fade-leave-from {
    max-height: 500px;
    opacity: 1;
}
</style>
