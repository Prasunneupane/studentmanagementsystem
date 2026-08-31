<script setup lang="ts">
/**
 * EventForm.vue
 * -------------------------------------------------------------
 * Example "create event" form showing how to use ImageDropzone:
 *  - `mainImage`   -> single image   (multiple = false)
 *  - `galleryImages` -> multiple images (multiple = true)
 *
 * Submits via Inertia's useForm + FormData (needed because we're
 * sending files). Adjust the route name / field names to match
 * your EventController.
 */
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Button } from '@/components/ui/button'
import ImageDropzone from '@/components/ui/image-dropzone/ImageDropZone.vue'
import { toast } from 'vue-sonner' // swap for whatever toast lib you use, or drop this
import { Toaster } from '@/components/ui/sonner';
import CustomSelect from '../CustomSelect.vue';
import DatePicker from '@/components/ui/customdatepicker/CustomDatePicker.vue';

const props = defineProps<{
    statusOptions: { value: string; label: string }[],
    eventTypeOptions: { value: string; label: string }[],
}>()
console.log(new Date().toISOString().split('T')[0], "start date")
console.log(new Date().toISOString().split('T')[0], "end date")
const form = useForm({
    title: '',
    status: 'upcoming',
    event_type: 'holiday',
    start_date: new Date().toISOString().split('T')[0], // default to today
    end_date: new Date().toISOString().split('T')[0], // default to today
    description: '',
    location: '',
    banner_image: null as File | null,
    gallery_images: [] as File[],
})
const breadcrumbs = [{ title: 'Events', href: '/events' }];

function handleUploadError(message: string) {
    toast.error(message)
}

function submit() {
    form.post(route('events.store'), {
        forceFormData: true, // required since we have files in the payload
        onSuccess: () => form.reset(),
    })
}
</script>

<template>

    <Head title="View Events" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toaster />
        <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">
            <Card class="w-full rounded-2xl shadow-lg">
                <form @submit.prevent="submit">
                    <CardHeader>
                        <CardTitle>Create Event</CardTitle>
                        <CardDescription>Fill in the event details and upload images.</CardDescription>
                    </CardHeader>

                    <CardContent class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="title">Title</Label>
                                <Input id="title" v-model="form.title" placeholder="Event title" />
                                <p v-if="form.errors.title" class="text-sm text-destructive">{{ form.errors.title }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label for="status">Status</Label>
                                <CustomSelect id="status" v-model="form.status" placeholder="Event status"
                                    :options="props.statusOptions" />
                                <p v-if="form.errors.status" class="text-sm text-destructive">{{ form.errors.status }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="event_type">Event Type</Label>
                                <CustomSelect id="event_type" v-model="form.event_type" placeholder="Event type"
                                    :options="props.eventTypeOptions" />
                                <p v-if="form.errors.event_type" class="text-sm text-destructive">{{
                                    form.errors.event_type }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label for="event_type">Event Start Date</Label>
                                <DatePicker id="start_date" v-model="form.start_date"
                                    placeholder="Event start date" />
                                <p v-if="form.errors.start_date" class="text-sm text-destructive">{{
                                    form.errors.start_date }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="location">Location</Label>
                                <Input id="location" v-model="form.location" placeholder="Event location" />
                                <p v-if="form.errors.location" class="text-sm text-destructive">{{ form.errors.location
                                    }}</p>
                            </div>


                            <div class="space-y-2">
                                <Label for="event_type">Event End Date</Label>
                                <DatePicker id="end_date" v-model="form.end_date"
                                    placeholder="Event end date" />
                                <p v-if="form.errors.end_date" class="text-sm text-destructive">{{
                                    form.errors.end_date }}</p>
                            </div>

                        </div>
                        <div class="space-y-2">
                                    <Label for="description">Description</Label>
                                    <Textarea id="description" v-model="form.description"
                                        placeholder="Event description" rows="4" />
                                    <p v-if="form.errors.description" class="text-sm text-destructive">
                                        {{ form.errors.description }}
                                    </p>
                                </div>
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div class="space-y-2">
                                <!-- Main image: single only -->
                                <ImageDropzone v-model="form.banner_image" :multiple="false" label="Banner image"
                                    description="This is the banner image for the event"
                                    @error="handleUploadError" />
                                <p v-if="form.errors.banner_image" class="text-sm text-destructive">
                                    {{ form.errors.banner_image }}
                                </p>
                            </div>
                            <div class="space-y-2">
                                <!-- Gallery: multiple images -->
                                <ImageDropzone v-model="form.gallery_images" :multiple="true" :max-files="10"
                                    label="Event images" description="Add photos for the event gallery"
                                    @error="handleUploadError" />
                                <p v-if="form.errors.gallery_images" class="text-sm text-destructive">
                                    {{ form.errors.gallery_images }}
                                </p>
                            </div>
                            </div>

                            <!-- <div class="grid grid-cols-1 gap-6 md:grid-cols-2"> -->
                                
                            <!-- </div> -->
                    </CardContent>
                    <br>
                    <CardFooter class="justify-end gap-6">
                    <div class="flex justify-between gap-3 border-t px-6 py-4">
                        
                            <Button type="button" variant="outline" :disabled="form.processing" @click="form.reset()">
                                Reset
                            </Button>
                            <Button type="submit" :disabled="form.processing">
                                {{ form.processing ? 'Saving...' : 'Create Event' }}
                            </Button>
                       
                    </div>
                    </CardFooter>
                </form>
            </Card>
        </div>
    </AppLayout>
</template>
<!--  -->
