<script setup lang="ts">
import AppLayout from "@/layouts/AppLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import type { BreadcrumbItem } from "@/types";

defineProps<{
  team: Array<{
    id: number;
    empnum: string;
    name: string;
    businessTitle: string;
    deptName: string;
    shiftCode: string;
  }>;
}>();

function navigate(id: string) {
  router.get(`/employees/${id}`);
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Dashboard", href: "/" },
  { title: "Manage My Team", href: "/my-team" },
];
</script>

<template>
  <Head title="Manage My Team" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-6">
      <div
        class="rounded-lg border bg-white p-4 shadow-sm dark:bg-gray-900 dark:border-gray-700"
      >
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
          My Team Members
        </h2>

        <p class="text-sm text-gray-600 dark:text-gray-400">
          Employees who report directly to you
        </p>

        <div class="mt-4 overflow-x-auto">
          <table class="min-w-full border border-gray-200 dark:border-gray-700">
            <thead class="bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200">
              <tr>
                <th class="px-4 py-2 text-left">Emp #</th>
                <th class="px-4 py-2 text-left">Name</th>
                <th class="px-4 py-2 text-left">Position</th>
                <th class="px-4 py-2 text-left">Department</th>
                <th class="px-4 py-2 text-left">Shift Code</th>
                <th class="px-4 py-2 text-left">Action</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="member in team"
                :key="member.id"
                class="border-t border-gray-200 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
              >
                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">
                  {{ member.empnum }}
                </td>
                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">
                  {{ member.name }}
                </td>
                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">
                  {{ member.businessTitle }}
                </td>
                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">
                  {{ member.deptName }}
                </td>
                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">
                  {{ member.shiftCode }}
                </td>
                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">
                  <button
                    class="px-3 py-1 rounded text-white bg-blue-500 hover:bg-blue-700"
                    @click="navigate(member.empnum)"
                  >
                    View Profile
                  </button>
                </td>
              </tr>

              <tr v-if="team.length === 0">
                <td
                  colspan="4"
                  class="px-4 py-6 text-center text-gray-500 dark:text-gray-400"
                >
                  No team members found.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
