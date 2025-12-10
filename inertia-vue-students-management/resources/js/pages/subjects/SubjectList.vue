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
import type { ColumnDef } from '@tanstack/vue-table'
import { useStudentData, type Subject } from '@/composables/fetchData'

const { toast } = useToast()
const props = defineProps<{ subjects: Subject[] }>()
const subject = ref(props.subjects)
const selectedSubject = ref<Subject | null>(null)
const isDeleteOpen = ref(false)
const breadcrumbs = [
  { title: 'Subjects', href: '/subjects' },
  // { title: 'View Subjects', href: '/subjects/create' }
]
const {
  
  loading,
  
} = useStudentData();
const formatType = (value: string) => {
  return value
    .split('_')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ')
}
const columns: ColumnDef<Subject>[] = [
  { accessorKey: 'id', header: 'ID', cell: ({ row }) => h('div', { class: 'font-medium' }, row.getValue('id')) },
  { accessorKey: 'name', header: 'Subject Name' },
  { accessorKey: 'code', header: 'Subject Code' },
  // { accessorKey: 'code', header: 'Subject Code' },
  { accessorKey: 'type', header: 'Subject Type',cell: ({ row }) => {
      const value = row.getValue('type') as string
      return h('div', formatType(value))
    } },
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
      const subject = row.original
      return h('div', { class: 'flex items-center gap-2' }, [
        // h(Button, { variant: 'ghost', size: 'sm', class: 'h-8 w-8 p-0', title: 'View', onClick: () => handleView(student) }, () => h(Eye, { class: 'h-4 w-4' })),
        h(Button, { variant: 'ghost', size: 'sm', class: 'h-8 w-8 p-0 cursor-pointer', title: 'Edit', onClick: () => handleEdit(subject) }, () => h(Edit, { class: 'h-4 w-4' })),
        h(Button, { variant: 'ghost', size: 'sm', class: 'h-8 w-8 p-0 text-red-600 hover:text-red-700 hover:bg-red-50 cursor-pointer', title: 'Delete', onClick: () => handleDelete(subject) }, () => h(Trash2, { class: 'h-4 w-4' })),
      ])
    },
  },
]
const handleEdit = (subject?: Subject) => {
  const s = subject 
  console.log(s,"studentdetails");
  
  // if (s) startEdit(s)
}

const handleDelete = (subject?: Subject) => {
  if (subject) {
    selectedSubject.value = subject
    isDeleteOpen.value = true
  }
}



const confirmDelete = async (id: string | number | null) => {
  console.log(id, "id");
  
  if (!id) return

  try {
    router.put(
      route('subjects.delete', id),
      {}, // Data object (empty since you're just soft deleting)
      {
        preserveScroll: true,
        onSuccess: () => {
          toast.success('Subject deleted successfully')
          // Remove from local list immediately
          subject.value = subject.value.filter(s => s.id !== id)
          isDeleteOpen.value = false
          selectedSubject.value = null
        },
        onError: (errors) => {
          toast.error('Failed to delete subject')
          console.error(errors)
        },
        onFinish: () => {
          isDeleteOpen.value = false
          selectedSubject.value = null
        }
      }
    )
  } catch (err) {
    toast.error('Failed to delete subject')
    console.error(err)
  }
}
</script>

<template>
  <Head title="View Subjects" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <Toaster />

    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">
      

      <!-- Students Table -->
      <Card class="w-full shadow-lg rounded-2xl">
        <CardHeader>
          <CardTitle class="text-xl font-bold">
            Subject List 
                <Button as-child class="ml-auto float-right">
                  <Link :href="route('subjects.create')">
                    <Plus class="w-4 h-4 mr-2" />
                    Create Subject
                  </Link>
                </Button>
          </CardTitle>
        </CardHeader>
        
        <CardContent class="pt-6">
          <DataTable :columns="columns" :data="subject" :loading="loading" title="Subject List" />
        </CardContent>
      </Card>
    </div>
  </AppLayout>

  <DialogueDelete
     v-model="isDeleteOpen"
    :title="'Delete Subject'"
    :description="'Are you sure you want to delete this subject? This action cannot be undone.'"
    :item-name="selectedSubject?.name"
    :item-id="selectedSubject?.id"
    @confirm="confirmDelete(selectedSubject?.id ?? null)"
  />
</template>

<style scoped>
/* Optional: Improve select trigger appearance when invalid */
</style>