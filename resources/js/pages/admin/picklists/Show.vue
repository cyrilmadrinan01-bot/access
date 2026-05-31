<script setup lang="ts">
import AppLayout from "@/layouts/AppLayout.vue";
import SectionHeader from "@/components/SectionHeader.vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { ref } from "vue";

import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from "@/components/ui/dialog";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

const props = defineProps<{
  type: string;
  picklists: any[];
}>();

const showModal = ref(false);
const editing = ref(false);

const form = useForm({
  id: null as number | null,
  type: props.type,
  code: "",
  label: "",
  parent_code: "",
  sort_order: 0,
});

const breadcrumbs = [
  {
    title: "Picklists",
    href: route("picklists.index").toString(),
  },
  {
    title: props.type,
    href: "",
  },
];

function createRecord() {
  editing.value = false;

  form.reset();

  form.type = props.type;

  showModal.value = true;
}

function editRecord(row: any) {
  editing.value = true;

  form.id = row.id;
  form.code = row.code;
  form.label = row.label;
  form.parent_code = row.parent_code ?? "";
  form.sort_order = row.sort_order ?? 0;

  showModal.value = true;
}

function save() {
  if (editing.value) {
    if (form.id === null) {
      return;
    }

    form.put(route("picklists.update", form.id), {
      onSuccess: () => {
        showModal.value = false;
      },
    });
  } else {
    form.post(route("picklists.store"), {
      onSuccess: () => {
        showModal.value = false;
      },
    });
  }
}

function toggle(row: any) {
  form.patch(route("picklists.toggle", row.id));
}

function remove(row: any) {
  if (!confirm("Delete this picklist?")) {
    return;
  }

  form.delete(route("picklists.destroy", row.id));
}
</script>

<template>
  <Head :title="type" />
  <Dialog v-model:open="showModal">
    <DialogContent class="sm:max-w-lg">
      <DialogHeader>
        <DialogTitle>
          {{ editing ? "Edit Picklist" : "Add Picklist" }}
        </DialogTitle>
      </DialogHeader>

      <div class="space-y-4 py-2">
        <div>
          <Label>Code</Label>

          <Input v-model="form.code" placeholder="REGULAR" />

          <div v-if="form.errors.code" class="mt-1 text-sm text-destructive">
            {{ form.errors.code }}
          </div>
        </div>

        <div>
          <Label>Label</Label>

          <Input v-model="form.label" placeholder="Regular Employee" />

          <div v-if="form.errors.label" class="mt-1 text-sm text-destructive">
            {{ form.errors.label }}
          </div>
        </div>

        <div>
          <Label>Parent Code</Label>

          <Input v-model="form.parent_code" placeholder="Optional" />
        </div>

        <div>
          <Label>Sort Order</Label>

          <Input v-model="form.sort_order" type="number" />
        </div>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="showModal = false"> Cancel </Button>

        <Button :disabled="form.processing" @click="save"> Save </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6">
      <div
        class="mb-6 flex flex-col gap-4 rounded-xl border border-border bg-card p-6 shadow-sm md:flex-row md:items-center md:justify-between"
      >
        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-2xl font-bold tracking-tight">
              {{ type }}
            </h1>

            <span
              class="rounded-md bg-primary/10 px-2 py-1 text-xs font-medium text-primary"
            >
              {{ picklists.length }} Items
            </span>
          </div>

          <p class="mt-1 text-sm text-muted-foreground">
            Manage picklist values, status, and sorting order.
          </p>
        </div>

        <div class="flex items-center gap-2">
          <Button @click="createRecord"> Add Picklist </Button>
        </div>
      </div>

      <div class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
        <table class="w-full">
          <thead class="bg-muted/50">
            <tr>
              <th class="px-4 py-2 text-left">Code</th>
              <th class="px-4 py-2 text-left">Label</th>
              <th class="px-4 py-2 text-left">Parent</th>
              <th class="px-4 py-2 text-left">Status</th>
              <th class="px-4 py-2 text-left">Sort</th>
              <th class="px-4 py-2 text-right">Actions</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="row in picklists"
              :key="row.id"
              class="border-t border-border hover:bg-muted/50 transition-colors"
            >
              <td class="px-4 py-2">
                {{ row.code }}
              </td>

              <td class="px-4 py-2">
                {{ row.label }}
              </td>

              <td class="px-4 py-2">
                {{ row.parent_code || "-" }}
              </td>

              <td class="px-4 py-2">
                <span
                  class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
                  :class="
                    row.is_active
                      ? 'bg-green-500/10 text-green-600'
                      : 'bg-red-500/10 text-red-600'
                  "
                >
                  {{ row.is_active ? "Active" : "Disabled" }}
                </span>
              </td>

              <td class="px-4 py-2">
                {{ row.sort_order }}
              </td>

              <td class="space-x-2 text-right">
                <Button size="sm" variant="outline" @click="editRecord(row)">
                  Edit
                </Button>

                <Button size="sm" variant="outline" @click="toggle(row)">
                  {{ row.is_active ? "Disable" : "Enable" }}
                </Button>

                <Button size="sm" variant="destructive" @click="remove(row)">
                  Delete
                </Button>
              </td>
            </tr>

            <tr v-if="picklists.length === 0">
              <td colspan="6" class="py-6 text-center text-gray-500">
                <div class="space-y-2">
                  <p class="font-medium">No picklists found</p>

                  <Button size="sm" @click="createRecord"> Add First Picklist </Button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Modal goes here -->
    </div>
  </AppLayout>
</template>
