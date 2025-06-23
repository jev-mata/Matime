<?php

namespace Extensions\Timesheet\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TimeEntry;
use Extensions\Timesheet\Models\Timesheet;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TimesheetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $date = $request->get('date'); 
        $period = $this->getBimonthlyPeriod(Carbon::parse($date));
        $range = $this->getBimonthlyRange(Carbon::parse($date));

        $entries = TimeEntry::where('user_id', auth()->id())
            ->whereBetween('start', [$range['from'], $range['to']])
            ->get();

        $grouped = $entries->map(function ($entry) {
            return [
                'id' => $entry->id,
                'date' => $entry->start?->format('Y-m-d'), // Grouping key
                'start' => $entry->start?->toDateTimeString(),
                'end' => $entry->end?->toDateTimeString(),
                'hours' => optional($entry->getDuration()),
                'description' => $entry->description,
                'status' => 'Pending',
            ];
        })->groupBy('date')->map->values(); // Reset keys inside each group

        // Total duration
        $totalSeconds = $entries->reduce(function ($carry, $entry) {
            return $carry + optional($entry->getDuration())?->totalSeconds ?? 0;
        }, 0);

        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        $totalFormatted = sprintf('%dh %02dm', $hours, $minutes);

        Log::info($grouped);
        return Inertia::render('Timesheet::index', [
            'entries' => $grouped,
            'totalHours' => $totalFormatted,
            'totalHoursNotForm' => $hours . '.' . $minutes,
            'period' => $range,
        ]);
    }

    private function getBimonthlyRange(Carbon $date): array
    {
        if ($date->day <= 15) {
            $from = $date->copy()->startOfMonth()->startOfDay();             // 1st day
            $to = $date->copy()->startOfMonth()->addDays(14)->endOfDay();    // 15th day
        } else {
            $from = $date->copy()->startOfMonth()->addDays(15)->startOfDay(); // 16th
            $to = $date->copy()->endOfMonth()->endOfDay();                    // end of month (30th or 31st)
        }

        return [
            'from' => $from,
            'to' => $to,
        ];
    }
    public function getBimonthlyPeriod(Carbon $date)
    {
        return $date->day <= 15
            ? $date->format('Y-m') . '-1' // 1st half
            : $date->format('Y-m') . '-2'; // 2nd half
    }
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'date_start' => 'required|date',
                'date_end' => 'required|date',
                'hours' => 'required|numeric|min:0.25', // assuming hours are required too
            ]);
 

            $data['user_id'] = Auth::user()->id;
            $data['status'] = 'pending';

            // Calculate bimonthly period
            // if ($date->day <= 15) {
            //     $data['date'] = $date->format('Y-m') . '-1';  // 1st half
            // } else {
            //     $data['date'] = $date->format('Y-m') . '-2';  // 2nd half
            // }

            $timesheet = Timesheet::create($data);

            return response()->json([
                'message' => 'Timesheet entry created successfully.',
                'data' => $timesheet,
            ], 201);
        } catch (\Throwable $e) {
            \Log::error('Timesheet store error', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to create timesheet entry.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function approve(Timesheet $timesheet)
    {
        $timesheet->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return response()->json(['message' => 'Approved']);
    }


    public function reject($id)
    {
        $timesheet = Timesheet::findOrFail($id);
        $timesheet->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
        return response()->json(['message' => 'Rejected']);
    }


    /**
     * Store a newly created resource in storage.
     */

    /**
     * Show the specified resource.
     */
    public function show(Request $request)
    {
        $date = $request->get('date');
        $range = $this->getBimonthlyRange(Carbon::parse($date));

        $entries = TimeEntry::where('user_id', auth()->id())
            ->whereBetween('start', [$range['from'], $range['to']])
            ->get();
        return response()->json([
            'isSubmitted' => $entries,
        ]);
    }
    public function showAll()
    {
        $entries = Timesheet::where('user_id', auth()->id())
            ->get();
        return response()->json([
            'isSubmitted' => $entries,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('timesheet::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
