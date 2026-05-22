<script setup lang="ts">
import AppLayout from "@/layouts/AppLayout.vue";
import { Head, usePage } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { ref, watch, computed } from "vue";

import type { Timekeeping } from "@/components/timekeeping/columns";
import { columns as buildColumns } from "@/components/timekeeping/columns";
import DataTable from "@/components/timekeeping/data-table.vue";
import type { PageProps as InertiaPageProps } from "@inertiajs/core";

type Props = InertiaPageProps & {
  timekeeping: Timekeeping[];
  currentPayrollDate: string | null;
};

// Access Inertia page props
const page = usePage<Props>();

// reactive table data
const data = ref<Timekeeping[]>([]);

// computed properties
const timekeeping = computed(() => page.props.timekeeping);
const currentPayrollDate = computed(() => page.props.currentPayrollDate);
const tableColumns = computed(() => buildColumns(currentPayrollDate.value));

// keep data reactive if page props change
watch(
  () => page.props.timekeeping,
  (val) => (data.value = val as Timekeeping[]),
  { immediate: true }
);

// ✅ Fixed breadcrumbs with correct route name
const breadcrumbs = [
  { title: "Timekeeping", href: route("timekeeping") }, // must match Laravel route name exactly
];
</script>

<template>
  <Head title="Timekeeping" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="m-4 space-y-4">
      <div class="w-full py-5 px-5">
        <DataTable
          :columns="tableColumns"
          :data="timekeeping"
          :currentPayrollDate="currentPayrollDate"
        />
      </div>
    </div>
  </AppLayout>
</template>
