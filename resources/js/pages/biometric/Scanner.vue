<template>
  <div class="scanner-container p-6 relative">
    <h1 class="text-xl font-bold mb-4">ID Scanner</h1>

    <div class="relative w-full max-w-md mx-auto overflow-hidden mb-4">
      <video ref="video" class="w-full border rounded" autoplay playsinline></video>
      <canvas ref="overlay" class="absolute top-0 left-0 w-full h-full pointer-events-none z-20"></canvas>
    </div>

    <!-- Product Info -->
    <div v-if="product" class="p-3 bg-green-100 rounded mb-2">
      <p><strong>Name:</strong> {{ product.name }}</p>
      <p><strong>Description:</strong> {{ product.description }}</p>
    </div>

    <!-- Last scans -->
    <div v-if="lastScans.length" class="p-3 bg-blue-100 rounded">
      <p class="font-bold mb-1">Last scans:</p>
      <ul>
        <li v-for="scan in lastScans" :key="scan.id">
          {{ new Date(scan.scanned_at).toLocaleString() }}
        </li>
      </ul>
    </div>

    <!-- Error Message -->
    <div v-if="error" class="p-3 bg-red-100 rounded mt-2">
      {{ error }}
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from "vue"
import { BrowserMultiFormatReader } from "@zxing/browser"
import axios from "axios"

const video = ref(null)
const overlay = ref(null)
const product = ref(null)
const lastScans = ref([])
const error = ref(null)

const codeReader = new BrowserMultiFormatReader()
let cropCanvas, cropCtx

// ROI for adaptive overlay
let roi = { x: 0.175, y: 0.325, width: 0.65, height: 0.35 }
const centerROI = { ...roi }
let lastDetectionTime = Date.now()
const resetDelay = 1500

// Store last scanned barcode to prevent duplicate consecutive scans
let lastScannedBarcode = null
let lastScanTime = 0

const submitBarcode = async (barcodeValue) => {
  if (!barcodeValue.trim()) return

  const now = Date.now()
  // Prevent scanning same barcode within 3 seconds
  if (barcodeValue === lastScannedBarcode && now - lastScanTime < 3000) return

  lastScannedBarcode = barcodeValue
  lastScanTime = now

  try {
    const response = await axios.post('/scan', { barcode: barcodeValue })
    product.value = response.data.product
    lastScans.value = response.data.lastScans
    error.value = null
  } catch (err) {
    error.value = err.response?.data?.error || "Unknown error"
    product.value = null
    lastScans.value = []
  }
}

// Scanner setup
onMounted(async () => {
  const stream = await navigator.mediaDevices.getUserMedia({
    video: { facingMode: "environment", width: { ideal: 1280 }, height: { ideal: 720 } }
  })
  video.value.srcObject = stream
  await video.value.play()

  const overlayCtx = overlay.value.getContext("2d")
  cropCanvas = document.createElement("canvas")
  cropCtx = cropCanvas.getContext("2d")

  function resizeCanvas() {
    overlay.value.width = video.value.videoWidth
    overlay.value.height = video.value.videoHeight
  }
  resizeCanvas()
  window.addEventListener("resize", resizeCanvas)

  async function scanLoop() {
    const vw = video.value.videoWidth
    const vh = video.value.videoHeight
    if (!vw || !vh) return requestAnimationFrame(scanLoop)

    // Draw overlay box
    overlayCtx.clearRect(0, 0, vw, vh)
    const boxX = roi.x * vw
    const boxY = roi.y * vh
    const boxW = roi.width * vw
    const boxH = roi.height * vh
    overlayCtx.strokeStyle = "#00ff00"
    overlayCtx.lineWidth = 4
    overlayCtx.setLineDash([12, 8])
    overlayCtx.strokeRect(boxX, boxY, boxW, boxH)

    // Crop ROI for decoding
    const targetW = 400
    const targetH = Math.floor((boxH / boxW) * targetW)
    cropCanvas.width = targetW
    cropCanvas.height = targetH
    cropCtx.drawImage(video.value, boxX, boxY, boxW, boxH, 0, 0, targetW, targetH)

    try {
      const decoded = await codeReader.decodeFromCanvas(cropCanvas)
      if (decoded) {
        submitBarcode(decoded.getText())
        lastDetectionTime = Date.now()

        // Update ROI to follow barcode
        if (decoded.resultPoints?.length) {
          const points = decoded.resultPoints
          const minX = Math.min(...points.map(p => p.x))
          const minY = Math.min(...points.map(p => p.y))
          const maxX = Math.max(...points.map(p => p.x))
          const maxY = Math.max(...points.map(p => p.y))

          roi = {
            x: Math.max(minX / targetW - 0.05, 0),
            y: Math.max(minY / targetH - 0.05, 0),
            width: Math.min((maxX - minX) / targetW + 0.1, 1),
            height: Math.min((maxY - minY) / targetH + 0.1, 1)
          }
        }
      }
    } catch (e) {
      // ignore decode errors
    }

    // Gradually reset ROI if no detection
    const now = Date.now()
    if (now - lastDetectionTime > resetDelay) {
      const resetSpeed = 0.05
      roi.x += (centerROI.x - roi.x) * resetSpeed
      roi.y += (centerROI.y - roi.y) * resetSpeed
      roi.width += (centerROI.width - roi.width) * resetSpeed
      roi.height += (centerROI.height - roi.height) * resetSpeed
    }

    requestAnimationFrame(scanLoop)
  }

  scanLoop()
})

onUnmounted(() => {
  codeReader.reset()
})
</script>

<style>
video,
canvas {
  transform: scaleX(-1);
}
.scanner-container {
  max-width: 480px;
  margin: 0 auto;
}
</style>
