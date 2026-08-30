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
const form = useForm({
    title: '',
    description: '',
    main_image: null as File | null,
    images: [] as File[],
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
                        <div class="space-y-2">
                            <Label for="title">Title</Label>
                            <Input id="title" v-model="form.title" placeholder="Event title" />
                            <p v-if="form.errors.title" class="text-sm text-destructive">{{ form.errors.title }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="description">Description</Label>
                            <Textarea id="description" v-model="form.description" placeholder="Event description"
                                rows="4" />
                            <p v-if="form.errors.description" class="text-sm text-destructive">
                                {{ form.errors.description }}
                            </p>
                        </div>

                        <!-- Main image: single only -->
                        <ImageDropzone v-model="form.main_image" :multiple="false" label="Main image"
                            description="This is the cover image shown on event listings" @error="handleUploadError" />
                        <p v-if="form.errors.main_image" class="text-sm text-destructive">
                            {{ form.errors.main_image }}
                        </p>

                        <!-- Gallery: multiple images -->
                        <ImageDropzone v-model="form.images" :multiple="true" :max-files="10" label="Event images"
                            description="Add photos for the event gallery" @error="handleUploadError" />
                        <p v-if="form.errors.images" class="text-sm text-destructive">
                            {{ form.errors.images }}
                        </p>
                    </CardContent>

                    <CardFooter class="justify-end gap-2">
                        <Button type="button" variant="outline" :disabled="form.processing" @click="form.reset()">
                            Reset
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Saving...' : 'Create Event' }}
                        </Button>
                    </CardFooter>
                </form>
            </Card>
        </div>
    </AppLayout>
</template>
