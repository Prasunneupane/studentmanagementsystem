<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Toaster } from '@/components/ui/sonner';
import { useStudentData, type Event, type EventGalleryImage } from '@/composables/fetchData';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import DialogueDelete from '@/pages/Dialogue/DialogueDelete.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import type { ColumnDef } from '@tanstack/vue-table';
import { Edit, Images, Plus, Trash2 } from 'lucide-vue-next';
import { h, ref } from 'vue';
import 'vue-sonner/style.css';
import DataTable from '../students/Datatable.vue';
import ImageGalleryDialog from '../../components/ui/image-gallery/ImageGalleryDialog.vue';

import { usePermission } from '@/composables/usePermissions';

const { can } = usePermission();
const { toast } = useToast();

const props = defineProps<{ events: Event[] }>();
const event = ref(props.events);
const selectedEvent = ref<Event | null>(null);
const isDeleteOpen = ref(false);
const { loading } = useStudentData();

const breadcrumbs = [{ title: 'Events', href: '/events' }];

// gallery state
const isGalleryOpen = ref(false);
const galleryImages = ref<EventGalleryImage[]>([]);
const galleryStartIndex = ref(0);

const buildGallery = (evt: Event): EventGalleryImage[] => [{ id: -1, url: evt.banner_image }, ...(evt.images ?? [])];

const openGallery = (evt: Event, startIndex = 0) => {
    galleryImages.value = buildGallery(evt);
    galleryStartIndex.value = startIndex;
    isGalleryOpen.value = true;
};

const columns: ColumnDef<Event>[] = [
    { accessorKey: 'id', header: 'ID', cell: ({ row }) => h('div', { class: 'font-medium' }, row.getValue('id')) },
    {
        id: 'image',
        header: 'Image',
        enableSorting: false,
        cell: ({ row }) => {
            const evt = row.original;
            const extraCount = evt.images?.length ?? 0;

            return h('div', { class: 'relative h-14 w-20' }, [
                h('img', {
                    src: evt.banner_image,
                    alt: evt.title,
                    class: 'h-14 w-20 rounded-md object-cover cursor-pointer transition hover:opacity-80',
                    onClick: () => openGallery(evt, 0),
                }),
                extraCount > 0 &&
                    h(
                        'span',
                        { class: 'absolute -top-1.5 -right-1.5 rounded-full bg-black/70 px-1.5 py-0.5 text-[10px] font-semibold text-white' },
                        `+${extraCount}`,
                    ),
            ]);
        },
    },
    { accessorKey: 'title', header: 'Title' },
    {
        accessorKey: 'start_date',
        header: 'Start Date',
        cell: ({ row }) => h('div', new Date(row.getValue('start_date') as string).toLocaleDateString()),
    },
    {
        accessorKey: 'end_date',
        header: 'End Date',
        cell: ({ row }) => h('div', new Date(row.getValue('end_date') as string).toLocaleDateString()),
    },
    { accessorKey: 'location', header: 'Location', cell: ({ row }) => h('div', row.getValue('location') || '-') },
    {
        accessorKey: 'status',
        header: 'Status',
        cell: ({ row }) => {
            const value = row.getValue('status') as string;
            const styles: Record<string, string> = {
                upcoming: 'bg-blue-100 text-blue-800',
                ongoing: 'bg-yellow-100 text-yellow-800',
                completed: 'bg-green-100 text-green-800',
                cancelled: 'bg-red-100 text-red-800',
            };
            return h(
                'span',
                { class: `px-2 py-1 rounded-full text-xs font-semibold ${styles[value] ?? 'bg-gray-100 text-gray-800'}` },
                value.charAt(0).toUpperCase() + value.slice(1),
            );
        },
    },
    {
        id: 'gallery',
        header: 'Gallery',
        enableSorting: false,
        cell: ({ row }) => {
            const evt = row.original;
            if (!evt.images?.length) return h('div', { class: 'text-xs text-muted-foreground' }, '-');

            return h(
                Button,
                { variant: 'outline', size: 'sm', class: 'h-8 cursor-pointer gap-1', onClick: () => openGallery(evt, 0) },
                () => [h(Images, { class: 'h-4 w-4' }), `View all (${evt.images.length + 1})`],
            );
        },
    },
    {
        id: 'actions',
        header: 'Actions',
        enableSorting: false,
        cell: ({ row }) => {
            const evt = row.original;
            return h('div', { class: 'flex items-center gap-2' }, [
                can('events.canEdit') &&
                    h(
                        Button,
                        { variant: 'ghost', size: 'sm', class: 'h-8 w-8 p-0 cursor-pointer', title: 'Edit', onClick: () => handleEdit(evt) },
                        () => h(Edit, { class: 'h-4 w-4' }),
                    ),
                can('events.canDelete') &&
                    h(
                        Button,
                        {
                            variant: 'ghost',
                            size: 'sm',
                            class: 'h-8 w-8 p-0 text-red-600 hover:text-red-700 hover:bg-red-50 cursor-pointer',
                            title: 'Delete',
                            onClick: () => handleDelete(evt),
                        },
                        () => h(Trash2, { class: 'h-4 w-4' }),
                    ),
            ]);
        },
    },
];

const handleEdit = (evt?: Event) => {
    if (evt) router.get(route('events.edit', evt.id));
};

const handleDelete = (evt?: Event) => {
    if (evt) {
        selectedEvent.value = evt;
        isDeleteOpen.value = true;
    }
};

const confirmDelete = async (id: string | number | null) => {
    if (!id) return;

    try {
        router.put(
            route('events.delete', id),
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Event deleted successfully');
                    event.value = event.value.filter((e) => e.id !== id);
                    isDeleteOpen.value = false;
                    selectedEvent.value = null;
                },
                onError: (errors) => {
                    toast.error('Failed to delete event');
                    console.error(errors);
                },
                onFinish: () => {
                    isDeleteOpen.value = false;
                    selectedEvent.value = null;
                },
            },
        );
    } catch (err) {
        toast.error('Failed to delete event');
        console.error(err);
    }
};
</script>

<template>
    <Head title="View Events" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toaster />

        <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">
            <Card class="w-full rounded-2xl shadow-lg">
                <CardHeader>
                    <CardTitle class="text-xl font-bold">
                        Event List
                        <Button v-if="can('events.canCreate')" as-child class="float-right ml-auto">
                            <Link :href="route('events.create')">
                                <Plus class="mr-2 h-4 w-4" />
                                Create Event
                            </Link>
                        </Button>
                    </CardTitle>
                </CardHeader>

                <CardContent class="pt-6">
                    <DataTable :columns="columns" :data="event" :loading="loading" title="Event List" />
                </CardContent>
            </Card>
        </div>
    </AppLayout>

    <DialogueDelete
        v-model="isDeleteOpen"
        :title="'Delete Event'"
        :description="'Are you sure you want to delete this event? This action cannot be undone.'"
        :item-name="selectedEvent?.title"
        :item-id="selectedEvent?.id"
        @confirm="confirmDelete(selectedEvent?.id ?? null)"
    />

    <ImageGalleryDialog v-model:open="isGalleryOpen" :images="galleryImages" :start-index="galleryStartIndex" />
</template>