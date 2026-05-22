<script setup lang="ts">
import AppLayout from "@/layouts/AppLayout.vue";
import { router, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import { route } from "ziggy-js";
import type { BreadcrumbItem } from "@/types";

import Button from "@/components/ui/button/Button.vue";
import Input from "@/components/ui/input/Input.vue";
import Dialog from "@/components/ui/dialog/Dialog.vue";
import DialogContent from "@/components/ui/dialog/DialogContent.vue";
import DialogHeader from "@/components/ui/dialog/DialogHeader.vue";
import DialogTitle from "@/components/ui/dialog/DialogTitle.vue";
import DialogFooter from "@/components/ui/dialog/DialogFooter.vue";

const fileInput = ref<HTMLInputElement | null>(null);
const uploadError = ref("");
const showMassAccrualModal = ref(false);
const form = ref({
  file: null as File | null,
  processing: false,
});

defineProps<{
  employees: {
    data: any[];
    links: any[];
    current_page: number;
    last_page: number;
  };
  leaveTypes: any[];
}>();

/* ---------------- Breadcrumbs ---------------- */
const breadcrumbs: BreadcrumbItem[] = [
  { title: "Timekeeping", href: "" },
  { title: "Leave Balances", href: route("admin.leave.balances.index") },
];

/* ---------------- State ---------------- */
const showAdjustModal = ref(false);
const selectedEmployee = ref<any | null>(null);

const leaveForm = useForm({
  empnum: null as string | null,
  leave_type_id: null as number | null,
  amount: "",
  remarks: "",
  file: null as File | null,
});

// New mass upload form
const massUploadForm = ref({
  file: null as File | null,
  processing: false,
});

/* ---------------- Actions ---------------- */
function openAdjust(employee: any) {
  selectedEmployee.value = employee;
  leaveForm.empnum = employee.empnum;
  leaveForm.leave_type_id = null;
  leaveForm.amount = "";
  leaveForm.remarks = "";
  showAdjustModal.value = true;
}

function submitAdjustment() {
  leaveForm.post(route("admin.leave.balances.adjust"), {
    onSuccess: () => {
      showAdjustModal.value = false;
      leaveForm.reset();
    },
    onError: (errors) => {
      console.error(errors);
    },
  });
}

// Handling file change
function handleFileUpload(event: Event) {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files[0]) {
    massUploadForm.value.file = target.files[0]; // <- notice `.value`
  }
}

// Submit (if using fetch or axios)
async function submitMassAccrual() {
  if (!massUploadForm.value.file) return;
  const data = new FormData();
  data.append("file", massUploadForm.value.file);

  massUploadForm.value.processing = true;

  await fetch("/your-endpoint", {
    method: "POST",
    body: data,
  });

  massUploadForm.value.processing = false;
  massUploadForm.value.file = null;
}

function getBalance(emp: any, typeId: number) {
  return emp.leave_balances?.[typeId]?.balance?.toFixed(2) ?? "0.00";
}

function goTo(url: string | null) {
  if (!url) return;

  router.visit(url, {
    preserveScroll: true,
    preserveState: true,
  });
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6">
      <h1 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-100">
        Leave Balances
      </h1>
      <div class="flex justify-end mb-4">
        <Button @click="showMassAccrualModal = true" size="sm" variant="outline">
          Mass Upload
        </Button>
      </div>

      <!-- TABLE -->
      <div class="bg-white dark:bg-gray-800 rounded shadow overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-100 dark:bg-gray-700">
            <tr>
              <th class="px-4 py-3 text-left">Employee</th>
              <th v-for="type in leaveTypes" :key="type.id" class="px-4 py-3 text-center">
                {{ type.code }}
              </th>
              <th class="px-4 py-3 text-center">Action</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="emp in employees.data"
              :key="emp.id"
              class="border-t dark:border-gray-700"
            >
              <td class="px-4 py-3">
                <div class="font-medium">{{ emp.name }}</div>
                <div class="text-xs text-gray-500">{{ emp.empnum }}</div>
              </td>

              <td v-for="type in leaveTypes" :key="type.id" class="px-4 py-3 text-center">
                {{ getBalance(emp, type.id) }}
              </td>

              <td class="px-4 py-3 text-center">
                <Button size="sm" @click="openAdjust(emp)"> Adjust </Button>
              </td>
            </tr>

            <tr v-if="!employees.data.length">
              <td
                :colspan="leaveTypes.length + 2"
                class="px-4 py-6 text-center text-gray-500 dark:text-gray-400"
              >
                No employees found
              </td>
            </tr>
          </tbody>
        </table>
        <!-- PAGINATION -->
        <div v-if="employees.last_page > 1" class="mt-6 flex flex-col items-center gap-3">
          <!-- Page Info -->
          <div class="text-sm text-gray-600 dark:text-gray-400">
            Page {{ employees.current_page }} of {{ employees.last_page }}
          </div>

          <!-- Controls -->
          <div class="flex items-center gap-1">
            <!-- Previous -->
            <button
              :disabled="!employees.links[0].url"
              @click="goTo(employees.links[0].url)"
              class="px-3 py-1 text-sm rounded border transition"
              :class="[
                !employees.links[0].url
                  ? 'opacity-40 cursor-not-allowed'
                  : 'hover:bg-gray-200 dark:hover:bg-gray-700',
              ]"
            >
              Prev
            </button>

            <!-- Page Numbers -->
            <button
              v-for="(link, index) in employees.links.slice(1, -1)"
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
              :disabled="!employees.links[employees.links.length - 1].url"
              @click="goTo(employees.links[employees.links.length - 1].url)"
              class="px-3 py-1 text-sm rounded border transition"
              :class="[
                !employees.links[employees.links.length - 1].url
                  ? 'opacity-40 cursor-not-allowed'
                  : 'hover:bg-gray-200 dark:hover:bg-gray-700',
              ]"
            >
              Next
            </button>
          </div>
        </div>
      </div>

      <!-- ADJUST MODAL -->
      <Dialog v-model:open="showAdjustModal">
        <DialogContent class="max-w-md">
          <DialogHeader>
            <DialogTitle>Adjust Leave Credits</DialogTitle>
            <p class="text-sm text-gray-500">
              {{ selectedEmployee?.name }}
            </p>
          </DialogHeader>

          <div class="space-y-4 mt-4">
            <div>
              <label class="text-sm">Leave Type</label>
              <select
                v-model="leaveForm.leave_type_id"
                class="w-full mt-1 rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700"
              >
                <option value="" disabled>Select leave type</option>
                <option v-for="type in leaveTypes" :key="type.id" :value="type.id">
                  {{ type.name }}
                </option>
              </select>
            </div>

            <div>
              <label class="text-sm">Amount (+ / -)</label>
              <Input v-model="leaveForm.amount" type="number" step="0.25" />
            </div>

            <div>
              <label class="text-sm">Remarks</label>
              <Input v-model="leaveForm.remarks" />
            </div>
          </div>

          <DialogFooter class="mt-6">
            <button
              type="button"
              class="rounded-md border px-4 py-2 text-sm"
              @click="showAdjustModal = false"
            >
              Cancel
            </button>

            <button
              type="button"
              class="rounded-md bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700 disabled:opacity-50"
              :disabled="form.processing"
              @click="submitAdjustment"
            >
              Save
            </button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <!-- Modal -->
      <div
        v-if="showMassAccrualModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
      >
        <div
          class="bg-white dark:bg-gray-800 rounded-lg shadow-lg w-full max-w-md p-6 relative"
        >
          <!-- Close button -->
          <button
            class="absolute top-2 right-2 text-gray-500 dark:text-gray-300 hover:text-gray-700 dark:hover:text-gray-100"
            @click="showMassAccrualModal = false"
          >
            ✕
          </button>

          <h2 class="text-lg font-medium text-gray-800 dark:text-gray-200 mb-4">
            Mass Leave Accrual
          </h2>

          <form @submit.prevent="submitMassAccrual" class="flex flex-col gap-3">
            <input
              type="file"
              accept=".csv,.xlsx"
              @change="handleFileUpload"
              class="block w-full text-sm text-gray-500 dark:text-gray-300 file:border file:rounded file:px-3 file:py-2 file:bg-gray-200 file:text-gray-700 dark:file:bg-gray-700 dark:file:text-gray-200"
              required
            />
            <p v-if="uploadError" class="text-sm text-red-600">{{ uploadError }}</p>

            <div class="flex justify-end gap-2 mt-2">
              <Button
                type="button"
                variant="secondary"
                @click="showMassAccrualModal = false"
              >
                Cancel
              </Button>
              <Button type="submit" :disabled="form.processing || !form.file">
                {{ form.processing ? "Uploading..." : "Upload" }}
              </Button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
