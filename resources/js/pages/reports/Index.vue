<script setup lang="ts">
import AppLayout from "@/layouts/AppLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { route } from "ziggy-js";

interface Report {
  id: number;
  name: string;
  module: string;
}

defineProps<{
  reports: Report[];
}>();

const breadcrumbs = [
  {
    title: "Reports",
    href: route("reports.index"),
  },
];
</script>

<template>
  <Head title="Reports" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6">
      <!-- HEADER -->
      <div
        class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
      >
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
            Reports
          </h1>

          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Create, manage, and share custom reports.
          </p>
        </div>

        <Link
          :href="route('reports.create')"
          class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow transition hover:bg-blue-700"
        >
          Create Report
        </Link>
      </div>

      <!-- CARD -->
      <div
        class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900"
      >
        <!-- TABLE -->
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
            <thead class="bg-gray-50 dark:bg-gray-950/40">
              <tr>
                <th
                  class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400"
                >
                  Report Name
                </th>

                <th
                  class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400"
                >
                  Module
                </th>

                <th
                  class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400"
                >
                  Actions
                </th>
              </tr>
            </thead>

            <tbody
              class="divide-y divide-gray-100 bg-white dark:divide-gray-800 dark:bg-gray-900"
            >
              <tr
                v-for="report in reports"
                :key="report.id"
                class="transition hover:bg-gray-50 dark:hover:bg-gray-800/50"
              >
                <!-- NAME -->
                <td class="whitespace-nowrap px-6 py-4">
                  <div class="font-medium text-gray-900 dark:text-gray-100">
                    {{ report.name }}
                  </div>
                </td>

                <!-- MODULE -->
                <td class="whitespace-nowrap px-6 py-4">
                  <span
                    class="inline-flex rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300"
                  >
                    {{ report.module }}
                  </span>
                </td>

                <!-- ACTIONS -->
                <td class="whitespace-nowrap px-6 py-4">
                  <div class="flex justify-end gap-3">
                    <Link
                      :href="route('reports.show', report.id)"
                      class="rounded-md px-3 py-1.5 text-sm font-medium text-blue-600 transition hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-950/40"
                    >
                      Open
                    </Link>
                    <!--
                    <Link
                      :href="route('reports.edit', report.id)"
                      class="rounded-md px-3 py-1.5 text-sm font-medium text-amber-600 transition hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-950/40"
                    >
                      Edit
                    </Link>
-->
                  </div>
                </td>
              </tr>

              <!-- EMPTY STATE -->
              <tr v-if="reports.length === 0">
                <td colspan="3" class="px-6 py-16 text-center">
                  <div class="text-sm text-gray-500 dark:text-gray-400">
                    No reports found.
                  </div>

                  <div class="mt-4">
                    <Link
                      :href="route('reports.create')"
                      class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700"
                    >
                      Create Your First Report
                    </Link>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
