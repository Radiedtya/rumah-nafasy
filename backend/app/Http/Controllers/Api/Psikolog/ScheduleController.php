<?php

namespace App\Http\Controllers\Api\Psikolog;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Psikolog\CreateScheduleRequest;
use App\Http\Requests\Psikolog\UpdateScheduleRequest;
use App\Http\Resources\ScheduleResource;
use App\Models\Schedule;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $schedules = Schedule::where('psikolog_id', $request->user()->id)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return $this->successResponse(
            ScheduleResource::collection($schedules),
            'Daftar jadwal praktek'
        );
    }

    public function store(CreateScheduleRequest $request)
    {
        $schedule = Schedule::create([
            'psikolog_id' => $request->user()->id,
            'day_of_week' => $request->day_of_week,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'is_available' => true,
        ]);

        return $this->successResponse(
            new ScheduleResource($schedule),
            'Jadwal praktek ditambahkan',
            201
        );
    }

    public function update(UpdateScheduleRequest $request, Schedule $schedule)
    {
        if ($schedule->psikolog_id !== $request->user()->id) {
            return $this->errorResponse('Anda tidak memiliki akses', 403);
        }

        $schedule->update($request->only(['day_of_week', 'start_time', 'end_time', 'is_available']));

        return $this->successResponse(
            new ScheduleResource($schedule->fresh()),
            'Jadwal praktek diperbarui'
        );
    }

    public function destroy(Request $request, Schedule $schedule)
    {
        if ($schedule->psikolog_id !== $request->user()->id) {
            return $this->errorResponse('Anda tidak memiliki akses', 403);
        }

        $schedule->delete();

        return $this->successResponse(null, 'Jadwal praktek dihapus');
    }
}