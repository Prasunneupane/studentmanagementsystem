<script setup lang="ts">
// Isolated into its own component because useEcho() captures the channel
// name once at setup() time — it is not reactive. The parent modal mounts a
// fresh instance of this component (via :key="attemptUuid") whenever a new
// attempt is created, so each attempt gets its own correctly-named
// subscription instead of trying to re-point one long-lived listener.
import { useEcho } from '@laravel/echo-vue';

const props = defineProps<{ attemptUuid: string }>();
const emit = defineEmits<{ updated: [payload: { status: string; amount: number; gateway: string }] }>();

useEcho(`payment-attempts.${props.attemptUuid}`, '.PaymentAttemptUpdated', (payload: any) => {
    emit('updated', payload);
});
</script>

<template></template>
