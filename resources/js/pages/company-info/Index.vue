<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Toaster } from '@/components/ui/sonner';
import { usePermission } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import DialogueDelete from '@/pages/Dialogue/DialogueDelete.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import type { ColumnDef } from '@tanstack/vue-table';
import { Edit, Plus, Trash2 } from 'lucide-vue-next';
import { computed, h, onMounted, ref } from 'vue';
import 'vue-sonner/style.css';
import DataTable from '../students/Datatable.vue';

interface CompanyInfoRow {
    id: number;
    company_name: string;
    company_address: string | null;
    company_phone_no: string | null;
    company_mobile_no: string | null;
    company_email: string | null;
    company_website: string | null;
    logo_url: string | null;
    is_active: boolean;
}

const { can } = usePermission();
const { toast } = useToast();
const page = usePage();

const props = defineProps<{ companies: CompanyInfoRow[] }>();
const companies = ref(props.companies);
const hasActiveCompany = computed(() => companies.value.some((company) => company.is_active));
const selected = ref<CompanyInfoRow | null>(null);
const isDeleteOpen = ref(false);

onMounted(() => {
    const { success, error } = page.props.flash;

    if (success) toast.success(success);
    if (error) toast.error(error);
});

const breadcrumbs = [{ title: 'Company Info', href: '/company-info' }];

const handleDelete = (company: CompanyInfoRow) => {
    selected.value = company;
    isDeleteOpen.value = true;
};

const confirmDelete = (id: number | string | null) => {
    if (!id) return;

    router.delete(route('company-info.destroy', id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Company info deleted successfully');
            companies.value = companies.value.filter((c) => c.id !== id);
        },
        onError: () => toast.error('Failed to delete company info'),
        onFinish: () => {
            isDeleteOpen.value = false;
            selected.value = null;
        },
    });
};

const columns: ColumnDef<CompanyInfoRow>[] = [
    {
        id: 'logo',
        header: 'Logo',
        enableSorting: false,
        meta: { excludeFromExport: true },
        cell: ({ row }) =>
            row.original.logo_url
                ? h('img', { src: row.original.logo_url, alt: row.original.company_name, class: 'h-10 w-10 rounded-md object-cover' })
                : h('div', { class: 'h-10 w-10 rounded-md bg-slate-100' }),
    },
    { accessorKey: 'company_name', header: 'Company name' },
    { accessorKey: 'company_address', header: 'Address', cell: ({ row }) => h('div', row.original.company_address || '-') },
    {
        id: 'contact',
        header: 'Contact',
        enableSorting: false,
        meta: { exportValue: (row: CompanyInfoRow) => [row.company_phone_no, row.company_mobile_no, row.company_email].filter(Boolean).join(' / ') },
        cell: ({ row }) => h('div', { class: 'text-sm text-slate-500' }, [row.original.company_phone_no, row.original.company_mobile_no, row.original.company_email].filter(Boolean).join(' / ') || '-'),
    },
    {
        accessorKey: 'is_active',
        header: 'Status',
        cell: ({ row }) =>
            h(Badge, { variant: 'secondary', class: row.original.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }, () => (row.original.is_active ? 'Active' : 'Inactive')),
    },
    {
        id: 'actions',
        header: 'Actions',
        enableSorting: false,
        meta: { excludeFromExport: true },
        cell: ({ row }) => {
            const company = row.original;
            return h('div', { class: 'flex justify-end gap-1' }, [
                can('companyInfo.canEdit') &&
                    h(Button, { variant: 'ghost', size: 'icon', title: 'Edit', onClick: () => router.visit(route('company-info.edit', company.id)) }, () => h(Edit, { class: 'h-4 w-4' })),
                can('companyInfo.canDelete') &&
                    h(Button, { variant: 'ghost', size: 'icon', class: 'text-red-600', title: 'Delete', onClick: () => handleDelete(company) }, () => h(Trash2, { class: 'h-4 w-4' })),
            ]);
        },
    },
];
</script>

<template>
    <Head title="Company Info" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toaster />

        <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">
            <Card class="w-full rounded-2xl shadow-lg">
                <CardHeader>
                    <CardTitle class="text-xl font-bold">
                        Company Info
                        <Button v-if="can('companyInfo.canCreate') && !hasActiveCompany" as-child class="float-right ml-auto">
                            <Link :href="route('company-info.create')">
                                <Plus class="mr-2 h-4 w-4" />
                                Add Company Info
                            </Link>
                        </Button>
                    </CardTitle>
                </CardHeader>

                <CardContent class="pt-6">
                    <DataTable :columns="columns" :data="companies" title="Company Info" />
                </CardContent>
            </Card>
        </div>
    </AppLayout>

    <DialogueDelete
        v-model="isDeleteOpen"
        title="Delete company info"
        description="Are you sure you want to delete this company info? This action cannot be undone."
        :item-name="selected?.company_name"
        :item-id="selected?.id"
        @confirm="confirmDelete(selected?.id ?? null)"
    />
</template>
