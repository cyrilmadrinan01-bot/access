<script setup lang="ts">
import AppLayout from "@/layouts/AppLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { computed } from "vue";
import { route } from "ziggy-js";

interface Column {
  field: string;
  label: string;
  type?: string;
}

interface Relationship {
  table: string;
  first: string;
  second: string;
}

interface Module {
  label: string;
  table: string;
  columns: Column[];
  relationships?: Relationship[];
}

interface Filter {
  field: string;
  operator: string;
  value: string;
  boolean?: "and" | "or";
}

const props = defineProps<{
  modules: Record<string, Module>;
}>();

const moduleKeys = Object.keys(props.modules);

const form = useForm({
  name: "",
  module: moduleKeys[0] ?? "",
  columns: [] as string[],
  joins: [] as string[],
  filters: [] as Filter[],
  sorts: [],
  groups: [],
  aggregates: [],
  is_public: false,
});

const currentModule = computed(() => {
  return props.modules[form.module];
});

const availableColumns = computed(() => {
  if (!currentModule.value) {
    return [];
  }

  let columns = [...currentModule.value.columns];

  form.joins.forEach((joinTable: string) => {
    const joinedModule = props.modules[joinTable];

    if (joinedModule?.columns) {
      columns.push(...joinedModule.columns);
    }
  });

  return columns;
});

function buildJoins() {
  if (!currentModule.value?.relationships) {
    return [];
  }

  return form.joins
    .map((joinTable: string) => {
      const relation = currentModule.value.relationships?.find(
        (r) => r.table === joinTable
      );

      if (!relation) {
        return null;
      }

      return {
        table: joinTable,
        first: relation.first,
        second: relation.second,
      };
    })
    .filter(Boolean);
}

function addFilter() {
  form.filters.push({
    field: "",
    operator: "=",
    value: "",
    boolean: "and",
  });
}

function submit() {
  form.transform((data) => ({
    ...data,
    joins: buildJoins(),
  }));

  form.post(route("reports.store"));
}
</script>

<template>
  <Head title="Advanced Report Builder" />

  <AppLayout>
    <div class="p-6">
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          Advanced Report Builder
        </h1>
      </div>

      <div class="grid grid-cols-1 gap-6 xl:grid-cols-4">
        <!-- SIDEBAR -->

        <div class="space-y-6 xl:col-span-1">
          <div
            class="rounded-2xl border bg-white p-5 dark:border-gray-800 dark:bg-gray-900"
          >
            <div class="space-y-4">
              <div>
                <label
                  class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                  Report Name
                </label>

                <input
                  v-model="form.name"
                  class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                />
              </div>

              <div>
                <label
                  class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                  Base Table
                </label>

                <select
                  v-model="form.module"
                  class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                >
                  <option v-for="key in moduleKeys" :key="key" :value="key">
                    {{ props.modules[key].label }}
                  </option>
                </select>
              </div>
            </div>
          </div>

          <!-- RELATIONSHIPS -->

          <div
            v-if="currentModule?.relationships?.length"
            class="rounded-2xl border bg-white p-5 dark:border-gray-800 dark:bg-gray-900"
          >
            <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
              Relationships
            </h2>

            <div class="space-y-3">
              <label
                v-for="relation in currentModule.relationships"
                :key="relation.table"
                class="flex items-center gap-3"
              >
                <input v-model="form.joins" :value="relation.table" type="checkbox" />

                <span class="text-sm text-gray-700 dark:text-gray-300">
                  {{ relation.table }}
                </span>
              </label>
            </div>
          </div>
        </div>

        <!-- MAIN -->

        <div class="space-y-6 xl:col-span-3">
          <!-- COLUMNS -->

          <div
            class="rounded-2xl border bg-white p-5 dark:border-gray-800 dark:bg-gray-900"
          >
            <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
              Columns
            </h2>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
              <label
                v-for="column in availableColumns"
                :key="column.field"
                class="flex items-center gap-3 rounded-xl border border-gray-200 p-3 dark:border-gray-700"
              >
                <input v-model="form.columns" :value="column.field" type="checkbox" />

                <span class="text-sm text-gray-700 dark:text-gray-300">
                  {{ column.label }}
                </span>
              </label>
            </div>
          </div>

          <!-- FILTERS -->

          <div
            class="rounded-2xl border bg-white p-5 dark:border-gray-800 dark:bg-gray-900"
          >
            <div class="mb-4 flex items-center justify-between">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Filters</h2>

              <button
                type="button"
                @click="addFilter"
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm text-white"
              >
                Add Filter
              </button>
            </div>

            <div class="space-y-4">
              <div
                v-for="(filter, index) in form.filters"
                :key="index"
                class="grid grid-cols-1 gap-3 md:grid-cols-4"
              >
                <select
                  v-model="filter.field"
                  class="rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                >
                  <option v-for="column in availableColumns" :value="column.field">
                    {{ column.label }}
                  </option>
                </select>

                <select
                  v-model="filter.operator"
                  class="rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                >
                  <option value="=">=</option>
                  <option value="!=">!=</option>
                  <option value=">">></option>
                  <option value="<"><</option>
                  <option value="LIKE">LIKE</option>
                  <option value="contains">Contains</option>
                </select>

                <input
                  v-model="filter.value"
                  class="rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                />
              </div>
            </div>
          </div>

          <!-- SAVE -->

          <div class="flex justify-end">
            <button
              @click="submit"
              class="rounded-xl bg-blue-600 px-6 py-3 font-medium text-white"
            >
              Save Report
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
