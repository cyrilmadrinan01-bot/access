<script setup lang="ts">
import AppLayout from "@/layouts/AppLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { route } from "ziggy-js";

const props = defineProps<{
  report: any;
  data: any;
}>();

const breadcrumbs = [
  {
    title: "Reports",
    href: route("reports.index"),
  },
  {
    title: props.report.name,
    href: "#",
  },
];

function formatValue(value: any) {
  if (value === null || value === undefined || value === "") {
    return "-";
  }

  if (typeof value === "boolean") {
    return value ? "Yes" : "No";
  }

  return value;
}

function getColumnLabel(column: string) {
  return column.split(".").pop()?.replaceAll("_", " ");
}

function getColumnValue(row: any, column: string) {
  const alias = column.replaceAll(".", "_");

  return row[alias];
}
</script>

<template>
  <Head :title="report.name" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6">
      <!-- HEADER -->

      <div
        class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
      >
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ report.name }}
          </h1>

          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Dynamic report preview and generated records.
          </p>
        </div>

        <button
          class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
        >
          Export
        </button>
      </div>

      <!-- TABLE CARD -->

      <div
        class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900"
      >
        <!-- TABLE -->

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
            <!-- HEAD -->

            <thead class="bg-gray-50 dark:bg-gray-950">
              <tr>
                <th
                  v-for="column in report.columns"
                  :key="column"
                  class="whitespace-nowrap px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400"
                >
                  {{ getColumnLabel(column) }}
                </th>
              </tr>
            </thead>

            <!-- BODY -->

            <tbody
              class="divide-y divide-gray-100 bg-white dark:divide-gray-800 dark:bg-gray-900"
            >
              <tr
                v-for="(row, index) in data.data"
                :key="index"
                class="hover:bg-gray-50 dark:hover:bg-gray-800/40"
              >
                <td
                  v-for="column in report.columns"
                  :key="column"
                  class="whitespace-nowrap px-6 py-4 text-sm text-gray-700 dark:text-gray-200"
                >
                  {{ formatValue(getColumnValue(row, column)) }}
                </td>
              </tr>

              <!-- EMPTY -->

              <tr v-if="data.data.length === 0">
                <td
                  :colspan="report.columns.length"
                  class="px-6 py-16 text-center text-sm text-gray-500 dark:text-gray-400"
                >
                  No records found.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
