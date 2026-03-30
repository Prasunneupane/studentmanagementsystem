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
import { useStudentData, type Terms } from '@/composables/fetchData'

import {usePermission} from '@/composables/usePermissions'

const {can} = usePermission();

const { toast } = useToast()
const props = defineProps<{ terms: Terms[] }>()
const terms = ref(props.terms)
const selectedTerms = ref<Terms | null>(null)
const isDeleteOpen = ref(false)
const breadcrumbs = [
  { title: 'Terms', href: '/terms' },
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
const columns: ColumnDef<Terms>[] = [
  { accessorKey: 'id', header: 'ID', cell: ({ row }) => h('div', { class: 'font-medium' }, row.getValue('id')) },
  { accessorKey: 'name', header: 'Term Name' },
  { accessorKey: 'term_number', header: 'Term Number' },
  // { accessorKey: 'code', header: 'Subject Code' },
  // { accessorKey: 'type', header: 'Subject Type',cell: ({ row }) => {
  //     const value = row.getValue('type') as string
  //     return h('div', formatType(value))
  //   } },
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
        can('terms.canEdit') &&
        h(Button, { variant: 'ghost', size: 'sm', class: 'h-8 w-8 p-0 cursor-pointer', title: 'Edit', onClick: () => handleEdit(subject) }, () => h(Edit, { class: 'h-4 w-4' })),
        can('terms.canDelete') &&
        h(Button, { variant: 'ghost', size: 'sm', class: 'h-8 w-8 p-0 text-red-600 hover:text-red-700 hover:bg-red-50 cursor-pointer', title: 'Delete', onClick: () => handleDelete(subject) }, () => h(Trash2, { class: 'h-4 w-4' })),
      ])
    },
  },
]
const handleEdit = (term?: Terms) => {
  const s = term 
  console.log(s,"termdetails");
  if (s) router.get(route('terms.edit', s.id));
  
  // if (s) startEdit(s)
}

const handleDelete = (term?: Terms) => {
  if (term) {
    selectedTerms.value = term
    isDeleteOpen.value = true
  }
}



const confirmDelete = async (id: string | number | null) => {
  console.log(id, "id");
  
  if (!id) return

  try {
    router.put(
      route('terms.delete', id),
      {}, // Data object (empty since you're just soft deleting)
      {
        preserveScroll: true,
        onSuccess: () => {
          toast.success('Term deleted successfully')
          // Remove from local list immediately
          terms.value = terms.value.filter(s => s.id !== id)
          isDeleteOpen.value = false
          selectedTerms.value = null
        },
        onError: (errors) => {
          toast.error('Failed to delete term')
          console.error(errors)
        },
        onFinish: () => {
          isDeleteOpen.value = false
          selectedTerms.value = null
        }
      }
    )
  } catch (err) {
    toast.error('Failed to delete term')
    console.error(err)
  }
}
</script>

<template>
  <Head title="View Terms" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <Toaster />

    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">
      

      <!-- Students Table -->
      <Card class="w-full shadow-lg rounded-2xl">
        <CardHeader>
          <CardTitle class="text-xl font-bold">
            Term List 
                <Button v-if="can('terms.canCreate')" as-child class="ml-auto float-right">
                  <Link :href="route('terms.create')">
                    <Plus class="w-4 h-4 mr-2" />
                    Create Term
                  </Link>
                </Button>
          </CardTitle>
        </CardHeader>
        
        <CardContent class="pt-6">
          <DataTable :columns="columns" :data="terms" :loading="loading" title="Term List" />
        </CardContent>
      </Card>
    </div>
  </AppLayout>

  <DialogueDelete
     v-model="isDeleteOpen"
    :title="'Delete Term'"
    :description="'Are you sure you want to delete this term? This action cannot be undone.'"
    :item-name="selectedTerms?.name"
    :item-id="selectedTerms?.id"
    @confirm="confirmDelete(selectedTerms?.id ?? null)"
  />
</template>

<style scoped>
/* Optional: Improve select trigger appearance when invalid */
</style>