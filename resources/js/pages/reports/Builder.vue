<script setup lang="ts">
import AppLayout from "@/layouts/AppLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { computed } from "vue";
import draggable from "vuedraggable";
import { route } from "ziggy-js";

/* ---------------- TYPES ---------------- */

interface Column {
  field: string;
  label: string;
  table: string;
  column: string;
  type: string;
  is_numeric?: boolean;
}

interface Module {
  key: string;
  label: string;
  table: string;
  fields: Column[];
  relationships: any[];
}

interface Join {
  table: string;
  first: string;
  second: string;
  type: "left" | "inner";
}

interface Sort {
  field: string;
  direction: "asc" | "desc";
}

interface Filter {
  field: string;
  operator: string;
  value: string;
}

interface Aggregate {
  field: string;
  function: "SUM" | "AVG" | "MIN" | "MAX" | "COUNT";
  alias: string;
}

/* ---------------- PROPS ---------------- */

const props = defineProps<{
  modules: Record<string, Module>;
}>();

const moduleKeys = Object.keys(props.modules);

/* ---------------- FORM ---------------- */

const form = useForm<{
  name: string;
  module: string;
  columns: string[];
  joins: Join[];
  filters: Filter[];
  sorts: Sort[];
  groups: string[];
  aggregates: Aggregate[];
  is_public: boolean;
}>({
  name: "",
  module: "",
  columns: [],
  joins: [],
  filters: [],
  sorts: [],
  groups: [],
  aggregates: [],
  is_public: false,
});

/* ---------------- HELPERS ---------------- */

function cloneField(field: Column) {
  return field.field; // IMPORTANT: only store string field
}

function addAggregate() {
  form.aggregates.push({
    field: "",
    function: "SUM",
    alias: "",
  });
}

function removeAggregate(index: number) {
  form.aggregates.splice(index, 1);
}

/* ---------------- MODULE ---------------- */

const currentModule = computed(() => {
  return props.modules[form.module];
});

/* ---------------- AVAILABLE FIELDS ---------------- */

const availableFields = computed<Column[]>(() => {
  const fields: Column[] = [];

  if (currentModule.value) {
    fields.push(...currentModule.value.fields);
  }

  form.joins.forEach((j) => {
    const mod = props.modules[j.table];
    if (mod) fields.push(...mod.fields);
  });

  return fields;
});

const numericFields = computed(() => availableFields.value.filter((f) => f.is_numeric));

/* ---------------- GROUPED FIELDS ---------------- */

const groupedFields = computed(() => {
  const map: Record<string, Column[]> = {};

  availableFields.value.forEach((f) => {
    if (!map[f.table]) map[f.table] = [];
    map[f.table].push(f);
  });

  return map;
});

/* ---------------- JOINS ---------------- */

function addJoin() {
  form.joins.push({
    table: "",
    first: "",
    second: "",
    type: "left",
  });
}

function removeJoin(index: number) {
  form.joins.splice(index, 1);
}

function onJoinTableChange(join: Join) {
  const base = props.modules[form.module];
  if (!base) return;

  const rel = base.relationships.find((r: any) => r.table === join.table);

  if (rel) {
    join.first = rel.first;
    join.second = rel.second;
  }
}

/* ---------------- SORT / FILTER ---------------- */

function addSort() {
  form.sorts.push({ field: "", direction: "asc" });
}

function addFilter() {
  form.filters.push({ field: "", operator: "=", value: "" });
}

/* ---------------- DRAG ADD ---------------- */

function onAddColumn(field: string) {
  if (!form.columns.includes(field)) {
    form.columns.push(field);
  }
}

/* ---------------- PREVIEW ---------------- */

const previewPayload = computed(() => ({
  module: form.module,
  columns: form.columns,
  joins: form.joins,
  filters: form.filters,
  sorts: form.sorts,
  groups: form.groups,
  aggregates: form.aggregates,
}));

const joinFields = computed(() => {
  if (!form.module) return [];

  const fields: Column[] = [];

  // base module fields
  const base = props.modules[form.module];
  if (base) fields.push(...base.fields);

  // joined module fields
  form.joins.forEach((j: any) => {
    const mod = props.modules[j.table];
    if (mod) fields.push(...mod.fields);
  });

  return fields;
});

/* ---------------- SUBMIT ---------------- */

function submit() {
  form.clearErrors();

  form.post(route("reports.store"), {
    preserveScroll: true,
    onError: (err: any) => {
      console.log("REPORT ERRORS:", err);
    },
  });
}
</script>

<template>
  <Head title="Report Builder" />

  <AppLayout>
    <div class="h-screen flex flex-col bg-gray-50 dark:bg-gray-950">
      <!-- HEADER -->
      <div
        class="flex justify-between items-center border-b px-4 py-3 dark:border-gray-800"
      >
        <div class="flex gap-3">
          <input
            v-model="form.name"
            placeholder="Report Name"
            class="w-72 p-2 rounded border dark:bg-gray-900 dark:text-white"
          />

          <select
            v-model="form.module"
            class="p-2 rounded border dark:bg-gray-900 dark:text-white"
          >
            <option value="">Select Data Source</option>
            <option v-for="key in moduleKeys" :key="key" :value="key">
              {{ props.modules[key].label }}
            </option>
          </select>
        </div>

        <button @click="submit" class="bg-blue-600 text-white px-4 py-2 rounded">
          Save Report
        </button>
      </div>

      <!-- BODY -->
      <div class="flex flex-1 overflow-hidden">
        <!-- LEFT: AVAILABLE FIELDS -->
        <div class="w-80 overflow-y-auto border-r p-3 dark:border-gray-800">
          <h2 class="font-bold mb-3 text-white">Available Fields</h2>

          <div v-for="(fields, table) in groupedFields" :key="table" class="mb-4">
            <div class="text-sm font-semibold text-gray-400 mb-2">
              {{ table }}
            </div>

            <draggable
              :list="fields"
              :group="{ name: 'fields', pull: 'clone', put: false }"
              :clone="cloneField"
              item-key="field"
            >
              <template #item="{ element }">
                <div
                  class="p-2 mb-2 border rounded bg-gray-900 text-white cursor-move"
                  @click="onAddColumn(element.field)"
                >
                  {{ element.label }}
                </div>
              </template>
            </draggable>
          </div>
        </div>

        <!-- CENTER: SELECTED -->
        <div class="flex-1 p-4 overflow-y-auto">
          <h2 class="font-bold text-white mb-3">Selected Columns</h2>

          <draggable
            v-model="form.columns"
            item-key="field"
            class="min-h-[300px] border p-3 rounded bg-gray-900"
          >
            <template #item="{ element }">
              <div class="p-2 mb-2 border rounded bg-blue-900 text-white">
                {{ availableFields.find((f) => f.field === element)?.label || element }}
              </div>
            </template>
          </draggable>
        </div>

        <!-- RIGHT -->
        <div class="w-96 border-l p-4 overflow-y-auto dark:border-gray-800">
          <!-- JOINS -->
          <h2 class="font-bold text-white mb-2">Joins</h2>

          <button @click="addJoin" class="bg-green-600 text-white px-2 py-1 rounded mb-3">
            + Join
          </button>

          <div v-for="(join, i) in form.joins" :key="i" class="mb-3 space-y-2">
            <select
              v-model="join.table"
              @change="onJoinTableChange(join)"
              class="w-full p-2 bg-gray-900 text-white rounded"
            >
              <option value="">Select Table</option>
              <option v-for="key in moduleKeys" :key="key" :value="key">
                {{ props.modules[key].label }}
              </option>
            </select>
            <!-- FROM COLUMN -->
            <select
              v-model="join.first"
              class="w-full p-2 rounded bg-gray-900 text-white"
            >
              <option value="">Select From Column</option>
              <option v-for="c in joinFields" :key="c.field" :value="c.field">
                {{ c.table }} → {{ c.label }}
              </option>
            </select>

            <!-- TO COLUMN -->
            <select
              v-model="join.second"
              class="w-full p-2 rounded bg-gray-900 text-white"
            >
              <option value="">Select To Column</option>
              <option v-for="c in joinFields" :key="c.field + '-to'" :value="c.field">
                {{ c.table }} → {{ c.label }}
              </option>
            </select>

            <button @click="removeJoin(i)" class="text-red-500 text-xs">Remove</button>
          </div>

          <!-- SORT -->
          <h2 class="font-bold text-white mt-6">Sorting</h2>
          <button
            @click="addSort"
            class="bg-orange-600 text-white px-2 py-1 rounded mb-2"
          >
            + Sort
          </button>

          <div v-for="(sort, i) in form.sorts" :key="i" class="mb-2">
            <select v-model="sort.field" class="w-full p-2 bg-gray-900 text-white">
              <option v-for="c in availableFields" :key="c.field" :value="c.field">
                {{ c.label }}
              </option>
            </select>

            <select
              v-model="sort.direction"
              class="w-full p-2 bg-gray-900 text-white mt-1"
            >
              <option value="asc">ASC</option>
              <option value="desc">DESC</option>
            </select>
          </div>

          <!-- FILTER -->
          <h2 class="font-bold text-white mt-6">Filters</h2>
          <button
            @click="addFilter"
            class="bg-green-600 text-white px-2 py-1 rounded mb-2"
          >
            + Filter
          </button>

          <div v-for="(filter, i) in form.filters" :key="i" class="mb-2">
            <select v-model="filter.field" class="w-full p-2 bg-gray-900 text-white">
              <option v-for="c in availableFields" :key="c.field" :value="c.field">
                {{ c.label }}
              </option>
            </select>

            <input
              v-model="filter.value"
              class="w-full p-2 bg-gray-900 text-white mt-1"
              placeholder="Value"
            />
          </div>

          <!-- GROUP -->
          <h2 class="font-bold text-white mt-6">Group By</h2>

          <select
            v-model="form.groups"
            multiple
            class="w-full h-32 p-2 bg-gray-900 text-white"
          >
            <option v-for="c in availableFields" :key="c.field" :value="c.field">
              {{ c.label }}
            </option>
          </select>

          <!-- AGGREGATES -->

          <h2 class="font-bold text-white mt-6">Aggregates</h2>

          <button
            @click="addAggregate"
            class="bg-purple-600 text-white px-2 py-1 rounded mb-2"
          >
            + Aggregate
          </button>
          <div class="text-xs text-red-400">
            Available Fields: {{ availableFields.length }}
            <br />
            Numeric Fields: {{ numericFields.length }}
          </div>

          <div
            v-for="(agg, i) in form.aggregates"
            :key="i"
            class="mb-3 space-y-2 p-2 border rounded border-gray-700"
          >
            <select
              v-model="agg.function"
              class="w-full p-2 bg-gray-900 text-white rounded"
            >
              <option value="COUNT">COUNT</option>
              <option value="SUM">SUM</option>
              <option value="AVG">AVG</option>
              <option value="MIN">MIN</option>
              <option value="MAX">MAX</option>
            </select>

            <select v-model="agg.field" class="w-full p-2 bg-gray-900 text-white rounded">
              <option value="">Select Field</option>

              <option
                v-for="c in ['SUM', 'AVG'].includes(agg.function)
                  ? numericFields
                  : availableFields"
                :key="c.field"
                :value="c.field"
              >
                {{ c.table }} → {{ c.label }}
              </option>
            </select>

            <input
              v-model="agg.alias"
              placeholder="Alias (e.g. total_salary)"
              class="w-full p-2 bg-gray-900 text-white rounded"
            />

            <button @click="removeAggregate(i)" class="text-red-500 text-xs">
              Remove
            </button>
          </div>

          <!-- PREVIEW -->
          <h2 class="font-bold text-white mt-6">Preview</h2>
          <pre class="text-xs text-gray-300 bg-gray-900 p-2 rounded overflow-auto"
            >{{ previewPayload }}
          </pre>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
