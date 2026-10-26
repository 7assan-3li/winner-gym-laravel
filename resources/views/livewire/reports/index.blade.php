<div dir="rtl" class="wg-page" x-data="{
    printShift() {
        window.print();
    }
}">
    {{-- Page Head --}}
    <div class="wg-page-head">
        <div>
            <h1 class="wg-title">التقارير وتسوية الورديات</h1>
            <div class="wg-subtitle">متابعة إيرادات الصالة، كشف حركة الكاشير، وتسوية وإقفال الصندوق لكل وردية</div>
        </div>
        <div style="display:flex;gap:8px;align-items:center">
            @if($tab === 'shifts')
                <button type="button" class="wg-btn wg-btn-primary" x-on:click="printShift()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
                    <span>طباعة كشف تسليم الوردية</span>
                </button>
            @else
                <a target="_blank" href="{{ route('reports.pdf', ['from'=>$from,'to'=>$to,'gender'=>$gender,'currency'=>$currency]) }}" class="wg-btn wg-btn-primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                    <span>تصدير PDF</span>
                </a>
            @endif
        </div>
    </div>

    {{-- Tabs --}}
    <div class="wg-toolbar" style="padding:0;border:0;background:transparent;margin-bottom:14px;display:flex;gap:8px">
        <button type="button" wire:click="setTab('shifts')" class="wg-btn {{ $tab === 'shifts' ? 'wg-btn-primary' : '' }}" style="min-height:42px;font-weight:800">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M7 15h0M2 9.5h20"/></svg>
            <span>تسوية ورديات المحاسبين والصندوق (إقفال الوردية)</span>
        </button>
        <button type="button" wire:click="setTab('summary')" class="wg-btn {{ $tab === 'summary' ? 'wg-btn-primary' : '' }}" style="min-height:42px;font-weight:800">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18M18 17V9M13 17V5M8 17v-3"/></svg>
            <span>الملخص المالي العام للصالة</span>
        </button>
    </div>

    @if (session('success'))
        <div class="wg-flash" style="margin-bottom:14px">{{ session('success') }}</div>
    @endif

    {{-- TAB 1: CASHIER SHIFTS & RECONCILIATION --}}
    @if($tab === 'shifts')
        {{-- Shift Toolbar --}}
        <div class="wg-card" style="margin-bottom:14px;padding:14px">
            <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
                <div style="display:flex;flex-direction:column;gap:5px;flex:1;min-width:180px">
                    <label style="font-size:12px;font-weight:750;color:var(--wg-muted)">تاريخ الوردية</label>
                    <input type="date" wire:model.live="shiftDate" class="wg-field">
                </div>
                <div style="display:flex;flex-direction:column;gap:5px;flex:1.5;min-width:220px">
                    <label style="font-size:12px;font-weight:750;color:var(--wg-muted)">المحاسب / الكاشير</label>
                    <select wire:model.live="shiftCashierId" class="wg-select">
                        @if(auth()->user()->role === 'owner' || auth()->user()->role === 'manager')
                            <option value="">كل المحاسبين والكاشيرات (إجمالي الصندوق)</option>
                        @endif
                        @foreach($staffList as $staff)
                            <option value="{{ $staff->id }}">{{ $staff->name ?: $staff->username }} ({{ match($staff->role){'owner'=>'مالك','manager'=>'مدير','reception'=>'استقبال','accountant'=>'محاسب','nutritionist'=>'تغذية',default=>$staff->role} }})</option>
                        @endforeach
                    </select>
                </div>
                <div style="display:flex;flex-direction:column;gap:5px;flex:1;min-width:150px">
                    <label style="font-size:12px;font-weight:750;color:var(--wg-muted)">العملة (عزل كامل)</label>
                    <select wire:model.live="shiftCurrency" class="wg-select">
                        <option value="YER">ريال يمني (YER)</option>
                        <option value="SAR">ريال سعودي (SAR)</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Grand KPI Cards --}}
        <div class="wg-grid-stats" style="grid-template-columns:repeat(4,minmax(0,1fr));margin-bottom:16px">
            {{-- Card 1: Net Cash Handover --}}
            <div class="wg-card wg-stat" style="border:2px solid var(--wg-primary);background:linear-gradient(145deg,rgba(37,99,235,0.06),transparent)">
                <small style="font-size:13px;font-weight:800;color:var(--wg-primary)">صافي النقد للتسليم (كاش الدرج)</small>
                <strong style="font-size:26px;color:#059669" dir="ltr">{{ number_format($shiftData['net_cash_handover'], 0) }} <span style="font-size:14px;color:var(--wg-muted)">{{ $shiftCurrency }}</span></strong>
                <div class="wg-stat-note" style="color:var(--wg-primary);font-weight:700">المبلغ الواجب تواجده فعلياً في الدرج</div>
            </div>

            {{-- Card 2: Cash In --}}
            <div class="wg-card wg-stat">
                <small style="font-size:13px">إجمالي المقبوضات النقدية (الكاش)</small>
                <strong class="wg-green" style="font-size:24px" dir="ltr">{{ number_format($shiftData['total_cash_in'], 0) }} <span style="font-size:13px;color:var(--wg-muted)">{{ $shiftCurrency }}</span></strong>
                <div class="wg-stat-note">اشتراكات + متجر + تغذية</div>
            </div>

            {{-- Card 3: Transfer In --}}
            <div class="wg-card wg-stat">
                <small style="font-size:13px">إجمالي التحويلات البنكية</small>
                <strong class="wg-blue" style="font-size:24px" dir="ltr">{{ number_format($shiftData['total_transfer_in'], 0) }} <span style="font-size:13px;color:var(--wg-muted)">{{ $shiftCurrency }}</span></strong>
                <div class="wg-stat-note">حوالات وشبكة مصرفية موثقة</div>
            </div>

            {{-- Card 4: Cash Expenses --}}
            <div class="wg-card wg-stat">
                <small style="font-size:13px">مصروفات الصندوق المنصرفة</small>
                <strong class="wg-red" style="font-size:24px" dir="ltr">{{ number_format($shiftData['cash_expenses'], 0) }} <span style="font-size:13px;color:var(--wg-muted)">{{ $shiftCurrency }}</span></strong>
                <div class="wg-stat-note">نقد تم صرفه من الدرج</div>
            </div>
        </div>

        {{-- Breakdown by Channel --}}
        <div class="wg-card" style="margin-bottom:16px;padding:16px">
            <h3 class="wg-title" style="font-size:15px;margin-bottom:12px">تفصيل مقبوضات الوردية حسب القناة</h3>
            <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px">
                {{-- Subscriptions --}}
                <div style="padding:12px;border:1px solid var(--wg-border);border-radius:10px;background:var(--wg-panel-2)">
                    <div style="font-weight:800;font-size:13.5px;color:var(--wg-primary);margin-bottom:8px">الاشتراكات والأقساط</div>
                    <div style="display:flex;justify-content:space-between;font-size:12.5px;margin-bottom:4px">
                        <span style="color:var(--wg-muted)">نقد (كاش):</span>
                        <strong dir="ltr">{{ number_format($shiftData['subscription_cash'], 0) }} {{ $shiftCurrency }}</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:12.5px">
                        <span style="color:var(--wg-muted)">تحويل بنكي:</span>
                        <strong dir="ltr">{{ number_format($shiftData['subscription_transfer'], 0) }} {{ $shiftCurrency }}</strong>
                    </div>
                </div>

                {{-- Sales --}}
                <div style="padding:12px;border:1px solid var(--wg-border);border-radius:10px;background:var(--wg-panel-2)">
                    <div style="font-weight:800;font-size:13.5px;color:#0284c7;margin-bottom:8px">مبيعات المتجر (POS)</div>
                    <div style="display:flex;justify-content:space-between;font-size:12.5px;margin-bottom:4px">
                        <span style="color:var(--wg-muted)">نقد (كاش):</span>
                        <strong dir="ltr">{{ number_format($shiftData['sales_cash'], 0) }} {{ $shiftCurrency }}</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:12.5px">
                        <span style="color:var(--wg-muted)">تحويل بنكي:</span>
                        <strong dir="ltr">{{ number_format($shiftData['sales_transfer'], 0) }} {{ $shiftCurrency }}</strong>
                    </div>
                </div>

                {{-- Nutrition --}}
                <div style="padding:12px;border:1px solid var(--wg-border);border-radius:10px;background:var(--wg-panel-2)">
                    <div style="font-weight:800;font-size:13.5px;color:#7c3aed;margin-bottom:8px">جلسات التغذية</div>
                    <div style="display:flex;justify-content:space-between;font-size:12.5px;margin-bottom:4px">
                        <span style="color:var(--wg-muted)">نقد (كاش):</span>
                        <strong dir="ltr">{{ number_format($shiftData['nutrition_cash'], 0) }} {{ $shiftCurrency }}</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:12.5px">
                        <span style="color:var(--wg-muted)">تحويل بنكي:</span>
                        <strong dir="ltr">{{ number_format($shiftData['nutrition_transfer'], 0) }} {{ $shiftCurrency }}</strong>
                    </div>
                </div>

                {{-- Total Collection --}}
                <div style="padding:12px;border:1px solid var(--wg-border);border-radius:10px;background:var(--wg-panel-2)">
                    <div style="font-weight:800;font-size:13.5px;color:#059669;margin-bottom:8px">إجمالي المحصل العام</div>
                    <div style="display:flex;justify-content:space-between;font-size:12.5px;margin-bottom:4px">
                        <span style="color:var(--wg-muted)">إجمالي الدخل:</span>
                        <strong dir="ltr" style="color:#059669">{{ number_format($shiftData['total_collected'], 0) }} {{ $shiftCurrency }}</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:12.5px">
                        <span style="color:var(--wg-muted)">عدد العمليات:</span>
                        <strong>{{ $shiftData['transactions_count'] }} حركة</strong>
                    </div>
                </div>
            </div>
        </div>

        {{-- Staff Comparison Table (If Owner/Manager or Multiple Staff) --}}
        @if(count($shiftData['comparison']) > 0)
            <div class="wg-card" style="margin-bottom:16px">
                <div class="wg-toolbar" style="border-bottom:1px solid var(--wg-border)">
                    <h3 class="wg-title" style="font-size:15px;margin:0">مقارنة تحصيل المحاسبين والورديات لهذا اليوم</h3>
                    <span style="font-size:12px;color:var(--wg-muted)">لمعرفة ما دخله كل محاسب وتدقيق أي نقص أو خطأ</span>
                </div>
                <div class="wg-table-wrap" style="border:0">
                    <table class="wg-table">
                        <thead>
                            <tr>
                                <th>المحاسب</th>
                                <th>الدور والفترة</th>
                                <th>الكاش المستلم</th>
                                <th>التحويلات</th>
                                <th>مصروف الصندوق</th>
                                <th>صافي تسليم الدرج</th>
                                <th>العمليات</th>
                                <th>إجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($shiftData['comparison'] as $cRow)
                                <tr class="{{ $shiftCashierId === $cRow['id'] ? 'is-selected' : '' }}">
                                    <td><strong style="font-size:13.5px">{{ $cRow['name'] }}</strong><div style="font-size:11.5px;color:var(--wg-muted)" dir="ltr">{{ $cRow['username'] }}</div></td>
                                    <td><span class="wg-badge wg-badge-blue">{{ $cRow['role'] }}</span> <span style="font-size:12px;color:var(--wg-muted)">{{ $cRow['period'] }}</span></td>
                                    <td dir="ltr" style="font-weight:750;color:#059669">{{ number_format($cRow['cash_in'], 0) }} {{ $shiftCurrency }}</td>
                                    <td dir="ltr" style="font-weight:750;color:#1d4ed8">{{ number_format($cRow['transfer_in'], 0) }} {{ $shiftCurrency }}</td>
                                    <td dir="ltr" style="font-weight:750;color:#dc2626">{{ number_format($cRow['cash_out'], 0) }} {{ $shiftCurrency }}</td>
                                    <td dir="ltr" style="font-weight:850;font-size:14px;color:#059669">{{ number_format($cRow['net_handover'], 0) }} {{ $shiftCurrency }}</td>
                                    <td><strong>{{ $cRow['transactions_count'] }}</strong></td>
                                    <td>
                                        <button type="button" wire:click="$set('shiftCashierId', {{ $cRow['id'] }})" class="wg-btn wg-btn-sm" style="font-size:12px">
                                            عرض تفاصيل ورديته
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Detailed Shift Ledger --}}
        <div class="wg-card">
            <div class="wg-toolbar" style="border-bottom:1px solid var(--wg-border)">
                <div>
                    <h3 class="wg-title" style="font-size:15px;margin:0">سجل حركات الوردية التفصيلي</h3>
                    <div style="font-size:12px;color:var(--wg-muted);margin-top:2px">
                        المحاسب: <strong>{{ $shiftData['cashier_name'] }}</strong> • التاريخ: <strong>{{ $shiftDate }}</strong> • العملة: <strong>{{ $shiftCurrency }}</strong>
                    </div>
                </div>
                <div style="font-size:12px;font-weight:750;color:var(--wg-muted)">
                    إجمالي {{ count($shiftData['transactions']) }} حركة مسجلة
                </div>
            </div>

            <div class="wg-table-wrap" style="border:0">
                <table class="wg-table">
                    <thead>
                        <tr>
                            <th>الوقت</th>
                            <th>النوع</th>
                            <th>المرجع / الإيصال</th>
                            <th>العضو / العميل</th>
                            <th>المحاسب</th>
                            <th>طريقة الدفع</th>
                            <th>تفاصيل التحويل</th>
                            <th>المبلغ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($shiftData['transactions'] as $tx)
                            <tr>
                                <td dir="ltr" style="font-weight:600;font-size:12px">{{ $tx['time_formatted'] }}</td>
                                <td>
                                    <span class="wg-badge {{ match($tx['type']){'subscription'=>'wg-badge-blue','sale'=>'wg-badge-green','nutrition'=>'wg-badge-purple',default=>'wg-badge-red'} }}">
                                        {{ $tx['type_label'] }}
                                    </span>
                                </td>
                                <td><strong style="font-size:12.5px">{{ $tx['reference'] }}</strong></td>
                                <td>{{ $tx['client'] }}</td>
                                <td>{{ $tx['cashier'] }}</td>
                                <td>
                                    <span class="wg-pill {{ $tx['method'] === 'cash' ? 'is-green' : 'is-blue' }}">
                                        {{ $tx['method_label'] }}
                                    </span>
                                </td>
                                <td>{{ $tx['transfer_details'] ?: '—' }}</td>
                                <td>
                                    <strong dir="ltr" style="font-size:13.5px;color:{{ $tx['is_out'] ? '#dc2626' : '#059669' }}">
                                        {{ $tx['is_out'] ? '-' : '+' }}{{ number_format($tx['amount'], 0) }} {{ $shiftCurrency }}
                                    </strong>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="wg-muted" style="text-align:center;padding:30px">
                                    لا توجد حركات مالية مسجلة لهذه الوردية بالتاريخ والعملة المحددين.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Printable Shift Handover Document (Hidden on screen, Visible on print) --}}
        <div id="wg-print-shift-receipt" class="wg-print-only" style="display:none">
            <style>
                @media print {
                    body * { visibility: hidden !important; }
                    #wg-print-shift-receipt, #wg-print-shift-receipt * { visibility: visible !important; }
                    #wg-print-shift-receipt {
                        position: absolute !important;
                        left: 0 !important;
                        top: 0 !important;
                        width: 100% !important;
                        display: block !important;
                        padding: 24px !important;
                        background: #ffffff !important;
                        color: #000000 !important;
                        font-family: 'Cairo', Arial, sans-serif !important;
                        direction: rtl !important;
                    }
                    .wg-print-table {
                        width: 100% !important;
                        border-collapse: collapse !important;
                        margin: 16px 0 !important;
                    }
                    .wg-print-table th, .wg-print-table td {
                        border: 1px solid #333 !important;
                        padding: 6px 8px !important;
                        font-size: 11px !important;
                        text-align: right !important;
                    }
                    .wg-print-table th { background: #f0f0f0 !important; }
                }
            </style>
            <div style="border-bottom:2px solid #000;padding-bottom:12px;margin-bottom:16px;text-align:center">
                <h2 style="font-size:20px;font-weight:900;margin:0">WINNER GYM — صالة وينر الرياضية</h2>
                <h3 style="font-size:16px;font-weight:800;margin:6px 0 0">كشف تسوية وإقفال وردية الصندوق (Handover Summary)</h3>
                <div style="font-size:12px;margin-top:4px">تاريخ التقرير: {{ $shiftDate }} | العملة: {{ $shiftCurrency }}</div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;font-size:12.5px">
                <div>المحاسب المسلّم: <strong>{{ $shiftData['cashier_name'] }}</strong></div>
                <div>وقت الطباعة: <strong>{{ now('Asia/Aden')->format('Y-m-d h:i A') }}</strong></div>
            </div>

            <table class="wg-print-table">
                <thead>
                    <tr>
                        <th>البيان</th>
                        <th>المبلغ ({{ $shiftCurrency }})</th>
                        <th>ملاحظات</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>إجمالي مقبوضات الاشتراكات (كاش)</td>
                        <td dir="ltr"><b>{{ number_format($shiftData['subscription_cash'], 2) }}</b></td>
                        <td>صندوق</td>
                    </tr>
                    <tr>
                        <td>إجمالي مبيعات المتجر (كاش)</td>
                        <td dir="ltr"><b>{{ number_format($shiftData['sales_cash'], 2) }}</b></td>
                        <td>صندوق</td>
                    </tr>
                    <tr>
                        <td>إجمالي جلسات التغذية (كاش)</td>
                        <td dir="ltr"><b>{{ number_format($shiftData['nutrition_cash'], 2) }}</b></td>
                        <td>صندوق</td>
                    </tr>
                    <tr style="background:#f9f9f9">
                        <td><strong>إجمالي الكاش المستلم بالصندوق</strong></td>
                        <td dir="ltr"><strong>{{ number_format($shiftData['total_cash_in'], 2) }}</strong></td>
                        <td><strong>مجموع المقبوضات النقدية</strong></td>
                    </tr>
                    <tr>
                        <td>مصروفات الصندوق المنصرفة</td>
                        <td dir="ltr"><b>- {{ number_format($shiftData['cash_expenses'], 2) }}</b></td>
                        <td>صرف نقدي من الدرج</td>
                    </tr>
                    <tr style="background:#e8f4ec">
                        <td style="font-size:13px;font-weight:900">صافي النقد المسلّم (كاش الدرج الفعلي)</td>
                        <td dir="ltr" style="font-size:14px;font-weight:900"><strong>{{ number_format($shiftData['net_cash_handover'], 2) }} {{ $shiftCurrency }}</strong></td>
                        <td><strong>المبلغ الفعلي في الدرج</strong></td>
                    </tr>
                    <tr>
                        <td>إجمالي التحويلات المصرفية (غير نقدية)</td>
                        <td dir="ltr"><b>{{ number_format($shiftData['total_transfer_in'], 2) }}</b></td>
                        <td>حسابات بنك / صرافة</td>
                    </tr>
                    <tr style="background:#f0f0f0">
                        <td><strong>إجمالي إيراد الوردية العام</strong></td>
                        <td dir="ltr"><strong>{{ number_format($shiftData['total_collected'], 2) }} {{ $shiftCurrency }}</strong></td>
                        <td>كاش + تحويلات</td>
                    </tr>
                </tbody>
            </table>

            <h4 style="font-size:13px;margin:16px 0 6px">تفاصيل الحركات المنفذة خلال الوردية:</h4>
            <table class="wg-print-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الوقت</th>
                        <th>النوع</th>
                        <th>المرجع</th>
                        <th>العميل</th>
                        <th>الطريقة</th>
                        <th>المبلغ</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($shiftData['transactions'] as $idx => $t)
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td dir="ltr">{{ $t['time_formatted'] }}</td>
                            <td>{{ $t['type_label'] }}</td>
                            <td>{{ $t['reference'] }}</td>
                            <td>{{ $t['client'] }}</td>
                            <td>{{ $t['method_label'] }} {{ $t['transfer_details'] ? '('.$t['transfer_details'].')' : '' }}</td>
                            <td dir="ltr">{{ $t['is_out'] ? '-' : '' }}{{ number_format($t['amount'], 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="margin-top:36px;display:grid;grid-template-columns:1fr 1fr;gap:40px;text-align:center">
                <div style="border-top:1px dashed #333;padding-top:8px">
                    <div><strong>توقيع المحاسب المسلّم</strong></div>
                    <div style="font-size:11px;color:#555;margin-top:4px">أقر بصحة البيانات وصافي النقد المسلم</div>
                </div>
                <div style="border-top:1px dashed #333;padding-top:8px">
                    <div><strong>توقيع المحاسب / المدير المستلم</strong></div>
                    <div style="font-size:11px;color:#555;margin-top:4px">أقر باستلام كامل صافي النقد الموضح أعلاه</div>
                </div>
            </div>
        </div>

    {{-- TAB 2: OVERALL FINANCIAL SUMMARY --}}
    @else
        <div class="wg-card" style="margin-bottom:16px;padding:14px">
            <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px">
                <div>
                    <label style="font-size:12px;font-weight:750;color:var(--wg-muted);display:block;margin-bottom:4px">من تاريخ</label>
                    <input wire:model.live="from" type="date" class="wg-field">
                </div>
                <div>
                    <label style="font-size:12px;font-weight:750;color:var(--wg-muted);display:block;margin-bottom:4px">إلى تاريخ</label>
                    <input wire:model.live="to" type="date" class="wg-field">
                </div>
                <div>
                    <label style="font-size:12px;font-weight:750;color:var(--wg-muted);display:block;margin-bottom:4px">الفترة / الجنس</label>
                    <select wire:model.live="gender" class="wg-select">
                        <option value="all">كل الفترات (رجال ونساء)</option>
                        <option value="male">رجال فقط</option>
                        <option value="female">نساء فقط</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:12px;font-weight:750;color:var(--wg-muted);display:block;margin-bottom:4px">العملة</label>
                    <select wire:model.live="currency" class="wg-select">
                        <option value="YER">ريال يمني (YER)</option>
                        <option value="SAR">ريال سعودي (SAR)</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="wg-grid-stats" style="grid-template-columns:repeat(4,minmax(0,1fr));margin-bottom:16px">
            <div class="wg-card wg-stat">
                <small>إيراد الاشتراكات</small>
                <strong class="wg-blue" dir="ltr">{{ number_format($data['subscription_revenue'], 0) }} {{ $currency }}</strong>
                <div class="wg-stat-note">صافي بعد الاسترداد</div>
            </div>
            <div class="wg-card wg-stat">
                <small>إيراد المتجر</small>
                <strong class="wg-green" dir="ltr">{{ number_format($data['product_revenue'], 0) }} {{ $currency }}</strong>
                <div class="wg-stat-note">ربح المنتجات: {{ number_format($data['product_profit'], 0) }} {{ $currency }}</div>
            </div>
            <div class="wg-card wg-stat">
                <small>إيراد التغذية</small>
                <strong class="wg-purple" dir="ltr">{{ number_format($data['nutrition_revenue'], 0) }} {{ $currency }}</strong>
                <div class="wg-stat-note">الجلسات المكتملة</div>
            </div>
            <div class="wg-card wg-stat" style="border:2px solid var(--wg-primary);background:linear-gradient(145deg,rgba(37,99,235,0.06),transparent)">
                <small style="color:var(--wg-primary);font-weight:800">صافي الدخل العام</small>
                <strong style="color:#059669;font-size:26px" dir="ltr">{{ number_format($data['net'], 0) }} {{ $currency }}</strong>
                <div class="wg-stat-note" style="color:var(--wg-primary);font-weight:700">بعد خصم المصروفات ({{ number_format($data['expenses'], 0) }})</div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px">
            <div class="wg-card" style="padding:16px;text-align:center">
                <div style="color:var(--wg-muted);font-size:13px">تسجيلات الحضور</div>
                <div style="font-size:26px;font-weight:850;color:var(--wg-primary);margin-top:6px">{{ number_format($data['attendance_count']) }}</div>
            </div>
            <div class="wg-card" style="padding:16px;text-align:center">
                <div style="color:var(--wg-muted);font-size:13px">مواعيد التغذية</div>
                <div style="font-size:26px;font-weight:850;color:#7c3aed;margin-top:6px">{{ number_format($data['appointments_count']) }}</div>
            </div>
            <div class="wg-card" style="padding:16px;text-align:center">
                <div style="color:var(--wg-muted);font-size:13px">الأعضاء الجدد</div>
                <div style="font-size:26px;font-weight:850;color:#059669;margin-top:6px">{{ number_format($data['new_members_count']) }}</div>
            </div>
        </div>
    @endif
</div>
