<script setup lang="ts">
import {h, ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import DataTable from '../students/Datatable.vue'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import DialogueDelete  from '@/pages/Dialogue/DialogueDelete.vue'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'
import { Loader2, ArrowLeft, BookOpen, Edit, Trash2, Eye, Plus, Pencil } from 'lucide-vue-next'
import { Link } from '@inertiajs/vue3'
import { Toaster } from '@/components/ui/sonner'
import 'vue-sonner/style.css'
import { useToast } from '@/composables/useToast'
import { usePermissions } from '@/composables/usePermission'
import type { ColumnDef } from '@tanstack/vue-table'
import { useFetchingData, type Role } from '@/composables/fetchCommonData'

const { rolePermission } = usePermissions();
const rolePermissionList = rolePermission.value ;

const { toast } = useToast()
const props = defineProps<{ roles: Role[] }>()
const roles = ref(props.roles || [])
const selectedRole = ref<Role | null>(null)
const isDeleteOpen = ref(false)
const breadcrumbs = [
  { title: 'Roles', href: '/roles' },
  // { title: 'View Subjects', href: '/subjects/create' }
]
const {
  
  loading,
  
} = useFetchingData();
const formatType = (value: string) => {
  return value
    .split('_')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ')
}
const columns: ColumnDef<Role>[] = [
  { accessorKey: 'id', header: 'ID', cell: ({ row }) => h('div', { class: 'font-medium' }, row.getValue('id')) },
  { accessorKey: 'name', header: 'Name' },
  { accessorKey: 'description', header: 'Description' },
  {
    accessorKey: 'is_active',
    header: 'Status',
    cell: ({ row }) => {
      const value = row.getValue('is_active') as number
      return h(
        'span',
        {
          class: value
            ? 'bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-semibold'
            : 'bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs font-semibold'
        },
        value ? 'Active' : 'Inactive'
      )
    }
  },
  {
    id: 'actions',
    header: 'Actions',
    enableSorting: false,
    cell: ({ row }) => {
      const role = row.original
      return h('div', { class: 'flex items-center gap-2' }, [
        // h(Button, { variant: 'ghost', size: 'sm', class: 'h-8 w-8 p-0', title: 'View', onClick: () => handleView(student) }, () => h(Eye, { class: 'h-4 w-4' })),
        rolePermissionList.canEdit &&
        h(Button, { variant: 'ghost', size: 'sm', class: 'h-8 w-8 p-0 cursor-pointer', title: 'Edit', onClick: () => handleEdit(role) }, () => h(Edit, { class: 'h-4 w-4' })),
        rolePermissionList.canDelete &&
        h(Button, { variant: 'ghost', size: 'sm', class: 'h-8 w-8 p-0 text-red-600 hover:text-red-700 hover:bg-red-50 cursor-pointer', title: 'Delete', onClick: () => handleDelete(role) }, () => h(Trash2, { class: 'h-4 w-4' })),
        rolePermissionList.canAssignPermissions &&
        h(Button, { variant: 'ghost', size: 'sm', class: 'h-8 w-8 p-0 cursor-pointer', title: 'Assign Permissions', onClick: () => router.get(route('roles.assign_permissions', role.id)) }, () => h(BookOpen, { class: 'h-4 w-4' })),
      ])
    },
  },
]
const handleEdit = (role?: Role) => {
  const r = role 
  console.log(r,"role");
  if (r) router.get(route('roles.edit', r.id));
  
  // if (s) startEdit(s)
}

const handleDelete = (role?: Role) => {
  if (role) {
    selectedRole.value = role
    isDeleteOpen.value = true
  }
}



const confirmDelete = async (id: string | number | null) => {
  console.log(id, "id");
  
  if (!id) return

  try {
    router.put(
      route('roles.delete', id),
      {}, // Data object (empty since you're just soft deleting)
      {
        preserveScroll: true,
        onSuccess: () => {
          toast.success('Roles deleted successfully')
          // Remove from local list immediately
          roles.value = roles.value.filter(s => s.id !== id)
          isDeleteOpen.value = false
          selectedRole.value = null
        },
        onError: (errors) => {
          toast.error('Failed to delete role')
          console.error(errors)
        },
        onFinish: () => {
          isDeleteOpen.value = false
          selectedRole.value = null
        }
      }
    )
  } catch (err) {
    toast.error('Failed to delete role')
    console.error(err)
  }
}
</script>

<template>
  <Head title="View Roles" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <Toaster />

    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">
      

      <!-- Students Table -->
      <Card class="w-full shadow-lg rounded-2xl">
        <CardHeader>
          <CardTitle class="text-xl font-bold">
            Roles List 
                <Button v-if="rolePermissionList.canCreate" as-child class="ml-auto float-right">
                  <Link :href="route('roles.create')">
                    <Plus class="w-4 h-4 mr-2" />
                    Create Roles
                  </Link>
                </Button>
          </CardTitle>
        </CardHeader>
        
        <CardContent class="pt-6">
          <DataTable :columns="columns" :data="roles" :loading="loading" title="Roles List" />
        </CardContent>
      </Card>
    </div>
  </AppLayout>

  <DialogueDelete
     v-model="isDeleteOpen"
    :title="'Delete Role'"
    :description="'Are you sure you want to delete this role? This action cannot be undone.'"
    :item-name="selectedRole?.name"
    :item-id="selectedRole?.id"
    @confirm="confirmDelete(selectedRole?.id ?? null)"
  />
</template>

<style scoped>
/* Optional: Improve select trigger appearance when invalid */
</style>