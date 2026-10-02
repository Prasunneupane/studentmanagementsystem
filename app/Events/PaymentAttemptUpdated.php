<?php

namespace App\Events;

use App\Models\PaymentAttempt;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Broadcasts only safe, non-secret display state. The socket event is a
 * signal to refetch authoritative state from Laravel, never proof of
 * payment by itself — the frontend always re-reads attempt/invoice status
 * over HTTP after receiving this.
 */
class PaymentAttemptUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public string $attemptUuid;
    public string $status;
    public float $amount;
    public string $gateway;

    public function __construct(PaymentAttempt $attempt)
    {
        $this->attemptUuid = $attempt->uuid;
        $this->status = $attempt->status;
        $this->amount = (float) $attempt->amount;
        $this->gateway = $attempt->gateway instanceof \App\Enums\PaymentGateway ? $attempt->gateway->value : $attempt->gateway;
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel("payment-attempts.{$this->attemptUuid}")];
    }

    public function broadcastAs(): string
    {
        return 'PaymentAttemptUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'attemptUuid' => $this->attemptUuid,
            'status' => $this->status,
            'amount' => $this->amount,
            'gateway' => $this->gateway,
        ];
    }
}
