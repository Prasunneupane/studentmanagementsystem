<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Home, ShieldX } from 'lucide-vue-next';

const props = defineProps<{
    message?: string;
    previous: string;
}>();
console.log(props.previous, 'props.vr');
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-red-50 to-orange-50 p-4 dark:from-gray-900 dark:to-gray-800">
        <Head title="Access Forbidden" />

        <Card class="w-full max-w-md">
            <CardHeader class="space-y-4 text-center">
                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/20">
                    <ShieldX class="h-10 w-10 text-red-600 dark:text-red-400" />
                </div>
                <CardTitle class="text-3xl font-bold">Access Forbidden</CardTitle>
                <CardDescription class="text-base">
                    {{ message || 'You do not have permission to access this page.' }}
                </CardDescription>
            </CardHeader>

            <CardContent class="space-y-4">
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
                    <p class="text-sm text-amber-800 dark:text-amber-200">
                        If you believe this is an error, please contact your administrator to request the necessary permissions.
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <Button as-child variant="outline" class="flex-1">
                        <Link :href="previous" class="flex items-center justify-center gap-2">
                            <ArrowLeft class="h-4 w-4" />
                            Go Back
                        </Link>
                    </Button>

                    <Button as-child class="flex-1">
                        <Link href="/dashboard" class="flex items-center justify-center gap-2">
                            <Home class="h-4 w-4" />
                            Dashboard
                        </Link>
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
