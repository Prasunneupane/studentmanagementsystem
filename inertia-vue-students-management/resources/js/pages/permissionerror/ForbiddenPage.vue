<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ShieldX, Home, ArrowLeft } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

defineProps<{
  message?: string;
}>();
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-red-50 to-orange-50 dark:from-gray-900 dark:to-gray-800 p-4">
    <Head title="Access Forbidden" />
    
    <Card class="w-full max-w-md">
      <CardHeader class="text-center space-y-4">
        <div class="mx-auto w-20 h-20 bg-red-100 dark:bg-red-900/20 rounded-full flex items-center justify-center">
          <ShieldX class="w-10 h-10 text-red-600 dark:text-red-400" />
        </div>
        <CardTitle class="text-3xl font-bold">Access Forbidden</CardTitle>
        <CardDescription class="text-base">
          {{ message || 'You do not have permission to access this page.' }}
        </CardDescription>
      </CardHeader>
      
      <CardContent class="space-y-4">
        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
          <p class="text-sm text-amber-800 dark:text-amber-200">
            If you believe this is an error, please contact your administrator to request the necessary permissions.
          </p>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-3">
          <Button
            as-child
            variant="outline"
            class="flex-1"
          >
            <Link href="$page.props.previous || '/dashboard'" class="flex items-center justify-center gap-2">
              <ArrowLeft class="w-4 h-4" />
              Go Back
            </Link>
          </Button>
          
          <Button
            as-child
            class="flex-1"
          >
            <Link href="/dashboard" class="flex items-center justify-center gap-2">
              <Home class="w-4 h-4" />
              Dashboard
            </Link>
          </Button>
        </div>
      </CardContent>
    </Card>
  </div>
</template>