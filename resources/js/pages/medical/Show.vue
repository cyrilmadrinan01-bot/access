<script setup lang="ts">
import { ref } from "vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { Head, usePage, Link } from "@inertiajs/vue3";
import Label from "@/components/ui/label/Label.vue";
import { route } from "ziggy-js";

const medical = usePage().props.medical as {
  reqid: string;
  empnum: string;
  empname: string;
  receiptNumber: string;
  amount: number;
  transactionDate: string;
  images: string[];
  employeeNotes: string;
};

const zoomLevel = ref(1);
const zoomImage = ref<HTMLImageElement | null>(null);

const zoom = (event: WheelEvent) => {
  event.preventDefault();
  const delta = event.deltaY > 0 ? -0.1 : 0.1;
  zoomLevel.value = Math.min(Math.max(zoomLevel.value + delta, 0.5), 3);
  if (zoomImage.value) {
    zoomImage.value.style.transform = `scale(${zoomLevel.value})`;
  }
};

// Modal state
const showModal = ref(false);
const currentImage = ref("");

// Open modal
const openModal = (img: string) => {
  currentImage.value = img;
  showModal.value = true;
};

// Close modal
const closeModal = () => {
  showModal.value = false;
  currentImage.value = "";
};
const breadcrumbs = [
  { title: "Medical Re-imbursement", href: route("medical") },
  { title: "View Details", href: "#" },
];
</script>
<style>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>

<template>
  <Head title="View Medical Reimbursement" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="p-4 w-8/12 space-y-6">
        <!-- EMP NUMBER -->
        <div>
          <Label>Request ID</Label>
          <div class="mt-1 p-2 border rounded">
            {{ medical.reqid }}
          </div>
        </div>

        <!-- EMP NUMBER -->
        <div>
          <Label>Employee Number</Label>
          <div class="mt-1 p-2 border rounded">
            {{ medical.empnum }}
          </div>
        </div>

        <!-- EMP NAME -->
        <div>
          <Label>Employee Name</Label>
          <div class="mt-1 p-2 border rounded">
            {{ medical.empname }}
          </div>
        </div>

        <!-- RECEIPT NUMBER -->
        <div>
          <Label>Official Receipt Number</Label>
          <div class="mt-1 p-2 border rounded">
            {{ medical.receiptNumber }}
          </div>
        </div>

        <!-- AMOUNT -->
        <div>
          <Label>Amount</Label>
          <div class="mt-1 p-2 border rounded">
            ₱ {{ Number(medical.amount).toFixed(2) }}
          </div>
        </div>

        <!-- DATE -->
        <div>
          <Label>Transaction Date</Label>
          <div class="mt-1 p-2 border rounded">
            {{ medical.transactionDate }}
          </div>
        </div>

        <!-- NOTES -->
        <div>
          <Label>Employee Notes</Label>
          <textarea
            class="mt-1 w-full p-2 border rounded whitespace-pre-line"
            :value="medical.employeeNotes"
            disabled
            rows="4"
          ></textarea>
        </div>

        <div class="flex justify-start mt-6 p-4 w-8/12 space-y-6">
          <Link
            :href="route('medical')"
            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded shadow text-gray-700"
          >
            Close
          </Link>
        </div>
      </div>
      <!-- RIGHT SIDE — RECEIPT IMAGE PREVIEW -->
      <div class="p-4 w-8/12 space-y-6">
        <Label>Receipt Images</Label>
        <div
          v-for="img in medical.images"
          :key="img"
          class="border rounded p-2 bg-white shadow-sm cursor-pointer"
          @click="openModal(img)"
        >
          <img
            :src="`/storage/${img}`"
            class="w-full h-40 object-cover rounded"
            alt="Receipt image"
          />
        </div>
      </div>
    </div>
    <transition name="fade">
      <div
        v-if="showModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75"
        @click.self="closeModal"
      >
        <div class="relative max-w-[90%] max-h-[90%] overflow-hidden">
          <!-- Close Button -->
          <button
            @click="closeModal"
            class="absolute top-2 right-2 text-white text-2xl font-bold z-50"
          >
            &times;
          </button>

          <!-- Image with zoom -->
          <img
            :src="`/storage/${currentImage}`"
            class="w-full h-auto max-h-[90vh] object-contain cursor-zoom-in"
            @wheel.prevent="zoom($event)"
            ref="zoomImage"
          />
        </div>
      </div>
    </transition>
  </AppLayout>
</template>
