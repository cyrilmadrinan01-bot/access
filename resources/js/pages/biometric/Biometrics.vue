<!-- resources/js/Pages/biometric/Biometrics.vue -->
<template>
  <div
    class="min-h-screen bg-gray-100 dark:bg-gray-900 flex items-center justify-center p-6"
  >
    <div
      class="w-full max-w-6xl bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden"
    >
      <!-- HEADER -->
      <div class="bg-blue-700 text-white p-8 text-center">
        <h1 class="text-4xl font-bold tracking-wide">Employee Attendance System</h1>

        <p class="mt-2 text-blue-100 text-lg">
          {{ currentDate }}
        </p>

        <p class="text-5xl font-extrabold mt-3 tracking-widest">
          {{ currentTime }}
        </p>
      </div>

      <!-- BODY -->
      <div class="grid md:grid-cols-2 gap-10 p-10">
        <!-- LEFT -->
        <div>
          <label class="block text-sm font-semibold text-gray-500 mb-2">
            Scan Employee ID
          </label>

          <input
            ref="scannerInput"
            v-model="barcode"
            @keyup.enter="submitScan"
            type="text"
            placeholder="Enter Employee Number"
            class="w-full border-2 border-gray-300 rounded-xl px-5 py-5 text-2xl focus:ring-4 focus:ring-blue-300 outline-none"
          />

          <div class="mt-6 text-sm text-gray-500">Scanner Ready...</div>

          <!-- Loading -->
          <div v-if="loading" class="mt-8 flex items-center gap-3 text-blue-600">
            <div
              class="animate-spin h-6 w-6 rounded-full border-4 border-blue-600 border-t-transparent"
            ></div>
            Processing Attendance...
          </div>
        </div>

        <!-- RIGHT -->
        <div>
          <transition name="fade">
            <div
              v-if="employee"
              class="border rounded-2xl p-8 shadow bg-gray-50 dark:bg-gray-700"
            >
              <div class="flex gap-6 items-center">
                <!-- PHOTO -->
                <div class="w-28 h-28 rounded-xl overflow-hidden border bg-white">
                  <img
                    v-if="employee.photo"
                    :src="employee.photo"
                    class="w-full h-full object-cover"
                  />
                  <div
                    v-else
                    class="w-full h-full flex items-center justify-center text-xs text-gray-400"
                  >
                    No Image
                  </div>
                </div>

                <!-- INFO -->
                <div class="flex-1">
                  <h2 class="text-2xl font-bold text-gray-800 dark:text-white">
                    {{ employee.name }}
                  </h2>

                  <p class="text-gray-500 mt-1">Employee #: {{ employee.empnum }}</p>

                  <p class="mt-2 font-semibold text-green-600">
                    {{ employee.statusMessage }}
                  </p>

                  <p class="text-sm text-gray-500 mt-1">
                    {{ employee.time }}
                  </p>
                </div>
              </div>
            </div>
          </transition>

          <!-- ERROR -->
          <transition name="fade">
            <div
              v-if="errorMessage"
              class="border border-red-300 bg-red-50 text-red-700 rounded-xl p-6"
            >
              {{ errorMessage }}
            </div>
          </transition>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";

const barcode = ref("");
const loading = ref(false);
const employee = ref(null);
const errorMessage = ref("");
const scannerInput = ref(null);

const currentDate = ref("");
const currentTime = ref("");

let hideTimer = null;

/* CLOCK */
function startClock() {
  setInterval(() => {
    const now = new Date();

    currentDate.value = now.toLocaleDateString("en-US", {
      weekday: "long",
      month: "long",
      day: "numeric",
      year: "numeric",
    });

    currentTime.value = now.toLocaleTimeString();
  }, 1000);
}

/* SOUND */
function successSound() {
  new Audio("/sounds/success.mp3").play();
}

function errorSound() {
  new Audio("/sounds/error.mp3").play();
}

/* RESET */
function resetScreen() {
  hideTimer = setTimeout(() => {
    employee.value = null;
    errorMessage.value = "";
    barcode.value = "";
    scannerInput.value.focus();
  }, 3000);
}

/* SUBMIT */
async function submitScan() {
  if (!barcode.value.trim()) return;

  loading.value = true;
  employee.value = null;
  errorMessage.value = "";

  if (hideTimer) clearTimeout(hideTimer);

  try {
    const res = await axios.post("/api/biometric/scan", {
      empnum: barcode.value,
    });

    employee.value = {
      ...res.data.data,
      statusMessage: res.data.message,
      time: new Date().toLocaleTimeString(),
    };

    successSound();
  } catch (err) {
    errorMessage.value = err.response?.data?.message || "Attendance failed.";

    errorSound();
  }

  loading.value = false;
  barcode.value = "";
  scannerInput.value.focus();

  resetScreen();
}

onMounted(() => {
  startClock();
  scannerInput.value.focus();
});
</script>

<style>
.fade-enter-active,
.fade-leave-active {
  transition: all 0.4s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(15px);
}
</style>
