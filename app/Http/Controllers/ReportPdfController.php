<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Mpdf\Mpdf;

class ReportPdfController extends Controller
{
    public function __invoke(Request $request, ReportService $reports): Response
    {
        abort_unless(
            $request->user()?->role === 'owner'
            || $request->user()?->hasGymPermission('reports.operational')
            || $request->user()?->hasGymPermission('reports.finance'),
            403
        );

        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'gender' => ['required', 'in:all,male,female'],
            'currency' => ['required', 'in:YER,SAR'],
        ]);

        $data = $reports->summary(
            $validated['from'],
            $validated['to'],
            $validated['gender'],
            $validated['currency']
        );

        $html = view('reports.summary-pdf', [
            'data' => $data,
            'filters' => $validated,
            'user' => $request->user(),
        ])->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_top' => 12,
            'margin_bottom' => 12,
            'margin_left' => 12,
            'margin_right' => 12,
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->WriteHTML($html);

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="winner-gym-report-'.$validated['currency'].'.pdf"',
        ]);
    }
}
