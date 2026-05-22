<script setup lang="ts">
import AppLayout from "@/layouts/AppLayout.vue";
import { router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import { route } from "ziggy-js";
import type { BreadcrumbItem } from "@/types";

interface Device {
  id: number;
  deviceName: string;
  deviceType: string;
  deviceIp: string;
  location: string;
  status: "active" | "inactive";
}

const props = defineProps<{ devices: Device[] }>();

const activeTab = ref<"active" | "inactive">("active");
const selected = ref<Device | null>(null);

const activeDevices = computed(() => props.devices.filter((d) => d.status === "active"));
const inactiveDevices = computed(() =>
  props.devices.filter((d) => d.status === "inactive")
);

// Edit Device
const editDevice = ref<any | null>(null);
const editError = ref("");

// Open Edit Modal
function openEdit(device: any) {
  editDevice.value = { ...device }; // shallow copy to avoid mutating table directly
  editError.value = "";
}

// Close Edit Modal
function closeEditModal() {
  editDevice.value = null;
  editError.value = "";
}

// Save edited device
function updateDevice() {
  editError.value = "";

  if (
    !editDevice.value.deviceName.trim() ||
    !editDevice.value.deviceType.trim() ||
    !editDevice.value.deviceIp.trim() ||
    !editDevice.value.location.trim()
  ) {
    editError.value = "All fields are required.";
    return;
  }

  router.put(`/admin/devices/${editDevice.value.id}`, editDevice.value, {
    onSuccess: () => {
      closeEditModal();
    },
    onError: (errors) => {
      editError.value = errors.device || "Failed to update device.";
    },
  });
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Admin", href: "" },
  { title: "Devices", href: "" },
];

function open(device: Device) {
  selected.value = device;
}

function close() {
  selected.value = null;
}

function activate(device: Device) {
  router.post(`/admin/devices/${device.id}/activate`, {}, { onSuccess: close });
}

function deactivate(device: Device) {
  router.post(`/admin/devices/${device.id}/deactivate`, {}, { onSuccess: close });
}

// Add Device modal
const showAddModal = ref(false);
const newDevice = ref({
  deviceName: "",
  deviceType: "",
  deviceIp: "",
  location: "",
});

const addError = ref("");

function closeAddModal() {
  showAddModal.value = false;
  addError.value = "";
  newDevice.value = { deviceName: "", deviceType: "", deviceIp: "", location: "" };
}

function saveDevice() {
  addError.value = "";

  // simple validation
  if (
    !newDevice.value.deviceName.trim() ||
    !newDevice.value.deviceType.trim() ||
    !newDevice.value.deviceIp.trim() ||
    !newDevice.value.location.trim()
  ) {
    addError.value = "All fields are required.";
    return;
  }

  router.post("/admin/devices", newDevice.value, {
    onSuccess: () => {
      closeAddModal();
    },
    onError: (errors) => {
      addError.value = errors.device || "Failed to save device.";
    },
  });
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 text-gray-800 dark:text-gray-200">
      <h1 class="text-xl font-semibold mb-4">Device Management</h1>

      <!-- TABS -->
      <div class="flex border-b border-gray-200 dark:border-gray-700 mb-4">
        <button
          class="px-4 py-2 transition"
          :class="
            activeTab === 'active'
              ? 'border-b-2 border-green-600 text-green-600'
              : 'text-gray-500 dark:text-gray-400'
          "
          @click="activeTab = 'active'"
        >
          Active
        </button>

        <button
          class="px-4 py-2 transition"
          :class="
            activeTab === 'inactive'
              ? 'border-b-2 border-red-600 text-red-600'
              : 'text-gray-500 dark:text-gray-400'
          "
          @click="activeTab = 'inactive'"
        >
          Inactive
        </button>
      </div>
      <!-- ADD DEVICE BUTTON -->
      <div class="flex justify-end mb-4 gap-2">
        <button
          class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700"
          @click="showAddModal = true"
        >
          + Add Device
        </button>
        <button
          class="px-4 py-2 rounded-lg bg-blue-500 text-white hover:bg-blue-600"
          @click="router.visit(route('device.assignment'))"
        >
          Device Assignment
        </button>
      </div>

      <!-- TABLE -->
      <div
        class="rounded shadow overflow-x-auto bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700"
      >
        <table class="w-full text-sm">
          <thead class="bg-gray-100 dark:bg-gray-800">
            <tr>
              <th class="p-3 text-left">Name</th>
              <th class="p-3 text-left">Type</th>
              <th class="p-3 text-left">IP</th>
              <th class="p-3 text-left">Location</th>
              <th class="p-3 text-center">Action</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="d in activeTab === 'active' ? activeDevices : inactiveDevices"
              :key="d.id"
              class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition"
            >
              <td class="p-3">{{ d.deviceName }}</td>
              <td class="p-3">{{ d.deviceType }}</td>
              <td class="p-3">{{ d.deviceIp }}</td>
              <td class="p-3">{{ d.location }}</td>
              <td class="p-3 text-center">
                <button
                  class="text-blue-600 dark:text-blue-400 hover:underline"
                  @click="open(d)"
                >
                  Manage
                </button>
                |
                <button
                  class="text-blue-600 dark:text-blue-400 hover:underline"
                  @click="openEdit(d)"
                >
                  Edit
                </button>
              </td>
            </tr>

            <tr
              v-if="
                !(activeTab === 'active' ? activeDevices.length : inactiveDevices.length)
              "
            >
              <td colspan="5" class="p-6 text-center text-gray-500 dark:text-gray-400">
                No devices found
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- MODAL -->
      <div
        v-if="selected"
        class="fixed inset-0 bg-black/40 dark:bg-black/60 flex items-center justify-center z-50"
        @click.self="close"
      >
        <div
          class="w-full max-w-md rounded-xl shadow-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700"
        >
          <!-- HEADER -->
          <div
            class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center"
          >
            <div>
              <h2 class="font-semibold">{{ selected.deviceName }}</h2>
              <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ selected.deviceIp }}
              </p>
            </div>

            <span
              class="px-3 py-1 text-xs rounded-full font-medium"
              :class="
                selected.status === 'active'
                  ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
                  : 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300'
              "
            >
              {{ selected.status.toUpperCase() }}
            </span>
          </div>

          <!-- BODY -->
          <div class="p-4 text-sm space-y-2">
            <p><strong>Type:</strong> {{ selected.deviceType }}</p>
            <p><strong>Location:</strong> {{ selected.location }}</p>
          </div>

          <!-- FOOTER -->
          <div
            class="p-4 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center"
          >
            <button
              class="text-sm text-gray-600 dark:text-gray-400 hover:underline"
              @click="close"
            >
              Cancel
            </button>

            <div class="flex gap-2">
              <button
                v-if="selected.status === 'active'"
                class="px-4 py-2 rounded text-white bg-red-600 hover:bg-red-700"
                @click="deactivate(selected)"
              >
                Deactivate
              </button>

              <button
                v-if="selected.status === 'inactive'"
                class="px-4 py-2 rounded text-white bg-green-600 hover:bg-green-700"
                @click="activate(selected)"
              >
                Activate
              </button>
            </div>
          </div>
        </div>
      </div>
      <!-- ADD DEVICE MODAL -->
      <div
        v-if="showAddModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 dark:bg-black/60"
        @click.self="closeAddModal"
      >
        <div
          class="w-full max-w-md bg-white dark:bg-gray-900 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700"
        >
          <div
            class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center"
          >
            <h2 class="font-semibold text-gray-800 dark:text-gray-100">Add New Device</h2>
            <button
              class="text-gray-500 dark:text-gray-400 hover:underline"
              @click="closeAddModal"
            >
              Cancel
            </button>
          </div>

          <div class="p-4 space-y-3 text-sm text-gray-800 dark:text-gray-200">
            <div>
              <label class="block text-xs font-medium mb-1">Device Name</label>
              <input
                v-model="newDevice.deviceName"
                type="text"
                class="w-full px-3 py-2 border rounded-lg dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100"
              />
            </div>
            <div>
              <label class="block text-xs font-medium mb-1">Device Type</label>
              <select
                v-model="newDevice.deviceType"
                class="w-full px-3 py-2 border rounded-lg dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100"
              >
                <option value="Clock In">Clock In</option>
                <option value="Clock Out">Clock Out</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium mb-1">Device IP</label>
              <input
                v-model="newDevice.deviceIp"
                type="text"
                class="w-full px-3 py-2 border rounded-lg dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100"
              />
            </div>
            <div>
              <label class="block text-xs font-medium mb-1">Location</label>
              <input
                v-model="newDevice.location"
                type="text"
                class="w-full px-3 py-2 border rounded-lg dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100"
              />
            </div>

            <div v-if="addError" class="p-2 text-sm text-red-700 bg-red-100 rounded">
              {{ addError }}
            </div>
          </div>

          <div
            class="p-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-2"
          >
            <button
              class="px-4 py-2 rounded-lg bg-gray-300 dark:bg-gray-700 hover:bg-gray-400 dark:hover:bg-gray-600"
              @click="closeAddModal"
            >
              Cancel
            </button>
            <button
              class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700"
              @click="saveDevice"
            >
              Save
            </button>
          </div>
        </div>
      </div>
      <!-- EDIT DEVICE MODAL -->
      <div
        v-if="editDevice"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 dark:bg-black/60"
        @click.self="closeEditModal"
      >
        <div
          class="w-full max-w-md bg-white dark:bg-gray-900 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700"
        >
          <div
            class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center"
          >
            <h2 class="font-semibold text-gray-800 dark:text-gray-100">Edit Device</h2>
            <button
              class="text-gray-500 dark:text-gray-400 hover:underline"
              @click="closeEditModal"
            >
              Cancel
            </button>
          </div>

          <div class="p-4 space-y-3 text-sm text-gray-800 dark:text-gray-200">
            <div>
              <label class="block text-xs font-medium mb-1">Device Name</label>
              <input
                v-model="editDevice.deviceName"
                type="text"
                class="w-full px-3 py-2 border rounded-lg dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100"
              />
            </div>
            <div>
              <label class="block text-xs font-medium mb-1">Device Type</label>
              <select
                v-model="editDevice.deviceType"
                class="w-full px-3 py-2 border rounded-lg dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100"
              >
                <option value="Clock In">Clock In</option>
                <option value="Clock Out">Clock Out</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium mb-1">Device IP</label>
              <input
                v-model="editDevice.deviceIp"
                type="text"
                class="w-full px-3 py-2 border rounded-lg dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100"
              />
            </div>
            <div>
              <label class="block text-xs font-medium mb-1">Location</label>
              <input
                v-model="editDevice.location"
                type="text"
                class="w-full px-3 py-2 border rounded-lg dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100"
              />
            </div>

            <div v-if="editError" class="p-2 text-sm text-red-700 bg-red-100 rounded">
              {{ editError }}
            </div>
          </div>

          <div
            class="p-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-2"
          >
            <button
              class="px-4 py-2 rounded-lg bg-gray-300 dark:bg-gray-700 hover:bg-gray-400 dark:hover:bg-gray-600"
              @click="closeEditModal"
            >
              Cancel
            </button>
            <button
              class="px-4 py-2 rounded-lg bg-green-600 text-white hover:bg-green-700"
              @click="updateDevice"
            >
              Save
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
