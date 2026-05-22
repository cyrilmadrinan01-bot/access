<script setup lang="ts">
import AppLayout from "@/layouts/AppLayout.vue";
import { Head, useForm, usePage, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import { route } from "ziggy-js";
import Alert from "@/components/ui/alert/Alert.vue";
import { Info } from "lucide-vue-next";
import type { BreadcrumbItem } from "@/types";

interface User {
  id: number;
  name: string;
  selectedRoles: string[];
}

interface Role {
  id: number;
  name: string;
}

const props = defineProps<{
  users: {
    data: User[];
    links: any[];
    current_page: number;
    last_page: number;
  };
  roles: Role[];
  filters: {
    search?: string;
  };
}>();

const page = usePage();

const search = ref(props.filters?.search ?? "");
const flashMessage = ref<string | null>(null);

function applyFilter() {
  router.get(
    route("users.roles"),
    { search: search.value },
    {
      preserveState: true,
      preserveScroll: true,
    }
  );
}

function goTo(url: string | null) {
  if (!url) return;

  router.visit(url, {
    preserveScroll: true,
    preserveState: true,
  });
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Users", href: "" },
  { title: "Roles", href: route("users.roles") },
];

// Submit updated roles for a user
const submit = (user: User) => {
  useForm({ roles: user.selectedRoles }).post(route("users.roles.update", user.id), {
    preserveScroll: true,
  });
};

// Watch for flash messages (auto-dismiss)
watch(
  () => (page.props.flash as { message?: string })?.message,
  (newMessage) => {
    flashMessage.value = newMessage ?? null;
    if (newMessage) setTimeout(() => (flashMessage.value = null), 5000);
  },
  { immediate: true }
);
</script>

<template>
  <Head title="User Roles" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="m-4 space-y-4">
      <!-- Flash message -->
      <Alert v-if="flashMessage">
        <template #description>
          <div class="flex items-center space-x-2">
            <Info class="w-5 h-5 text-blue-600" />
            <span>{{ flashMessage }}</span>
          </div>
        </template>
      </Alert>

      <div
        class="rounded-lg border bg-white dark:bg-gray-800 dark:border-gray-700 p-6 shadow-sm transition-colors"
      >
        <h1 class="text-xl font-bold mb-4 text-gray-900 dark:text-gray-100">
          User Roles
        </h1>

        <div class="mb-4 flex items-center gap-2">
          <input
            v-model="search"
            type="text"
            placeholder="Search user..."
            class="border rounded px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600"
            @keyup.enter="applyFilter"
          />
          <button
            @click="applyFilter"
            class="px-3 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 text-sm"
          >
            Search
          </button>
        </div>
        <table class="w-full border-collapse">
          <thead>
            <tr
              class="border-b bg-gray-50 dark:bg-gray-700 text-left text-gray-700 dark:text-gray-200"
            >
              <th class="p-2">User</th>
              <th class="p-2">Roles</th>
              <th class="p-2 w-32"></th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="user in users.data"
              :key="user.id"
              class="border-b hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
              <td class="p-2 font-medium text-gray-800 dark:text-gray-100">
                {{ user.name }}
              </td>

              <td class="p-2">
                <label
                  v-for="role in roles"
                  :key="role.id"
                  class="mr-4 inline-flex items-center space-x-1"
                >
                  <input
                    type="checkbox"
                    class="rounded border-gray-300 dark:border-gray-500"
                    :value="role.name"
                    v-model="user.selectedRoles"
                  />
                  <span class="text-gray-800 dark:text-gray-200">
                    {{ role.name }}
                  </span>
                </label>
              </td>

              <td class="p-2">
                <button
                  class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 transition-colors"
                  @click="submit(user)"
                >
                  Save
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="users.last_page > 1" class="mt-6 flex flex-col items-center gap-3">
          <div class="text-sm text-gray-600 dark:text-gray-400">
            Page {{ users.current_page }} of {{ users.last_page }}
          </div>

          <div class="flex items-center gap-1">
            <!-- Prev -->
            <button
              :disabled="!users.links[0].url"
              @click="goTo(users.links[0].url)"
              class="px-3 py-1 text-sm rounded border transition"
              :class="[
                !users.links[0].url
                  ? 'opacity-40 cursor-not-allowed'
                  : 'hover:bg-gray-200 dark:hover:bg-gray-700',
              ]"
            >
              Prev
            </button>

            <!-- Numbers -->
            <button
              v-for="(link, index) in users.links.slice(1, -1)"
              :key="index"
              v-html="link.label"
              @click="goTo(link.url)"
              :disabled="!link.url"
              class="px-3 py-1 text-sm rounded border transition min-w-[36px]"
              :class="[
                link.active
                  ? 'bg-blue-600 text-white border-blue-600 shadow'
                  : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200',
                !link.url
                  ? 'opacity-40 cursor-not-allowed'
                  : 'hover:bg-gray-200 dark:hover:bg-gray-700',
              ]"
            />

            <!-- Next -->
            <button
              :disabled="!users.links[users.links.length - 1].url"
              @click="goTo(users.links[users.links.length - 1].url)"
              class="px-3 py-1 text-sm rounded border transition"
              :class="[
                !users.links[users.links.length - 1].url
                  ? 'opacity-40 cursor-not-allowed'
                  : 'hover:bg-gray-200 dark:hover:bg-gray-700',
              ]"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
