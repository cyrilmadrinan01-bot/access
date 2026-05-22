<script setup>
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
  users: Array,
  roles: Array,
})

const updateRoles = (user) => {
  router.post(`/users/${user.id}/roles`, {
    roles: user.manualRoles,
  })
}
</script>

<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-6">User Role Management</h1>

    <table class="w-full border">
      <thead>
        <tr class="bg-gray-100">
          <th class="p-2">User</th>
          <th class="p-2">Paygrade</th>
          <th class="p-2">Roles</th>
          <th class="p-2">Action</th>
        </tr>
      </thead>

      <tbody>
        <tr v-for="user in users" :key="user.id" class="border-t">
          <td class="p-2">
            {{ user.username }}
          </td>

          <td class="p-2">
            {{ user.employee?.paygrade ?? '—' }}
          </td>

          <td class="p-2">
            <div class="flex gap-2 flex-wrap">
              <label
                v-for="role in roles"
                :key="role"
                class="flex items-center gap-1"
              >
                <input
                  type="checkbox"
                  :value="role"
                  v-model="user.manualRoles"
                  :disabled="user.roles.some(r => r.name === 'super-admin')"
                />
                {{ role }}
              </label>
            </div>
          </td>

          <td class="p-2">
            <button
              class="bg-blue-600 text-white px-3 py-1 rounded"
              @click="updateRoles(user)"
            >
              Save
            </button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
export default {
  mounted() {
    this.users.forEach(user => {
      user.manualRoles = user.roles
        .map(r => r.name)
        .filter(r =>
          !['employee', 'manager'].includes(r)
        )
    })
  }
}
</script>
