<script setup lang="ts">
import {h, ref,computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router, useForm,usePage } from '@inertiajs/vue3'
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
import { useFetchingData, type User } from '@/composables/fetchCommonData'
const page = usePage();
const permissionList = computed(() => (page.props.auth as any)?.permissions || {});
const perm = permissionList.value.permissions ;
const { toast } = useToast()
const props = defineProps<{ users: User[] }>()
const users = ref(props.users)
const selectedUser = ref<User | null>(null)
const isDeleteOpen = ref(false)
const breadcrumbs = [
  { title: 'users', href: '/users' },
  // { title: 'View Subjects', href: '/subjects/create' }
]
const {
  
  loading,
  
} = useFetchingData();
const { usersPermission } = usePermissions();
const userPermissionList = usersPermission.value ;
// console.log(userPermissionList,"teacher");

const formatType = (value: string) => {
  return value
    .split('_')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ')
}
const columns: ColumnDef<User>[] = [
  { accessorKey: 'id', header: 'ID', cell: ({ row }) => h('div', { class: 'font-medium' }, row.getValue('id')) },
  { accessorKey: 'name', header: 'Name' },
  { accessorKey: 'email', header: 'Email' },
  // { accessorKey: 'phone', header: 'Phone No' },
  // { accessorKey: 'address', header: 'address' },
  // { accessorKey: 'subject_specialization', header: 'Subject Specializtion' },
 
  { accessorKey: 'roleName', header: 'Role' },
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
      const user = row.original
      return h('div', { class: 'flex items-center gap-2' }, [
        // h(Button, { variant: 'ghost', size: 'sm', class: 'h-8 w-8 p-0', title: 'View', onClick: () => handleView(student) }, () => h(Eye, { class: 'h-4 w-4' })),
        userPermissionList.canEdit &&
        h(Button, { variant: 'ghost', size: 'sm', class: 'h-8 w-8 p-0 cursor-pointer', title: 'Edit', onClick: () => handleEdit(user) }, () => h(Edit, { class: 'h-4 w-4' })),
        userPermissionList.canDelete &&
        h(Button, { variant: 'ghost', size: 'sm', class: 'h-8 w-8 p-0 text-red-600 hover:text-red-700 hover:bg-red-50 cursor-pointer', title: 'Delete', onClick: () => handleDelete(user) }, () => h(Trash2, { class: 'h-4 w-4' })),
      ])
    },
  },
]
const handleEdit = (user?: User) => {
  const u = user 
  console.log(u,"user");
  if (u) router.get(route('users.edit', u.id));
  
  // if (s) startEdit(s)
}

const handleDelete = (user?: User) => {
  if (user) {
    selectedUser.value = user
    isDeleteOpen.value = true
  }
}



const confirmDelete = async (id: string | number | null) => {
  console.log(id, "id");
  
  if (!id) return

  try {
    router.put(
      route('users.delete', id),
      {}, // Data object (empty since you're just soft deleting)
      {
        preserveScroll: true,
        onSuccess: () => {
          toast.success('User deleted successfully')
          // Remove from local list immediately
          users.value = users.value.filter(s => s.id !== id)
          isDeleteOpen.value = false
          selectedUser.value = null
        },
        onError: (errors) => {
          toast.error('Failed to delete User')
          console.error(errors)
        },
        onFinish: () => {
          isDeleteOpen.value = false
          selectedUser.value = null
        }
      }
    )
  } catch (err) {
    toast.error('Failed to delete User')
    console.error(err)
  }
}
</script>

<template>
  <Head title="View users" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <Toaster />

    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">
      

      <!-- Students Table -->
      <Card class="w-full shadow-lg rounded-2xl">
        <CardHeader>
          <CardTitle class="text-xl font-bold">
            Users List 
                <Button v-if="userPermissionList.canCreate" as-child class="ml-auto float-right">
                  <Link :href="route('users.create')">
                    <Plus class="w-4 h-4 mr-2" />
                    Create User
                  </Link>
                </Button>
          </CardTitle>
        </CardHeader>
        
        <CardContent class="pt-6">
          <DataTable :columns="columns" :data="users" :loading="loading" title="users List" />
        </CardContent>
      </Card>
    </div>
  </AppLayout>

  <DialogueDelete
     v-model="isDeleteOpen"
    :title="'Delete User'"
    :description="'Are you sure you want to delete this teacher? This action cannot be undone.'"
    :item-name="selectedUser?.name"
    :item-id="selectedUser?.id"
    @confirm="confirmDelete(selectedUser?.id ?? null)"
  />
</template>

<style scoped>
/* Optional: Improve select trigger appearance when invalid */
</style>