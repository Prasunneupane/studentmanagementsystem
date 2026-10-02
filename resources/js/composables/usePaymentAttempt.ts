import axios from 'axios';

export interface PaymentGatewayOption {
    value: string;
    label: string;
    icon?: string;
    color?: string;
    sessionType: 'redirect' | 'qr';
}

export interface PaymentAttemptSession {
    attemptUuid: string;
    gateway: string;
    type: 'redirect' | 'qr';
    redirectUrl: string | null;
    qrPayload: string | null;
    amount: number;
    expiresAt: string | null;
}

export interface PaymentAttemptStatus {
    attemptUuid: string;
    status: 'pending' | 'succeeded' | 'failed' | 'expired';
    amount: number;
    outstandingBalance: number;
}

export const paymentAttemptService = {
    async createAttempt(invoiceId: number, gateway: string, amount: number): Promise<PaymentAttemptSession> {
        const { data } = await axios.post(route('payment-attempts.store', invoiceId), { gateway, amount });
        return data;
    },

    async status(invoiceId: number, attemptUuid: string): Promise<PaymentAttemptStatus> {
        const { data } = await axios.get(route('payment-attempts.status', [invoiceId, attemptUuid]));
        return data;
    },
};
