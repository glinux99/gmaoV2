<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\Activity;
use App\Models\Maintenance;
use App\Models\Task;
use Inertia\Inertia;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $activities = Activity::all()->map(function ($activity) {
            $taskTitle = $activity->task ? $activity->task->title : 'No Task';
            return [
                'id' => $activity->id,
                'title' => $taskTitle . ' (Activity)',
                'start_date' => $activity->actual_start_time,
                'end_date' => $activity->actual_end_time,
                'status' => $activity->status,
                'priority' => $activity->priority,
                'type' => 'activity',
            ];
        });

        $maintenances = Maintenance::all()->map(function ($maintenance) {
            return [
                'title' => $maintenance->title,
                'id' => $maintenance->id,
                'start' => $maintenance->start_date,
                'start_date' => $maintenance->scheduled_start_date,
                'end_date' => $maintenance->scheduled_end_date,
                'status' => $maintenance->status,
                'priority' => $maintenance->priority,
                'type' => 'maintenance',
            ];
        });

        $tasks = Task::all()->map(function ($task) {
            return [
                'title' => $task->title,
                'id' => $task->id,
                'start' => $task->start_date,
                   'start_date' => $task->planned_start_date,
                'end_date' => $task->planned_end_date,
                'status' => $task->status,
                'priority' => $task->priority,
                'type' => 'task',
            ];
        });

        $events = $activities->concat($maintenances)->concat($tasks);

        return Inertia::render('Tasks/Agenda', compact('events'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Agenda $agenda)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Agenda $agenda)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Agenda $agenda)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Agenda $agenda)
    {
        //
    }
}
