<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()

const flash = computed(() => page.props.flash as {
  success?: string
  error?: string
})

const visible = ref(false)
const message = ref('')
let timeout: number | null = null

watch(
  () => [flash.value?.success, flash.value?.error],
  ([success, error]) => {
    if (success || error) {
      message.value = success ?? error ?? ''
      visible.value = true

      // Clear previous timeout
      if (timeout) clearTimeout(timeout)

      // Auto-hide after 5s
      timeout = window.setTimeout(() => {
        visible.value = false
      }, 10000)
    }
  },
  { immediate: true }
)
</script>

<template>
  <transition name="fade">
    <div
      v-if="visible"
      class="fixed top-5 right-5 z-50 max-w-sm rounded-xl px-4 py-3 shadow-lg text-white"
      :class="flash?.success ? 'bg-green-600' : 'bg-red-600'"
    >
      <div class="font-semibold">
        {{ message }}
      </div>
    </div>
  </transition>
</template>
