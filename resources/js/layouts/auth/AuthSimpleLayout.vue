<script setup lang="ts">
import AppLogoIcon from "@/components/AppLogoIcon.vue";
import { home } from "@/routes";
import { Link } from "@inertiajs/vue3";

defineProps<{
  title?: string;
  description?: string;
}>();
</script>

<template>
  <div
    class="relative flex min-h-svh flex-col items-center justify-center p-6 md:p-10 overflow-hidden"
  >
    <!-- BACKGROUND GLOW -->
    <div
      class="absolute inset-0 bg-gradient-to-br from-blue-500/30 via-transparent to-orange-500/30"
    ></div>
    <div class="absolute inset-0 backdrop-blur-3xl"></div>

    <!-- CARD CONTAINER (SMALLER) -->
    <div class="relative w-full max-w-sm sm:max-w-md">
      <!-- GLASS CARD -->
      <div
        class="relative rounded-3xl border border-white/10 bg-white/10 dark:bg-white/5 backdrop-blur-2xl shadow-2xl p-6 md:p-8 overflow-hidden"
      >
        <!-- gradient edge glow -->
        <div
          class="absolute inset-0 rounded-3xl pointer-events-none bg-gradient-to-r from-black-500/20 via-transparent to-white-500/20 opacity-100"
        ></div>

        <div class="relative flex flex-col gap-4">
          <!-- HEADER -->
          <div class="flex flex-col items-center gap-5">
            <Link :href="home()" class="flex flex-col items-center gap-3">
              <div class="flex justify-center">
                <img
                  src="/storage/images/SPWR_BIG.svg"
                  class="h-20 md:h-36 dark:hidden transition-all drop-shadow-md"
                  alt="SPWR Logo"
                />

                <img
                  src="/storage/images/SPWR_BIG.D.svg"
                  class="h-20 md:h-36 hidden dark:block transition-all drop-shadow-md"
                  alt="SPWR Logo Dark"
                />
              </div>

              <span class="sr-only">{{ title }}</span>
            </Link>

            <!-- TEXT -->
            <div class="text-center space-y-1.5">
              <h1 class="text-xl md:text-2xl font-semibold tracking-tight">
                {{ title }}
              </h1>
              <p class="text-sm text-muted-foreground leading-relaxed max-w-xs mx-auto">
                {{ description }}
              </p>
            </div>
          </div>

          <!-- SLOT -->
          <div class="relative">
            <slot />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
