<script setup>
import { computed } from 'vue'

/**
 * Props:
 * collapsed = true → sidebar is shrunk
 * collapsed = false → sidebar is open
 */
const props = defineProps({
  collapsed: {
    type: Boolean,
    default: false,
  }
})

/**
 * Determine which image should be shown.
 * We return the correct file name depending on:
 * - collapsed state
 * - dark/light theme
 */
const logoLight = computed(() => 
  props.collapsed ? '/storage/images/SPWR.svg' : '/storage/images/SPWR_BIG.svg'
)

const logoDark = computed(() => 
  props.collapsed ? '/storage/images/SPWR.svg' : '/storage/images/SPWR_BIG.D.svg'
)
</script>

<template>
  <div class="flex items-center justify-center py-4">

    <!-- Light mode -->
    <img 
      :src="logoLight"
      class="h-10 dark:hidden transition-all"
      alt="logo"
    >

    <!-- Dark mode -->
    <img 
      :src="logoDark"
      class="h-10 hidden dark:block transition-all"
      alt="logo"
    >

  </div>
</template>
