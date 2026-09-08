<?php

namespace App\Console\Commands;

use App\Enums\EventStatus;
use App\Models\Events;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class UpdateEventStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'event:update-event-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
         $now = Carbon::now();

        // Update events that should be completed
        Events::where('end_date', '<', $now)
            ->whereNotIn('status', ['cancelled', 'postponed']) // preserve manual overrides
            ->update(['status' => EventStatus::COMPLETED]);

        // Update events that are ongoing (start_date <= now <= end_date)
        Events::where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->whereNotIn('status', ['cancelled', 'postponed'])
            ->update(['status' => EventStatus::ONGOING]);

        // Update upcoming events (start_date > now)
        Events::where('start_date', '>', $now)
            ->whereNotIn('status', ['cancelled', 'postponed'])
            ->update(['status' => EventStatus::UPCOMING]);

        $this->info('Event statuses updated successfully.');
    }
}
