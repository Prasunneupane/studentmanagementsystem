<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Toaster } from '@/components/ui/sonner';
import { useFetchingData, type Teacher } from '@/composables/fetchCommonData';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import DialogueDelete from '@/pages/Dialogue/DialogueDelete.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import type { ColumnDef } from '@tanstack/vue-table';
import { Check, Edit, Plus, Trash2 } from 'lucide-vue-next';
import { h, ref } from 'vue';
import 'vue-sonner/style.css';
import DataTable from '../students/Datatable.vue';

import { usePermission } from '@/composables/usePermissions';
const { toast } = useToast();
const props = defineProps<{ teachers: Teacher[] }>();
const teachers = ref(props.teachers);
const selectedTeacher = ref<Teacher | null>(null);
const isDeleteOpen = ref(false);
const breadcrumbs = [
    { title: 'Teachers', href: '/teachers' },
    // { title: 'View Subjects', href: '/subjects/create' }
];
const { loading } = useFetchingData();
const { can } = usePermission();

const formatType = (value: string) => {
    return value
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
};
const columns: ColumnDef<Teacher>[] = [
    { accessorKey: 'id', header: 'ID', cell: ({ row }) => h('div', { class: 'font-medium' }, row.getValue('id')) },
    { accessorKey: 'name', header: 'Name' },
    { accessorKey: 'email', header: 'Email' },
    { accessorKey: 'phone', header: 'Phone No' },
    { accessorKey: 'address', header: 'address' },
    { accessorKey: 'subject_specialization', header: 'Subject Specializtion' },
    {
        accessorKey: 'status',
        header: 'Status',
        cell: ({ row }) => {
            const value = row.getValue('status') as string;
            return h('div', formatType(value));
        },
    },
    { accessorKey: 'qualification', header: 'Qualification' },
    {
        accessorKey: 'is_active',
        header: 'Status',
        cell: ({ row }) => {
            const value = row.getValue('is_active') as number;
            return h(
                'span',
                {
                    class: value
                        ? 'bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-semibold'
                        : 'bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs font-semibold',
                },
                value ? 'Active' : 'Inactive',
            );
        },
    },
    {
        id: 'actions',
        header: 'Actions',
        enableSorting: false,
        cell: ({ row }) => {
            const teacher = row.original;
            return h('div', { class: 'flex items-center gap-2' }, [
                can('teachers.canEdit') &&
                    h(
                        Button,
                        { variant: 'ghost', size: 'sm', class: 'h-8 w-8 p-0 cursor-pointer', title: 'Edit', onClick: () => handleEdit(teacher) },
                        () => h(Edit, { class: 'h-4 w-4' }),
                    ),
                can('teachers.canDelete') &&
                    h(
                        Button,
                        {
                            variant: 'ghost',
                            size: 'sm',
                            class: 'h-8 w-8 p-0 text-red-600 hover:text-red-700 hover:bg-red-50 cursor-pointer',
                            title: 'Delete',
                            onClick: () => handleDelete(teacher),
                        },
                        () => h(Trash2, { class: 'h-4 w-4' }),
                    ),
                teacher?.has_user
                    ? h(
                          Button,
                          {
                              variant: 'ghost',
                              size: 'sm',
                              class: 'h-8 w-8 p-0 text-green-600 hover:text-green-700 hover:bg-green-50 cursor-pointer',
                              title: 'User Exists',
                              onClick: () => {},
                          },
                          () => h(Check, { class: 'h-4 w-4' }),
                      )
                    : h(
                          Button,
                          {
                              variant: 'ghost',
                              size: 'sm',
                              class: 'h-8 w-8 p-0 text-blue-600 hover:text-blue-700 hover:bg-blue-50 cursor-pointer',
                              title: 'Create User',
                              onClick: () => router.get(route('users.create-from-teacher', teacher.id)),
                          },
                          () => h(Plus, { class: 'h-4 w-4' }),
                      ),
            ]);
        },
    },
];
const handleEdit = (teacher?: Teacher) => {
    const t = teacher;
    console.log(t, 'teacher');
    if (t) router.get(route('teachers.edit', t.id));

    // if (s) startEdit(s)
};

const handleDelete = (subject?: Teacher) => {
    if (subject) {
        selectedTeacher.value = subject;
        isDeleteOpen.value = true;
    }
};

const confirmDelete = async (id: string | number | null) => {
    console.log(id, 'id');

    if (!id) return;

    try {
        router.put(
            route('teachers.delete', id),
            {}, // Data object (empty since you're just soft deleting)
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Teacher deleted successfully');
                    // Remove from local list immediately
                    teachers.value = teachers.value.filter((s) => s.id !== id);
                    isDeleteOpen.value = false;
                    selectedTeacher.value = null;
                },
                onError: (errors) => {
                    toast.error('Failed to delete teacher');
                    console.error(errors);
                },
                onFinish: () => {
                    isDeleteOpen.value = false;
                    selectedTeacher.value = null;
                },
            },
        );
    } catch (err) {
        toast.error('Failed to delete teacher');
        console.error(err);
    }
};
</script>

<template>
    <Head title="View Teachers" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toaster />

        <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Students Table -->
            <Card class="w-full rounded-2xl shadow-lg">
                <CardHeader>
                    <CardTitle class="text-xl font-bold">
                        Teachers List
                        <Button v-if="can('teachers.canCreate')" as-child class="float-right ml-auto">
                            <Link :href="route('teachers.create')">
                                <Plus class="mr-2 h-4 w-4" />
                                Create Teacher
                            </Link>
                        </Button>
                    </CardTitle>
                </CardHeader>

                <CardContent class="pt-6">
                    <DataTable :columns="columns" :data="teachers" :loading="loading" title="Teachers List" />
                </CardContent>
            </Card>
        </div>
    </AppLayout>

    <DialogueDelete
        v-model="isDeleteOpen"
        :title="'Delete Teacher'"
        :description="'Are you sure you want to delete this teacher? This action cannot be undone.'"
        :item-name="selectedTeacher?.name"
        :item-id="selectedTeacher?.id"
        @confirm="confirmDelete(selectedTeacher?.id ?? null)"
    />
</template>

<style scoped>
/* Optional: Improve select trigger appearance when invalid */
</style>
