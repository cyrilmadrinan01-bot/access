<script setup lang="ts">
import AppLayout from "@/layouts/AppLayout.vue";
import SectionHeader from "@/components/SectionHeader.vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { ref } from "vue";

import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

defineProps<{
  types: {
    type: string;
    total: number;
  }[];
}>();

const showTypeModal = ref(false);

const typeForm = useForm({
  type: "",
});

const breadcrumbs = [
  {
    title: "Administration",
    href: "#",
  },
  {
    title: "Picklists",
    href: "",
  },
];

function saveType() {
  typeForm.post(route("picklists.types.store"), {
    onSuccess: () => {
      showTypeModal.value = false;
      typeForm.reset();
    },
  });
}
</script>

<template>
  <Head title="Picklists" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6">
      <div class="mb-6 flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold tracking-tight">Picklist Types</h2>

          <p class="text-muted-foreground">
            Manage system dropdown values and classifications.
          </p>
        </div>

        <Button size="sm" @click="showTypeModal = true"> Add Picklist Type </Button>
      </div>
      <div class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
        <table class="w-full">
          <thead>
            <tr class="border-b border-border bg-muted/50">
              <th class="px-6 py-4 text-left text-sm font-semibold">Type</th>

              <th class="px-6 py-4 text-left text-sm font-semibold">Items</th>

              <th class="px-6 py-4 text-right text-sm font-semibold">Actions</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="item in types"
              :key="item.type"
              class="border-b border-border transition-colors hover:bg-muted/50"
            >
              <td class="px-6 py-4">
                <div class="font-medium">
                  {{ item.type }}
                </div>
              </td>

              <td class="px-6 py-4">
                <span
                  class="inline-flex rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary"
                >
                  {{ item.total }} Items
                </span>
              </td>

              <td class="px-6 py-4 text-right">
                <Button
                  variant="outline"
                  size="sm"
                  @click="router.visit(route('picklists.show', item.type))"
                >
                  Manage
                </Button>
              </td>
            </tr>

            <tr v-if="types.length === 0">
              <td colspan="3" class="py-16 text-center">
                <div class="space-y-2">
                  <h3 class="font-semibold">No Picklist Types</h3>

                  <p class="text-sm text-muted-foreground">
                    Create your first picklist type to get started.
                  </p>

                  <Button size="sm" @click="showTypeModal = true">
                    Add Picklist Type
                  </Button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <Dialog v-model:open="showTypeModal">
      <DialogContent class="sm:max-w-md border-border bg-background">
        <DialogHeader>
          <DialogTitle> Add Picklist Type </DialogTitle>
        </DialogHeader>

        <div class="space-y-6 py-2">
          <div class="space-y-2">
            <Label for="type" class="font-medium"> Picklist Type </Label>

            <Input
              id="type"
              v-model="typeForm.type"
              placeholder="WORK_LOCATION"
              class="lowercase"
            />

            <p class="text-xs text-muted-foreground">
              Example: WORK_LOCATION, EMPLOYEE_STATUS, LEAVE_TYPE
            </p>

            <div v-if="typeForm.errors.type" class="text-sm text-destructive">
              {{ typeForm.errors.type }}
            </div>
          </div>

          <div class="flex justify-end gap-2 border-t pt-4">
            <Button variant="outline" @click="showTypeModal = false"> Cancel </Button>

            <Button :disabled="typeForm.processing" @click="saveType"> Save </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
