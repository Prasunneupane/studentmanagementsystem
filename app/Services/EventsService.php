<?php

namespace App\Services;

use App\Enums\EventCategory;
use App\Enums\EventStatus;
use App\Interface\EventsInterface;
use App\Models\Event;
use App\Models\EventGallery;
use App\Models\Events;
use App\Models\EventsGallery;

class EventsService implements EventsInterface
{
    private EventsGallery $eventGallery;

    public function __construct(EventsGallery $eventGallery)
    {
        $this->eventGallery = $eventGallery;
    }

    public function getAllEvents(): array
    {
        $events = Events::query()
            ->select('id', 'title', 'description', 'start_date', 'end_date', 'location', 'event_type', 'status', 'banner_image', 'is_active')
            ->orderByDesc('id')
            ->get();

        $eventIds = $events->pluck('id');

        $galleryByEvent = $this->eventGallery
            ->whereIn('event_id', $eventIds)
            ->where('is_active', true)
            ->get(['id', 'event_id', 'image_path'])
            ->groupBy('event_id');

        return $events->map(fn($event) => $this->formatEvent($event, $galleryByEvent->get($event->id, collect())))
            ->values()
            ->toArray();
    }

    public function getEventsById($eventId): array
    {
        $event = Events::query()
            ->select('id', 'title', 'description', 'start_date', 'end_date', 'location', 'event_type', 'status', 'banner_image', 'is_active', 'created_by')
            ->findOrFail($eventId);

        $images = $this->eventGallery
            ->where('event_id', $eventId)
            ->where('is_active', true)
            ->get(['id', 'image_path']);

        return $this->formatEvent($event, $images);
    }

    public function createEvent(array $eventData): array
    {

        $status = isset($eventData['status'])
            ? EventStatus::tryFrom($eventData['status'])
            : EventStatus::UPCOMING; // Default if not sent

        $eventType = isset($eventData['event_type'])
            ? EventCategory::tryFrom($eventData['event_type'])
            : null;


        $event = Events::create([
            'title' => $eventData['title'],
            'description' => $eventData['description'] ?? null,
            'start_date' => $eventData['start_date'],
            'end_date' => $eventData['end_date'],
            'location' => $eventData['location'] ?? null,
            'status' => $status?->value ?? EventStatus::UPCOMING->value,
            'event_type' => $eventType?->value, // Saves null if not provided
            'banner_image' => $eventData['banner_image'] ?? 'default-banner.jpg',
            'is_active' => $eventData['is_active'] ?? true,
            'created_by' => $eventData['created_by'],
        ]);

        if (!empty($eventData['gallery_images']) && is_array($eventData['gallery_images'])) {
            foreach ($eventData['gallery_images'] as $path) {
                $this->eventGallery->create([
                    'event_id' => $event->id,
                    'image_path' => $path,
                    'is_active' => true,
                ]);
            }
        }

        return $this->getEventsById($event->id);
    }

    public function updateEvent($eventId, array $eventData): array
    {
        $event = Events::findOrFail($eventId);


        // Build the data array manually for better control
        $updateData = [];

        if (array_key_exists('title', $eventData))
            $updateData['title'] = $eventData['title'];
        if (array_key_exists('description', $eventData))
            $updateData['description'] = $eventData['description'];
        if (array_key_exists('start_date', $eventData))
            $updateData['start_date'] = $eventData['start_date'];
        if (array_key_exists('end_date', $eventData))
            $updateData['end_date'] = $eventData['end_date'];
        if (array_key_exists('location', $eventData))
            $updateData['location'] = $eventData['location'];
        if (array_key_exists('banner_image', $eventData))
            $updateData['banner_image'] = $eventData['banner_image'];
        if (array_key_exists('is_active', $eventData))
            $updateData['is_active'] = $eventData['is_active'];

        // Handle Status (with fallback)
        if (array_key_exists('status', $eventData)) {
            $status = EventStatus::tryFrom($eventData['status']);
            // Only update if it's a valid status, otherwise skip or keep old
            if ($status) {
                $updateData['status'] = $status->value;
            }
        }

        // Handle Event Type (nullable)
        if (array_key_exists('event_type', $eventData)) {
            if (is_null($eventData['event_type'])) {
                $updateData['event_type'] = null; // User wants to clear it
            } else {
                $type = EventCategory::tryFrom($eventData['event_type']);
                if ($type) {
                    $updateData['event_type'] = $type->value;
                }
            }
        }

        $event->update($updateData);

        if (!empty($eventData['gallery_images']) && is_array($eventData['gallery_images'])) {
            foreach ($eventData['gallery_images'] as $path) {
                $this->eventGallery->create([
                    'event_id' => $event->id,
                    'image_path' => $path,
                    'is_active' => true,
                ]);
            }
        }

        return $this->getEventsById($event->id);
    }

    public function deleteEvent($eventId): bool
    {
        $event = Events::findOrFail($eventId);

        // soft delete pattern, matching your Subject module's `delete` route
        return $event->update(['is_active' => false]);
    }

    public function getEventsGalleryById($eventId): array
    {
        return $this->eventGallery
            ->where('event_id', $eventId)
            ->where('is_active', true)
            ->get(['id', 'image_path'])
            ->map(fn($img) => [
                'id' => $img->id,
                'url' => $this->resolveImageUrl($img->image_path),
            ])
            ->values()
            ->toArray();
    }

    private function formatEvent(Events $event, $galleryImages): array
    {
        return [
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'start_date' => $event->start_date,
            'end_date' => $event->end_date,
            'location' => $event->location,
            'event_type' => $event->event_type,
            'status' => $event->status,
            'is_active' => $event->is_active,
            'banner_image' => $this->resolveImageUrl($event->banner_image),
            'images' => $galleryImages->map(fn($img) => [
                'id' => $img->id,
                'url' => $this->resolveImageUrl($img->image_path),
            ])->values()->toArray(),
        ];
    }

    private function resolveImageUrl(?string $path): string
    {
        if (!$path) {
            return asset('images/default-banner.jpg');
        }

        return asset('storage/events/' . $path);
    }

    public function getStatusOptions(): array
    {
        return array_map(fn($status) => [
            'value' => $status->value,
            'label' => ucfirst($status->label()),
        ], EventStatus::cases());
    }

    public function getEventTypeOptions(): array
    {
        return array_map(fn($type) => [
            'value' => $type->value,
            'label' => ucfirst($type->label()),
        ], EventCategory::cases());
    }
}