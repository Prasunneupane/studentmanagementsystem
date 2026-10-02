<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { paymentAttemptService, type PaymentAttemptSession, type PaymentGatewayOption } from '@/composables/usePaymentAttempt';
import { CheckCircle2, ExternalLink, Loader2, XCircle } from 'lucide-vue-next';
import QrcodeVue from 'qrcode.vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import PaymentAttemptSocketListener from './PaymentAttemptSocketListener.vue';

const props = defineProps<{
    open: boolean;
    invoiceId: number;
    gateway: PaymentGatewayOption | null;
    amount: number;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    settled: [];
}>();

type Phase = 'idle' | 'creating' | 'pending' | 'succeeded' | 'failed' | 'expired' | 'error';

const phase = ref<Phase>('idle');
const session = ref<PaymentAttemptSession | null>(null);
const errorMessage = ref('');
const nowTick = ref(Date.now());

let pollHandle: ReturnType<typeof setInterval> | null = null;
let clockHandle: ReturnType<typeof setInterval> | null = null;

const money = (value: number) => `Rs. ${Number(value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const secondsLeft = computed(() => {
    if (!session.value?.expiresAt) return null;
    const diff = Math.floor((new Date(session.value.expiresAt).getTime() - nowTick.value) / 1000);
    return Math.max(diff, 0);
});

const countdownLabel = computed(() => {
    if (secondsLeft.value === null) return '';
    const m = Math.floor(secondsLeft.value / 60);
    const s = secondsLeft.value % 60;
    return `${m}:${s.toString().padStart(2, '0')}`;
});

const stopPolling = () => {
    if (pollHandle) {
        clearInterval(pollHandle);
        pollHandle = null;
    }
};

const stopClock = () => {
    if (clockHandle) {
        clearInterval(clockHandle);
        clockHandle = null;
    }
};

const refreshStatus = async () => {
    if (!session.value) return;
    try {
        const result = await paymentAttemptService.status(props.invoiceId, session.value.attemptUuid);
        if (result.status === 'succeeded') {
            phase.value = 'succeeded';
            stopPolling();
            stopClock();
            emit('settled');
        } else if (result.status === 'failed') {
            phase.value = 'failed';
            stopPolling();
        } else if (result.status === 'expired') {
            phase.value = 'expired';
            stopPolling();
        }
        // still 'pending' on the server — leave phase as 'pending' and keep polling/listening
    } catch {
        // transient network error while polling — next tick/socket event will retry
    }
};

const startAttempt = async () => {
    if (!props.gateway) return;
    phase.value = 'creating';
    errorMessage.value = '';
    session.value = null;

    try {
        const result = await paymentAttemptService.createAttempt(props.invoiceId, props.gateway.value, props.amount);
        session.value = result;
        phase.value = 'pending';

        if (result.type === 'redirect' && result.redirectUrl) {
            window.open(result.redirectUrl, '_blank', 'noopener,noreferrer');
        }

        stopPolling();
        pollHandle = setInterval(refreshStatus, 4000);
        stopClock();
        clockHandle = setInterval(() => {
            nowTick.value = Date.now();
        }, 1000);
    } catch (error: any) {
        phase.value = 'error';
        errorMessage.value = error?.response?.data?.errors?.amount?.[0] ?? error?.response?.data?.message ?? 'Could not start the payment. Please try again.';
    }
};

const reopenCheckout = () => {
    if (session.value?.redirectUrl) {
        window.open(session.value.redirectUrl, '_blank', 'noopener,noreferrer');
    }
};

const onSocketUpdate = () => {
    // The socket payload is only a "something changed, go check" signal —
    // the actual status always comes from refreshStatus()'s server call.
    refreshStatus();
};

const close = () => {
    emit('update:open', false);
};

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            startAttempt();
        } else {
            stopPolling();
            stopClock();
            phase.value = 'idle';
            session.value = null;
        }
    },
);

onBeforeUnmount(() => {
    stopPolling();
    stopClock();
});
</script>

<template>
    <Dialog :open="open" @update:open="(value: boolean) => !value && close()">
        <DialogContent class="sm:max-w-sm">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: gateway?.color }"></span>
                    Pay with {{ gateway?.label }}
                </DialogTitle>
            </DialogHeader>

            <PaymentAttemptSocketListener v-if="session" :key="session.attemptUuid" :attempt-uuid="session.attemptUuid" @updated="onSocketUpdate" />

            <div class="space-y-4 py-2 text-center">
                <div class="flex justify-between rounded-md bg-slate-50 px-3 py-2 text-sm">
                    <span class="text-slate-500">Amount</span>
                    <span class="font-semibold tabular-nums">{{ money(amount) }}</span>
                </div>

                <div v-if="phase === 'creating'" class="flex flex-col items-center gap-2 py-6">
                    <Loader2 class="h-6 w-6 animate-spin text-slate-400" />
                    <p class="text-sm text-slate-500">Starting {{ gateway?.label }} checkout…</p>
                </div>

                <div v-else-if="phase === 'pending' && gateway?.sessionType === 'redirect'" class="flex flex-col items-center gap-3 py-4">
                    <Loader2 class="h-6 w-6 animate-spin text-slate-400" />
                    <p class="text-sm text-slate-600">
                        We opened {{ gateway?.label }} in a new tab. Complete the payment there — this screen updates automatically once it's confirmed.
                    </p>
                    <Button type="button" variant="outline" size="sm" @click="reopenCheckout">
                        <ExternalLink class="mr-1.5 h-3.5 w-3.5" />Reopen {{ gateway?.label }}
                    </Button>
                    <p v-if="countdownLabel" class="text-xs text-slate-400">Expires in {{ countdownLabel }}</p>
                </div>

                <div v-else-if="phase === 'pending' && gateway?.sessionType === 'qr' && session?.qrPayload" class="flex flex-col items-center gap-3 py-2">
                    <div class="rounded-lg border bg-white p-3">
                        <QrcodeVue :value="session.qrPayload" :size="200" />
                    </div>
                    <p class="text-sm text-slate-600">Scan with any {{ gateway?.label }}-enabled banking app</p>
                    <p v-if="countdownLabel" class="text-xs text-slate-400">Expires in {{ countdownLabel }}</p>
                </div>

                <div v-else-if="phase === 'succeeded'" class="flex flex-col items-center gap-2 py-6">
                    <CheckCircle2 class="h-10 w-10 text-emerald-600" />
                    <p class="text-sm font-medium text-emerald-700">Payment received</p>
                    <Badge variant="secondary" class="bg-emerald-100 text-emerald-700">{{ money(amount) }}</Badge>
                </div>

                <div v-else-if="phase === 'failed'" class="flex flex-col items-center gap-2 py-6">
                    <XCircle class="h-10 w-10 text-red-600" />
                    <p class="text-sm font-medium text-red-700">Payment was not completed</p>
                    <Button type="button" size="sm" @click="startAttempt">Try again</Button>
                </div>

                <div v-else-if="phase === 'expired'" class="flex flex-col items-center gap-2 py-6">
                    <XCircle class="h-10 w-10 text-amber-600" />
                    <p class="text-sm font-medium text-amber-700">This payment link expired</p>
                    <Button type="button" size="sm" @click="startAttempt">Start a new attempt</Button>
                </div>

                <div v-else-if="phase === 'error'" class="flex flex-col items-center gap-2 py-6">
                    <XCircle class="h-10 w-10 text-red-600" />
                    <p class="text-sm text-red-700">{{ errorMessage }}</p>
                    <Button type="button" size="sm" @click="startAttempt">Try again</Button>
                </div>

                <Button v-if="phase !== 'creating'" type="button" variant="ghost" size="sm" class="w-full" @click="close">Close</Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
