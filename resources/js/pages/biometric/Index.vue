<template>
  <div class="min-h-screen p-6 dark:bg-gray-900 dark:text-gray-200 
              flex justify-center items-center">

    <div class="flex flex-col items-center">

      <h1 class="text-4xl md:text-5xl font-extrabold mb-12 text-center dark:text-white">
        Employee Clock In / Clock Out
      </h1>

      <!-- CENTER WRAPPER -->
      <div class="flex flex-col md:flex-row justify-center items-start gap-12">

        <!-- LEFT: Scanner -->
        <div :class="['scanner-container max-w-md transition-all duration-500', { 'scanner-move': showEmployeeInfo }]">
          <div class="relative w-full overflow-hidden">
            <video ref="video" class="w-full border rounded dark:border-gray-700" autoplay playsinline></video>

            <canvas ref="overlay" class="absolute top-0 left-0 w-full h-full pointer-events-none z-20"></canvas>
          </div>

          <!-- Barcode result -->
          <input v-model="result" type="text" class="mt-4 w-full p-3 border rounded bg-white dark:bg-gray-800 
                   dark:border-gray-700 dark:text-gray-200" placeholder="Scanned barcode appears here" />
        </div>

        <!-- RIGHT: Database Record -->
        <transition name="slide-zoom-left">
          <div v-if="showEmployeeInfo" class="p-6 border rounded bg-white shadow dark:bg-gray-800 
            dark:border-gray-700 dark:text-gray-100 w-130">

            <h2 class="font-bold text-xl mb-4 border-b pb-2 dark:border-gray-700">
              Employee Information
            </h2>

            <!-- Loading -->
            <div v-if="isLoading" class="flex flex-col items-center justify-center p-6">
              <div class="animate-spin rounded-full h-12 w-12 border-4 border-blue-500 border-t-transparent"></div>
              <p class="mt-4 text-blue-500 dark:text-blue-300 font-semibold">Loading...</p>
            </div>

            <!-- No Record Found -->
            <div v-else-if="scannedData === false"
              class="p-4 text-center bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded">
              <p class="font-semibold text-lg">No Record Found</p>
              <p class="text-sm mt-1">Please contact your system administrator.</p>
            </div>

            <!-- Record Exists -->
            <div v-else-if="scannedData && scannedData.empnum" class="space-y-6">

            <!-- Display Laravel error or success messages -->
            <div v-if="scannedData.errorMessage" class="p-2 bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300 rounded">
              {{ scannedData.errorMessage }}
            </div>
            <div v-else-if="scannedData.message" class="p-2 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 rounded">
              {{ scannedData.message }}
            </div>

              <!-- Details -->
              <div class="space-y-3">
                <div class="flex justify-between">
                  <span class="font-semibold dark:text-gray-300">Employee #:</span>
                  <span>{{ scannedData.empnum }}</span>
                </div>

                <div class="flex justify-between">
                  <span class="font-semibold dark:text-gray-300">Employee Name:</span>
                  <span>{{ scannedData.name }}</span>
                </div>

                <div class="flex justify-between">
                  <span class="font-semibold dark:text-gray-300">Status:</span>
                  <span :class="{
                    'text-green-600 dark:text-green-400': scannedData.employeeStatus === 'Active',
                    'text-red-600 dark:text-red-400': scannedData.employeeStatus === 'Inactive'
                  }">
                    {{ scannedData.employeeStatus }}
                  </span>
                </div>

                <div class="flex justify-between">
                  <span class="font-semibold dark:text-gray-300">{{ scannedData.deviceType }}</span>
                  <span>{{ formatDateTime("full") }}</span>
                </div>
              </div>

              <!-- Image Placeholder -->
              <div class="flex justify-center items-center">
                <div class="w-28 h-28 rounded border dark:border-gray-600 bg-gray-200 
                  dark:bg-gray-700 flex items-center justify-center overflow-hidden">
                  <img v-if="scannedData.photo" :src="scannedData.photo" alt="Employee Photo"
                    class="w-full h-full object-cover" />
                  <span v-else class="text-gray-500 dark:text-gray-300 text-xs text-center">
                    No Image<br />Available
                  </span>
                </div>
              </div>

            </div>
          </div>
        </transition>
      </div> <!-- END CENTER WRAPPER -->

    </div>
  </div>
</template>



<script setup>
import { ref, watch, onMounted, onUnmounted, nextTick } from "vue";
import { BrowserMultiFormatReader } from "@zxing/browser";
import axios from "axios";

/* -----------------------------------------------------
   CORE STATE
----------------------------------------------------- */
const result = ref("");
const scannedData = ref(null);
const isLoading = ref(false);
const isRequesting = ref(false);   // API is processing
const showEmployeeInfo = ref(false);
const localIP = ref("Unknown");

const video = ref(null);
const overlay = ref(null);

let hideTimeout = null;
let lastScan = 0;

/* -----------------------------------------------------
   SOUND EFFECTS
----------------------------------------------------- */
//const successBeep = new Audio("/sounds/success.mp3");
//const errorBeep = new Audio("/sounds/error.mp3");

/* -----------------------------------------------------
   FORMATTERS
----------------------------------------------------- */
function formatDateTime(type = "full") {
  const now = new Date();
  const pad = (n) => String(n).padStart(2, "0");
  const y = now.getFullYear();
  const m = pad(now.getMonth() + 1);
  const d = pad(now.getDate());
  const h = pad(now.getHours());
  const i = pad(now.getMinutes());
  const s = pad(now.getSeconds());

  if (type === "date") return `${y}-${m}-${d}`;
  if (type === "time") return `${h}:${i}:${s}`;
  return `${y}-${m}-${d} ${h}:${i}:${s}`;
}

const getDayName = () =>
  new Date().toLocaleString("en-US", { weekday: "long" });

/* -----------------------------------------------------
   LOCAL IP DETECTION
----------------------------------------------------- */
async function getLocalIP() {
  return new Promise((resolve) => {
    try {
      const pc = new RTCPeerConnection({
        iceServers: [{ urls: "stun:stun.l.google.com:19302" }],
      });

      let address = null;

      pc.onicecandidate = (event) => {
        if (!event.candidate) {
          pc.close();
          resolve(address || "Unknown");
          return;
        }
        const match =
          event.candidate.candidate.match(/(\b\d{1,3}(\.\d{1,3}){3}\b)/);
        if (match) address = match[1];
      };

      pc.createDataChannel("x");
      pc.createOffer().then((offer) => pc.setLocalDescription(offer));
    } catch {
      resolve("Unknown");
    }
  });
}

/* -----------------------------------------------------
   WATCH: BARCODE VALUE
----------------------------------------------------- */
watch(result, async (barcode) => {
  if (!barcode) return;
  if (isRequesting.value) return; // API processing → ignore new scans.

  barcode = barcode.trim().replace(/"/g, "");

  console.log("BARCODE READ:", barcode);
  console.log("BARCODE READ:", localIP.value);

  // Lock scanning
  isRequesting.value = true;
  isLoading.value = true;
  showEmployeeInfo.value = true;
  scannedData.value = null;

  if (hideTimeout) clearTimeout(hideTimeout);

  try {
    // LOOKUP EMPLOYEE
    const lookup = await fetch(`/api/biometric/${encodeURIComponent(barcode)}/${localIP.value}`, {
      method: "GET",
      cache: "no-store",
    });

    const record = lookup.ok ? await lookup.json() : null;
    console.log("API RECORD:", record);

    if (record && record.empnum) {
      //successBeep.play();
      scannedData.value = record;

      // SAVE LOG
      try {
        const logResponse = await axios.post("/api/biometric/scan", {
          empnum: record.empnum,
          timeLog: formatDateTime("full"),
          deviceIp: localIP.value,
          dayName: getDayName(),
          dated: formatDateTime("date"),
          processed: "No",
          logType: record.deviceType // "Clock In" or "Clock Out"
        });

        if (logResponse.data.status === "success") {
          console.log("Success:", logResponse.data.message);
          // Optionally show success message inline
          scannedData.value.message = logResponse.data.message;
        }

      } catch (err) {
        // 422 validation errors or custom Laravel exceptions
        if (err.response && err.response.data) {
          const msg = err.response.data.message || "An error occurred.";
          scannedData.value.errorMessage = msg;
          //errorBeep.play();
          console.error("Biometric error:", msg);
        } else {
          scannedData.value.errorMessage = "Unexpected server error.";
          console.error(err);
        }
      }
    } else {
      //errorBeep.play();
      scannedData.value = false;
    }

    // Pause 2 sec to let user see the message
    await new Promise((resolve) => setTimeout(resolve, 5000));

    // Auto-hide
    hideTimeout = setTimeout(() => {
      showEmployeeInfo.value = false;
      scannedData.value = null;
    }, 5000);
  } catch (e) {
    console.error("ERROR:", e);
    errorBeep.play();
  } finally {
    isLoading.value = false;
    isRequesting.value = false;
    result.value = ""; // Clear scanned text
  }
});

/* -----------------------------------------------------
   BARCODE SCANNER ENGINE
----------------------------------------------------- */
const codeReader = new BrowserMultiFormatReader();
let cropCanvas, cropCtx;

// ROI auto-tracking
let roi = { x: 0.175, y: 0.325, width: 0.65, height: 0.35 };
const centerROI = { ...roi };

let lastDetectionTime = Date.now();
const resetDelay = 1500;

onMounted(async () => {
  localIP.value = await getLocalIP();

  const stream = await navigator.mediaDevices.getUserMedia({
    video: { facingMode: "environment", width: { ideal: 1280 } },
  });
  video.value.srcObject = stream;
  await video.value.play();

  const overlayCtx = overlay.value.getContext("2d");

  cropCanvas = document.createElement("canvas");
  cropCtx = cropCanvas.getContext("2d");

  function resizeCanvas() {
    overlay.value.width = video.value.videoWidth;
    overlay.value.height = video.value.videoHeight;
  }
  resizeCanvas();
  window.addEventListener("resize", resizeCanvas);

  async function scanLoop() {
    const vw = video.value.videoWidth;
    const vh = video.value.videoHeight;
    if (!vw || !vh) return requestAnimationFrame(scanLoop);

    // Draw ROI Box (Green if active, Red if API locked)
    overlayCtx.clearRect(0, 0, vw, vh);
    overlayCtx.strokeStyle = isRequesting.value ? "#ff0000" : "#00ff00";
    overlayCtx.lineWidth = 4;
    overlayCtx.setLineDash([12, 8]);

    const boxX = roi.x * vw;
    const boxY = roi.y * vh;
    const boxW = roi.width * vw;
    const boxH = roi.height * vh;

    overlayCtx.strokeRect(boxX, boxY, boxW, boxH);

    if (!isRequesting.value) {
      // Crop video for decoding
      const targetW = 400;
      const targetH = Math.floor((boxH / boxW) * targetW);
      cropCanvas.width = targetW;
      cropCanvas.height = targetH;

      cropCtx.drawImage(
        video.value,
        boxX,
        boxY,
        boxW,
        boxH,
        0,
        0,
        targetW,
        targetH
      );

      try {
        const decoded = await codeReader.decodeFromCanvas(cropCanvas);

        if (decoded) {
          const now = Date.now();
          if (now - lastScan > 1400) {
            lastScan = now;
            result.value = decoded.getText();
            lastDetectionTime = now;

            // update ROI
            if (decoded.resultPoints?.length) {
              const pts = decoded.resultPoints;
              const minX = Math.min(...pts.map((p) => p.x));
              const minY = Math.min(...pts.map((p) => p.y));
              const maxX = Math.max(...pts.map((p) => p.x));
              const maxY = Math.max(...pts.map((p) => p.y));

              roi = {
                x: Math.max(minX / targetW - 0.05, 0),
                y: Math.max(minY / targetH - 0.05, 0),
                width: Math.min((maxX - minX) / targetW + 0.1, 1),
                height: Math.min((maxY - minY) / targetH + 0.1, 1),
              };
            }
          }
        }
      } catch {
        // ignore decode errors
      }
    }

    // Reset ROI if idle
    const now = Date.now();
    if (now - lastDetectionTime > resetDelay) {
      const t = 0.06;
      roi.x += (centerROI.x - roi.x) * t;
      roi.y += (centerROI.y - roi.y) * t;
      roi.width += (centerROI.width - roi.width) * t;
      roi.height += (centerROI.height - roi.height) * t;
    }

    requestAnimationFrame(scanLoop);
  }

  scanLoop();
});

onUnmounted(() => {
  codeReader.reset();
});
</script>


<style>
video,
canvas {
  transform: scaleX(-1);
  /* Mirror for UX */
}

.scanner-container {
  max-width: 480px;
  margin: 0 auto;
}

/* Slide + Zoom from left */
.slide-zoom-left-enter-active,
.slide-zoom-left-leave-active {
  transition: all 0.5s ease;
}

.slide-zoom-left-enter-from,
.slide-zoom-left-leave-to {
  opacity: 0;
  transform: translateX(-50px) scale(0.8);
  /* start from left and smaller */
}

.slide-zoom-left-enter-to,
.slide-zoom-left-leave-from {
  opacity: 1;
  transform: translateX(0) scale(1);
  /* final position and normal size */
}

/* Base scanner container has transition already */
.scanner-container {
  max-width: 480px;
  margin: 0 auto;
  transition: all 0.5s ease;
}

/* Move slightly and scale down to fit with Employee Info */
.scanner-move {
  transform: translateX(-20px) scale(0.95);
}
</style>
