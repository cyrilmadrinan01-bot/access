<script setup lang="ts">
import AppLayout from "@/layouts/AppLayout.vue";
import { Head, useForm, usePage, router } from "@inertiajs/vue3";
import { ref, watch, computed } from "vue";
import { route } from "ziggy-js";

interface Role {
  id: number;
  name: string;
  selectedPermissions: string[];
}

interface Permission {
  id: number;
  name: string;
}

const props = defineProps<{
  roles: { data: Role[] };
  permissions: Permission[];
  filters: { search?: string };
}>();

const page = usePage();

const search = ref(props.filters.search ?? "");
const flashMessage = ref<string | null>(null);

const selectedRoleId = ref<number | null>(null);

const selectedRole = computed(
  () => props.roles.data.find((r) => r.id === selectedRoleId.value) || null
);

// auto select first role
if (props.roles.data.length && !selectedRoleId.value) {
  selectedRoleId.value = props.roles.data[0].id;
}

function selectRole(role: Role) {
  selectedRoleId.value = role.id;
}

function applyFilter() {
  router.get(
    route("roles.permissions"),
    { search: search.value },
    { preserveState: true, preserveScroll: true, replace: true }
  );
}

function submit(role: Role) {
  useForm({
    permissions: role.selectedPermissions,
  }).post(route("roles.permissions.update", role.id));
}

watch(
  () => (page.props.flash as any)?.message,
  (message) => {
    flashMessage.value = message ?? null;
  },
  { immediate: true }
);
</script>

<template>
  <Head title="Role Permissions" />

  <AppLayout>
    <div class="max-w-6xl mx-auto p-6 text-gray-900 dark:text-gray-100">
      <!-- Flash -->
      <div
        v-if="flashMessage"
        class="mb-4 rounded-lg px-4 py-3 text-sm bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-300"
      >
        {{ flashMessage }}
      </div>

      <!-- Search -->
      <div class="flex justify-between mb-6">
        <h1 class="text-xl font-semibold">Role Permissions</h1>

        <input
          v-model="search"
          @keyup.enter="applyFilter"
          placeholder="Search roles..."
          class="px-3 py-2 text-sm rounded-lg border bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-blue-500 outline-none"
        />
      </div>

      <!-- 2 COLUMN LAYOUT -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- LEFT: ROLES -->
        <div class="border rounded-xl p-2 border-gray-200 dark:border-gray-800">
          <h2 class="text-sm font-medium mb-2 px-2 text-gray-500">Roles</h2>

          <div class="space-y-1">
            <button
              v-for="role in roles.data"
              :key="role.id"
              @click="selectRole(role)"
              class="w-full text-left px-3 py-2 rounded-lg text-sm hover:bg-gray-100 dark:hover:bg-gray-800 transition"
              :class="{
                'bg-gray-100 dark:bg-gray-800 font-medium': role.id === selectedRoleId,
              }"
            >
              {{ role.name }}
            </button>
          </div>
        </div>

        <!-- RIGHT: PERMISSIONS -->
        <div
          class="md:col-span-2 border rounded-xl p-4 border-gray-200 dark:border-gray-800"
        >
          <div v-if="selectedRole" class="space-y-4">
            <div class="flex justify-between items-center">
              <h2 class="font-semibold text-lg">{{ selectedRole.name }} Permissions</h2>

              <button
                @click="submit(selectedRole)"
                class="px-4 py-2 text-xs rounded-lg bg-blue-600 hover:bg-blue-700 text-white"
              >
                Save Changes
              </button>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
              <label
                v-for="permission in permissions"
                :key="permission.id"
                class="flex items-center gap-2 text-sm cursor-pointer"
              >
                <input
                  type="checkbox"
                  :value="permission.name"
                  v-model="selectedRole.selectedPermissions"
                  class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500"
                />

                <span class="truncate">
                  {{ permission.name }}
                </span>
              </label>
            </div>
          </div>

          <div v-else class="text-gray-500 text-sm">
            Select a role to manage permissions
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
