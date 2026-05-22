<script setup lang="ts">
import { MoreHorizontal } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js';
import axios from 'axios';
import { ref, onMounted } from 'vue'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'

defineProps<{
  medical: {
    id: string
  }
}>()

const medicalList = ref<any[]>([]) 
const toast = ref<string>('')

onMounted(async () => {
  try {
    const response = await axios.get(route('medical')) // your API or JSON route
    medicalList.value = response.data
  } catch (err) {
    console.error(err)
  }
})

function copy(id: string) {
  navigator.clipboard.writeText(id)
}

function editRecord(id: string) {
  // Navigate to edit page for this record
  router.visit(`/medical/${id}/edit`)
}
function viewRecord(id: string) {
  // Navigate to edit page for this record
  router.visit(`/medical/${id}/show`)
}

async function deleteRecord(id: string) {
  if (!confirm('Are you sure you want to delete this record?')) return

  try {
    const response = await axios.delete(route('medical.destroy', { medical: id }))
    toast.value = response.data.message || 'Record deleted successfully'
    // Remove from the array to update UI instantly
    medicalList.value = medicalList.value.filter(m => m.id !== id)

    // Hide toast after 3 seconds
    setTimeout(() => (toast.value = ''), 3000)
    window.location.reload()
  } catch (error) {
    console.error(error)
    toast.value = 'Failed to delete record'
    setTimeout(() => (toast.value = ''), 3000)
  }
}
</script>

<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <Button variant="ghost" class="w-8 h-8 p-0">
        <span class="sr-only">Open menu</span>
        <MoreHorizontal class="w-4 h-4" />
      </Button>
    </DropdownMenuTrigger>
    <DropdownMenuContent align="end">
      <DropdownMenuLabel>Actions</DropdownMenuLabel>
      <DropdownMenuItem @click="viewRecord(medical.id)">View</DropdownMenuItem>
      <DropdownMenuItem @click="editRecord(medical.id)">Update</DropdownMenuItem>
      <DropdownMenuItem @click="deleteRecord(medical.id)">Delete</DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>
