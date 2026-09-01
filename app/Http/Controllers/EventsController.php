<?php

namespace App\Http\Controllers;

use App\Http\Requests\Events\EventRequest;
use App\Http\Requests\Events\StoreEventRequest;
use App\Interface\EventsInterface;
use App\Models\Events;
use App\Services\EventsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EventsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    private EventsInterface $eventsService;
    public function __construct(EventsInterface $eventsInterface)
    {
        $this->eventsService = $eventsInterface;
    }
    public function index()
    {
        $events = $this->eventsService->getAllEvents();
        return Inertia::render('events/Index', [
            'events' => $events,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $statusOptions = $this->eventsService->getStatusOptions();
        $eventTypeOptions = $this->eventsService->getEventTypeOptions();
        return Inertia::render('events/Create', [
            'statusOptions' => $statusOptions,
            'eventTypeOptions' => $eventTypeOptions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEventRequest $request)
    {
        $validatedData = $request->validated();
        $this->eventsService->createEvent($validatedData);
        return redirect()->route('events.index')->with('success', 'Event created successfully.');

    }

    /**
     * Display the specified resource.
     */
    public function show(Events $events)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Events $event)
    {
        $events = $this->eventsService->getEventsById($event->id);
        $statusOptions = $this->eventsService->getStatusOptions();
        $eventTypeOptions = $this->eventsService->getEventTypeOptions();
        return Inertia::render('events/Edit', [
            'event' => $events,
            'statusOptions' => $statusOptions,
            'eventTypeOptions' => $eventTypeOptions,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        //EventRequest $request, Events $events
        )
    {
        // $validatedData = $request->validated();
        // $this->eventsService->updateEvent($events, $validatedData);
        // return redirect()->route('events.index')->with('success', 'Event updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Events $events)
    {
        //
    }
}
