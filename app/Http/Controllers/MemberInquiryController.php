<?php

namespace App\Http\Controllers;

use App\Services\MemberPortalService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MemberInquiryController extends Controller
{
    public function __construct(private MemberPortalService $portalService) {}

    public function show(): View
    {
        return view('public.member-inquiry');
    }

    public function lookup(Request $request): View|RedirectResponse
    {
        $hasDirectCode = filled($request->input('membership_code')) && blank($request->input('phone'));

        if ($hasDirectCode) {
            $validated = $request->validate([
                'membership_code' => ['required', 'string', 'max:50'],
            ], [
                'membership_code.required' => 'كود العضوية مطلوب للاستعلام.',
            ]);
        } else {
            $validated = $request->validate([
                'phone' => ['required', 'string', 'max:30'],
                'verification_key' => ['required', 'string', 'max:50'],
            ], [
                'phone.required' => 'رقم الهاتف المسجل مطلوب.',
                'verification_key.required' => 'رمز التحقق (كود العضوية أو آخر 4 أرقام من الهوية) مطلوب.',
            ]);
        }

        $result = $this->portalService->lookup($validated);

        if (! $result) {
            $errorMsg = 'لم يتم العثور على اشتراك يطابق البيانات المدخلة. يرجى التأكد من صحة المدخلات والمحاولة مجدداً.';
            $errorKey = $hasDirectCode ? 'membership_code' : 'phone';

            return redirect()->route('member.inquiry')
                ->withErrors([$errorKey => $errorMsg, 'error' => $errorMsg])
                ->withInput();
        }

        return view('public.member-inquiry', compact('result'));
    }
}
