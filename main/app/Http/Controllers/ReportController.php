<?php

namespace App\Http\Controllers;

use App\Actions\BuildComplianceReport;
use App\Enums\UserRole;
use App\Http\Requests\ReportRequest;
use App\Models\Facility;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(ReportRequest $request, BuildComplianceReport $report): View
    {
        $filters = $request->validated();
        $type = $filters['type'] ?? 'readiness';
        $rows = [];
        foreach ($report->rows($request->user(), $filters) as $row) {
            $rows[] = $row;
            if (count($rows) === 25) {
                break;
            }
        }
        $facilities = Facility::query()->when($request->user()->role === UserRole::FacilityManager, fn ($query) => $query->whereKey($request->user()->facility_id ?? 0))
            ->when($request->user()->role === UserRole::RegionalDirector, fn ($query) => $query->where('region_id', $request->user()->region_id ?? 0))->orderBy('name')->get();

        return view('reports.index', ['headers' => $report->headers($type), 'rows' => $rows, 'facilities' => $facilities, 'type' => $type]);
    }

    public function show(ReportRequest $request, BuildComplianceReport $report): StreamedResponse
    {
        $filters = $request->validated();
        $type = $filters['type'] ?? 'readiness';

        return response()->streamDownload(function () use ($request, $report, $filters, $type): void {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, $report->headers($type), escape: '');
            foreach ($report->rows($request->user(), $filters) as $row) {
                fputcsv($stream, $report->safeCsvRow($row), escape: '');
            }
            fclose($stream);
        }, 'checkpoint-'.$type.'-'.now('Asia/Manila')->toDateString().'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }
}
