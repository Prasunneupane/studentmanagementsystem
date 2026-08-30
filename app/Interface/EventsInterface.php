<?php

namespace App\Interface;

interface EventsInterface
{
    public function getAllEvents():array;
    public function getEventsById($eventId):array;
    public function createEvent(array $eventData): array;
    public function updateEvent($eventId, array $eventData): array;
    public function deleteEvent($eventId): bool;
    public function getEventsGalleryById($eventId): array;
    public function getStatusOptions(): array;
    public function getEventTypeOptions(): array;

}
