<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Toaster } from '@/components/ui/sonner';
import ImageDropzone from '@/components/ui/image-dropzone/ImageDropZone.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Eye } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import 'vue-sonner/style.css';

const breadcrumbs = [
    { title: 'Company Info', href: '/company-info' },
    { title: 'Add', href: '/company-info/create' },
];

const form = useForm({
    company_name: '',
    company_address: '',
    company_pan_no: '',
    company_phone_no: '',
    company_mobile_no: '',
    company_email: '',
    company_website: '',
    logo: null as File | null,
    is_active: true as boolean,
});

const handleUploadError = (message: string) => toast.error(message);

const submit = () => {
    form.post(route('company-info.store'), {
        forceFormData: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Add Company Info" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toaster />
        <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">
            <Card class="w-full rounded-2xl shadow-lg">
                <CardHeader class="flex flex-row items-center justify-between">
                    <div>
                        <CardTitle>Add Company Info</CardTitle>
                        <CardDescription>Add a company/school record. Marking it active makes it the one shown everywhere.</CardDescription>
                    </div>
                    <Button as-child >
                        <Link :href="route('company-info.index')"><Eye class="mr-2 h-4 w-4" />View list</Link>
                    </Button>
                </CardHeader>

                <CardContent class="space-y-6">
                    <form @submit.prevent="submit">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                            <div class="space-y-2">
                                <Label for="company_name">Company name *</Label>
                                <Input id="company_name" v-model="form.company_name" placeholder="School / company name" />
                                <p v-if="form.errors.company_name" class="text-sm text-destructive">{{ form.errors.company_name }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="company_pan_no">PAN number</Label>
                                <Input id="company_pan_no" v-model="form.company_pan_no" placeholder="PAN no." />
                                <p v-if="form.errors.company_pan_no" class="text-sm text-destructive">{{ form.errors.company_pan_no }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="company_email">Email</Label>
                                <Input id="company_email" type="email" v-model="form.company_email" placeholder="info@school.edu.np" />
                                <p v-if="form.errors.company_email" class="text-sm text-destructive">{{ form.errors.company_email }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="company_phone_no">Phone number</Label>
                                <Input id="company_phone_no" v-model="form.company_phone_no" placeholder="+977-1-XXXXXXX" />
                                <p v-if="form.errors.company_phone_no" class="text-sm text-destructive">{{ form.errors.company_phone_no }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="company_mobile_no">Mobile number</Label>
                                <Input id="company_mobile_no" v-model="form.company_mobile_no" placeholder="+977-98XXXXXXXX" />
                                <p v-if="form.errors.company_mobile_no" class="text-sm text-destructive">{{ form.errors.company_mobile_no }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="company_website">Website</Label>
                                <Input id="company_website" v-model="form.company_website" placeholder="https://school.edu.np" />
                                <p v-if="form.errors.company_website" class="text-sm text-destructive">{{ form.errors.company_website }}</p>
                            </div>
                        </div>

                        <div class="mt-6 space-y-2">
                            <Label for="company_address">Address</Label>
                            <Textarea id="company_address" v-model="form.company_address" placeholder="Full address" rows="2" />
                            <p v-if="form.errors.company_address" class="text-sm text-destructive">{{ form.errors.company_address }}</p>
                        </div>

                        <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div class="space-y-2">
                                <ImageDropzone v-model="form.logo" :multiple="false" label="Logo" description="Shown in the app header, invoices, and print pages" @error="handleUploadError" />
                                <p v-if="form.errors.logo" class="text-sm text-destructive">{{ form.errors.logo }}</p>
                            </div>
                            <div class="flex items-center gap-2 self-start pt-8">
                                <input id="is_active" type="checkbox" v-model="form.is_active" class="h-4 w-4 accent-blue-600" />
                                <Label for="is_active">Make this the active company info</Label>
                            </div>
                        </div>

                        <CardFooter class="justify-end gap-3 px-0">
                            <Button type="button" variant="outline" :disabled="form.processing" @click="form.reset()">Reset</Button>
                            <Button type="submit" :disabled="form.processing">{{ form.processing ? 'Saving...' : 'Create' }}</Button>
                        </CardFooter>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
