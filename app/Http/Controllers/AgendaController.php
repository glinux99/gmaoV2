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
            $equipmentName = $activity->task && $activity->task->equipment ? $activity->task->equipment->name : 'No Equipment';
            $teamLeader = $activity->team && $activity->team->leader ? $activity->team->leader->name : 'No Team Leader';
            return [
                'id' => $activity->id,
                'title' => $taskTitle . ' (Activity)',
                'start_date' => $activity->actual_start_time,
                'daily_schedule' => \Carbon\Carbon::parse($activity->actual_start_time)->format('H:i') . ' - ' . \Carbon\Carbon::parse($activity->actual_end_time)->format('H:i'),
                'end_date' => $activity->actual_end_time,
                'status' => $activity->status,
                'equipment' => $equipmentName,
                'owner' => $teamLeader,
                'priority' => $activity->priority,
                'type' => 'activity',
            ];
        });

        $maintenances = Maintenance::all()->map(function ($maintenance) {
            return [
                'id' => $maintenance->id,
                'title' => $maintenance->title,
                'start' => $maintenance->start_date,
                'daily_schedule' => \Carbon\Carbon::parse($maintenance->scheduled_start_date)->format('H:i') . ' - ' . \Carbon\Carbon::parse($maintenance->scheduled_end_date)->format('H:i'),
                'start_date' => $maintenance->scheduled_start_date,
                'end_date' => $maintenance->scheduled_end_date,
                'status' => $maintenance->status,
                'equipment' => $maintenance->equipment ? $maintenance->equipment->name : 'No Equipment',
                'owner' => $maintenance->team && $maintenance->team->leader ? $maintenance->team->leader->name : 'No Team Leader',
                'priority' => $maintenance->priority,
                'type' => 'maintenance',
            ];
        });

        $tasks = Task::all()->map(function ($task) {
            return [
                'id' => $task->id,
                'title' => $task->title,
                'start' => $task->start_date,
                'daily_schedule' => \Carbon\Carbon::parse($task->planned_start_date)->format('H:i') . ' - ' . \Carbon\Carbon::parse($task->planned_end_date)->format('H:i'),
                'start_date' => $task->planned_start_date,
                'end_date' => $task->planned_end_date,
                'status' => $task->status,
                'equipment' => $task->equipment ? $task->equipment->name : 'No Equipment',
                'owner' => $task->assignedTo && $task->assignedTo->name ? $task->assignedTo->name : 'No Owner', // Assuming 'assignedTo' is the relationship for the owner/team leader

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
