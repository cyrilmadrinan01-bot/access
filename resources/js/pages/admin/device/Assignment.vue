<script setup lang="ts">
import { ref, watch } from "vue";
import { Head, useForm } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import AppLayout from "@/layouts/AppLayout.vue";
import { type BreadcrumbItem } from '@/types'

const props = defineProps<{
  users: { name: string; empnum: string }[];
  devices: { id: number; deviceName: string }[];
}>();

type AssignedUser = {
  empnum: string;
  name: string;
};

const selectedDevice = ref<number | null>(null);
const selectedLeft = ref<string[]>([]);
const selectedRight = ref<string[]>([]);
const assignedUsers = ref<AssignedUser[]>([]);

/* --------------------------------
 * Fetch assigned users
 * -------------------------------- */
const fetchAssignedUsers = async () => {
  if (!selectedDevice.value) {
    assignedUsers.value = [];
    return;
  }

  const res = await fetch(route("device.assignment.users", selectedDevice.value));
  assignedUsers.value = await res.json();
};

watch(selectedDevice, fetchAssignedUsers);

/* --------------------------------
 * Inertia Forms
 * -------------------------------- */
const assignForm = useForm<{
  device_id: number | null;
  users: string[];
}>({
  device_id: null,
  users: [],
});

const removeForm = useForm<{
  device_id: number | null;
  users: string[];
}>({
  device_id: null,
  users: [],
});

/* --------------------------------
 * Actions
 * -------------------------------- */
const assignUsers = () => {
  if (!selectedDevice.value) return;

  assignForm.device_id = selectedDevice.value;
  assignForm.users = selectedLeft.value;

  assignForm.post(route("device.assignment.assign"), {
    onSuccess: async () => {
      selectedLeft.value = [];
      await fetchAssignedUsers();
    },
  });
};

const removeUsers = () => {
  if (!selectedDevice.value) return;

  removeForm.device_id = selectedDevice.value;
  removeForm.users = selectedRight.value;

  removeForm.post(route("device.assignment.remove"), {
    onSuccess: async () => {
      selectedRight.value = [];
      await fetchAssignedUsers();
    },
  });
};

/* --------------------------------
 * Computed Helpers
 * -------------------------------- */
const unassignedUsers = () =>
  props.users.filter((u) => !assignedUsers.value.some((a) => a.empnum === u.empnum));

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '#' },
    { title: 'Device', href: route('devices.index') },
    { title: 'Device Assignment', href: '#' },
]
</script>

<template>
  <Head title="Device Assignment" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4 flex flex-col gap-4">
      <h1 class="text-2xl font-bold mb-4">Device Enrollment</h1>
      <div class="grid grid-cols-1 md:grid-cols-[1fr_auto_1fr] gap-10 p-6">
        <!-- LEFT: USERS -->
        <div
          class="border rounded p-4 bg-white text-gray-900 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-700"
        >
          <h3 class="font-semibold mb-2">Users</h3>
          <br />

          <select
            multiple
            v-model="selectedLeft"
            :disabled="!selectedDevice"
            class="w-full h-64 border rounded bg-white text-gray-900 dark:bg-gray-800 dark:text-gray-100 dark:border-gray-600 disabled:opacity-50"
          >
            <option v-for="u in unassignedUsers()" :key="u.empnum" :value="u.empnum">
              {{ u.name }}
            </option>
          </select>
        </div>

        <!-- MIDDLE: ACTIONS -->
        <div class="flex flex-col items-center justify-center gap-3">
          <button
            @click="assignUsers"
            :disabled="!selectedDevice || !selectedLeft.length"
            class="w-10 h-10 rounded-full bg-blue-600 text-white hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 disabled:opacity-40"
          >
            →
          </button>

          <button
            @click="removeUsers"
            :disabled="!selectedDevice || !selectedRight.length"
            class="w-10 h-10 rounded-full bg-red-600 text-white hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 disabled:opacity-40"
          >
            ←
          </button>
        </div>

        <!-- RIGHT: DEVICE + ASSIGNED -->
        <div
          class="border rounded p-4 bg-white text-gray-900 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-700"
        >
          <h3 class="font-semibold mb-2">Device</h3>

          <select
            v-model="selectedDevice"
            class="w-full border rounded mb-3 bg-white text-gray-900 dark:bg-gray-800 dark:text-gray-100 dark:border-gray-600"
          >
            <option :value="null" disabled>Select Device</option>
            <option v-for="d in devices" :key="d.id" :value="d.id">
              {{ d.deviceName }}
            </option>
          </select>

          <h4 class="font-medium mb-2">Assigned Users</h4>

          <select
            multiple
            v-model="selectedRight"
            :disabled="!selectedDevice"
            class="w-full h-56 border rounded bg-white text-gray-900 dark:bg-gray-800 dark:text-gray-100 dark:border-gray-600 disabled:opacity-50"
          >
            <option v-for="u in assignedUsers" :key="u.empnum" :value="u.empnum">
              {{ u.name }}
            </option>
          </select>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
