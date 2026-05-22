<script setup lang="ts">
import { ref, watch, onMounted, onBeforeUnmount } from "vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { router, useForm } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import type { BreadcrumbItem } from "@/types";

interface MedicalImage {
  id: number;
  url: string;
}
interface MedicalItem {
  id: number;
  reqid: string;
  empnum: string;
  empname: string;
  receiptNumber: string;
  amount: number;
  transactionDate: string;
  employeeNotes: string;
  status: string;
  images: MedicalImage[];
  payout?: string | null;
  adminNotes?: string | null;
}

const props = defineProps<{
  medical: MedicalItem;
  duplicates: any[];
}>();

const form = useForm({
  payout: props.medical.payout ?? "",
  adminNotes: props.medical.adminNotes ?? "",
});

/* =========================
   Approval Actions
========================= */

function approve() {
  //router.post(route("medical.approve", props.medical.id));
  form.post(route("medical.approve", props.medical.id));
}

function reject() {
  //router.post(route("medical.reject", props.medical.id), { adminNotes: note });
  form.post(route("medical.reject", props.medical.id));
}

function cancel() {
  router.get(route("medical.approval.index"));
}

/* =========================
   Image Modal
========================= */

const showModal = ref(false);
const selectedImage = ref("");
const zoomLevel = ref(1);

function openImage(url: string) {
  selectedImage.value = url;
  zoomLevel.value = 1;
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
}

function zoomIn() {
  zoomLevel.value += 0.2;
}

function zoomOut() {
  if (zoomLevel.value > 0.4) {
    zoomLevel.value -= 0.2;
  }
}

/* Close on ESC */
function handleEsc(e: KeyboardEvent) {
  if (e.key === "Escape") closeModal();
}

onMounted(() => window.addEventListener("keydown", handleEsc));
onBeforeUnmount(() => window.removeEventListener("keydown", handleEsc));

watch(showModal, (val) => {
  document.body.style.overflow = val ? "hidden" : "";
});

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Medical Re-imbursement", href: "medical.approval.index" },
  { title: "Details", href: route("") },
];
</script>

<template>
  <Head title="Medical Request Details" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="m-4 space-y-4">
      <!-- Header -->
      <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
          Medical Request Details
        </h1>
      </div>
      <!-- DETAILS CARD -->
      <div
        class="bg-white dark:bg-gray-900 shadow rounded-xl p-6 border border-gray-200 dark:border-gray-700"
      >
        <div class="grid grid-cols-2 gap-4 text-sm">
          <div><strong>Employee:</strong> {{ medical.reqid }}</div>
          <div><strong>Employee:</strong> {{ medical.empname }}</div>
          <div><strong>Emp #:</strong> {{ medical.empnum }}</div>
          <div><strong>Receipt #:</strong> {{ medical.receiptNumber }}</div>
          <div>
            <strong>Amount:</strong> ₱ {{ Number(medical.amount).toLocaleString() }}
          </div>
          <div><strong>Date:</strong> {{ medical.transactionDate }}</div>
          <div><strong>Status:</strong> {{ medical.status }}</div>
        </div>

        <!-- IMAGE GALLERY -->
        <div class="mt-6">
          <h3 class="font-semibold mb-2 text-gray-700 dark:text-gray-300">
            Attached Receipts
          </h3>

          <div class="flex gap-4 flex-wrap">
            <div
              v-for="img in medical.images"
              :key="img.id"
              class="cursor-pointer"
              @click="openImage(img.url)"
            >
              <img
                :src="img.url"
                class="w-32 h-32 object-cover rounded border border-gray-300 dark:border-gray-600 hover:scale-105 transition"
              />
            </div>
          </div>
        </div>
        <!-- ADMIN SECTION -->
        <div class="mt-8 border-t pt-6 border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-100">
            Admin Action
          </h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Payout Date -->
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
              >
                Payout Date
              </label>

              <input
                type="date"
                v-model="form.payout"
                class="w-full rounded-lg border px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500"
                :class="
                  form.errors.payout
                    ? 'border-red-500 focus:ring-red-500'
                    : 'border-gray-300 dark:border-gray-600'
                "
              />

              <p v-if="form.errors.payout" class="text-red-500 text-sm mt-1">
                {{ form.errors.payout }}
              </p>
            </div>

            <!-- Status Display -->
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
              >
                Current Status
              </label>
              <div
                class="px-3 py-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200"
              >
                {{ medical.status }}
              </div>
            </div>
          </div>

          <!-- Admin Notes -->
          <div class="mt-4">
            <label
              class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
            >
              Admin Notes
            </label>

            <textarea
              v-model="form.adminNotes"
              rows="4"
              class="w-full rounded-lg px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500"
              :class="
                form.errors.adminNotes
                  ? 'border border-red-500 focus:ring-red-500'
                  : 'border border-gray-300 dark:border-gray-600'
              "
              placeholder="Enter remarks / reason for approval or rejection"
            ></textarea>

            <p v-if="form.errors.adminNotes" class="text-red-500 text-sm mt-1">
              {{ form.errors.adminNotes }}
            </p>
          </div>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="flex gap-3 mt-6">
          <button
            @click="approve"
            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
          >
            Approve
          </button>
          <button
            @click="reject"
            class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700"
          >
            Reject
          </button>
          <button
            @click="cancel"
            class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600"
          >
            Return
          </button>
        </div>
      </div>

      <!-- DUPLICATES SECTION -->
      <div
        v-if="duplicates.length"
        class="bg-gray-50 dark:bg-gray-800 p-6 rounded-xl border dark:border-gray-700"
      >
        <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-100">
          Similar Receipt Numbers Found
        </h3>

        <div
          v-for="dup in duplicates"
          :key="dup.id"
          class="mb-4 p-4 bg-white dark:bg-gray-900 rounded border dark:border-gray-700"
        >
          <div class="grid grid-cols-2 gap-4 text-sm">
            <div><strong>Employee:</strong> {{ dup.reqid }}</div>
            <div><strong>Employee:</strong> {{ dup.empname }}</div>
            <div><strong>Emp #:</strong> {{ dup.empnum }}</div>
            <div><strong>Receipt #:</strong> {{ dup.receiptNumber }}</div>
            <div>
              <strong>Amount:</strong> ₱ {{ Number(dup.amount).toLocaleString() }}
            </div>
            <div><strong>Date:</strong> {{ dup.transactionDate }}</div>
            <div><strong>Status:</strong> {{ dup.status }}</div>
          </div>

          <div class="flex gap-3">
            <img
              v-for="img in dup.images"
              :key="img.id"
              :src="img.url"
              @click="openImage(img.url)"
              class="w-24 h-24 object-cover rounded border dark:border-gray-600 cursor-pointer hover:scale-105 transition"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- IMAGE MODAL -->
    <transition name="fade">
      <div
        v-if="showModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/80"
        @click.self="closeModal"
      >
        <!-- Modal Container -->
        <div class="relative w-full h-full flex items-center justify-center">
          <!-- IMAGE -->
          <img
            :src="selectedImage"
            :style="{ transform: `scale(${zoomLevel})` }"
            class="max-h-[85vh] max-w-[90vw] transition duration-200 select-none"
          />

          <!-- CLOSE BUTTON -->
          <button
            @click="closeModal"
            class="fixed top-6 right-6 z-50 bg-white dark:bg-gray-800 text-black dark:text-white shadow-lg rounded-full w-10 h-10 flex items-center justify-center hover:scale-110 transition"
          >
            ✕
          </button>

          <!-- ZOOM CONTROLS -->
          <div class="fixed bottom-6 left-1/2 -translate-x-1/2 flex gap-4 z-50">
            <button
              @click="zoomLevel += 0.2"
              class="bg-white dark:bg-gray-800 text-black dark:text-white px-4 py-2 rounded-lg shadow-lg hover:scale-105 transition"
            >
              Zoom In
            </button>

            <button
              @click="zoomLevel > 0.4 && (zoomLevel -= 0.2)"
              class="bg-white dark:bg-gray-800 text-black dark:text-white px-4 py-2 rounded-lg shadow-lg hover:scale-105 transition"
            >
              Zoom Out
            </button>
          </div>
        </div>
      </div>
    </transition>
  </AppLayout>
</template>
