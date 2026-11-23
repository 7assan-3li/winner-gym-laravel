<?php

namespace App\Http\Controllers;

use App\Services\MemberDirectoryService;
use App\Services\PermissionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MemberPrintController extends Controller
{
    public function index(Request $request, MemberDirectoryService $directory, PermissionService $permissions): View
    {
        $user = $request->user();
        abort_unless(
            $user && ($permissions->allows($user, 'members.view') || $permissions->allows($user, 'members.manage')),
            403
        );

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['all', 'active', 'suspended', 'archived'])],
            'period' => ['nullable', Rule::in(['all', 'men', 'women'])],
            'package_id' => ['nullable', 'string', 'max:20'],
            'subscription_status' => ['nullable', Rule::in([
                'all', 'active', 'expiring_soon', 'financial_overdue',
                'upcoming', 'expired', 'cancelled', 'refunded', 'none',
            ])],
            'sort' => ['nullable', Rule::in(['latest', 'name', 'end_date'])],
            'orientation' => ['nullable', Rule::in(['portrait', 'landscape'])],
            'show_phone' => ['nullable', 'boolean'],
            'show_dates' => ['nullable', 'boolean'],
            'show_financial' => ['nullable', 'boolean'],
            'autoprint' => ['nullable', 'boolean'],
        ]);

        $packageFilter = (string) ($validated['package_id'] ?? 'all');
        if ($packageFilter !== 'all' && (! ctype_digit($packageFilter) || (int) $packageFilter <= 0)) {
            $packageFilter = 'all';
        }

        $filters = [
            'search' => trim((string) ($validated['search'] ?? '')),
            'status' => (string) ($validated['status'] ?? 'all'),
            'period' => (string) ($validated['period'] ?? 'all'),
            'package_id' => $packageFilter,
            'subscription_status' => (string) ($validated['subscription_status'] ?? 'all'),
            'sort' => (string) ($validated['sort'] ?? 'latest'),
        ];

        $result = $directory->forPrint($filters);

        return view('members.print', [
            'members' => $result['members'],
            'summary' => $result['summary'],
            'packageName' => $result['package_name'],
            'filters' => $filters,
            'options' => [
                'orientation' => (string) ($validated['orientation'] ?? 'landscape'),
                'show_phone' => $request->has('show_phone') ? $request->boolean('show_phone') : true,
                'show_dates' => $request->has('show_dates') ? $request->boolean('show_dates') : true,
                'show_financial' => $request->has('show_financial') ? $request->boolean('show_financial') : true,
                'autoprint' => $request->boolean('autoprint'),
            ],
            'printedBy' => $user->name ?: $user->username,
            'printedAt' => now('Asia/Aden')->format('Y-m-d h:i A'),
        ]);
    }
}
