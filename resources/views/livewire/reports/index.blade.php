<div dir="rtl" class="wg-page" x-data="{
    printShift() {
        window.print();
    }
}" x-on:trigger-print.window="setTimeout(() => window.print(), 250)">
    {{-- Page Head --}}
    <div class="wg-page-head no-print">
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
                <a target="_blank" href="{{ route('reports.pdf', ['from'=>$from,'to'=>$to,'gender'=>$gender,'currency'=>$currency,'cashier_id'=>$summaryCashierId]) }}" class="wg-btn wg-btn-primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                    <span>تصدير PDF</span>
                </a>
            @endif
        </div>
    </div>

    {{-- Tabs --}}
    <div class="wg-toolbar no-print" style="padding:0;border:0;background:transparent;margin-bottom:14px;display:flex;gap:8px">
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
        <div class="wg-flash no-print" style="margin-bottom:14px">{{ session('success') }}</div>
    @endif

    {{-- TAB 1: CASHIER SHIFTS & RECONCILIATION --}}
    @if($tab === 'shifts')
        <div class="no-print">
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
                        <option value="ALL">كل العملات (عزل كامل YER + SAR)</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Grand KPI Cards --}}
        <div class="wg-grid-stats" style="grid-template-columns:repeat(4,minmax(0,1fr));margin-bottom:16px">
            {{-- Card 1: Net Cash Handover --}}
            <div class="wg-card wg-stat" style="border:2px solid var(--wg-primary);background:linear-gradient(145deg,rgba(37,99,235,0.06),transparent)">
                <small style="font-size:13px;font-weight:800;color:var(--wg-primary)">صافي النقد للتسليم (كاش الدرج)</small>
                @if($shiftCurrency === 'ALL')
                    <div style="display:flex;flex-direction:column;gap:3px;margin-top:4px">
                        <div style="font-size:18px;font-weight:900;color:#059669" dir="ltr">
                            {{ number_format($shiftData['yer']['net_cash_handover'] ?? 0, 0) }} <span style="font-size:12px;color:var(--wg-muted)">YER</span>
                        </div>
                        <div style="font-size:18px;font-weight:900;color:#059669" dir="ltr">
                            {{ number_format($shiftData['sar']['net_cash_handover'] ?? 0, 0) }} <span style="font-size:12px;color:var(--wg-muted)">SAR</span>
                        </div>
                    </div>
                @else
                    <strong style="font-size:26px;color:#059669" dir="ltr">{{ number_format($shiftData['net_cash_handover'], 0) }} <span style="font-size:14px;color:var(--wg-muted)">{{ $shiftCurrency }}</span></strong>
                @endif
                <div class="wg-stat-note" style="color:var(--wg-primary);font-weight:700">المبلغ الواجب تواجده فعلياً في الدرج</div>
            </div>

            {{-- Card 2: Cash In --}}
            <div class="wg-card wg-stat">
                <small style="font-size:13px">إجمالي المقبوضات النقدية (الكاش)</small>
                @if($shiftCurrency === 'ALL')
                    <div style="display:flex;flex-direction:column;gap:3px;margin-top:4px">
                        <div class="wg-green" style="font-size:17px;font-weight:850" dir="ltr">
                            {{ number_format($shiftData['yer']['total_cash_in'] ?? 0, 0) }} <span style="font-size:11px;color:var(--wg-muted)">YER</span>
                        </div>
                        <div class="wg-green" style="font-size:17px;font-weight:850" dir="ltr">
                            {{ number_format($shiftData['sar']['total_cash_in'] ?? 0, 0) }} <span style="font-size:11px;color:var(--wg-muted)">SAR</span>
                        </div>
                    </div>
                @else
                    <strong class="wg-green" style="font-size:24px" dir="ltr">{{ number_format($shiftData['total_cash_in'], 0) }} <span style="font-size:13px;color:var(--wg-muted)">{{ $shiftCurrency }}</span></strong>
                @endif
                <div class="wg-stat-note">اشتراكات + متجر + تغذية</div>
            </div>

            {{-- Card 3: Transfer In --}}
            <div class="wg-card wg-stat">
                <small style="font-size:13px">إجمالي التحويلات البنكية</small>
                @if($shiftCurrency === 'ALL')
                    <div style="display:flex;flex-direction:column;gap:3px;margin-top:4px">
                        <div class="wg-blue" style="font-size:17px;font-weight:850" dir="ltr">
                            {{ number_format($shiftData['yer']['total_transfer_in'] ?? 0, 0) }} <span style="font-size:11px;color:var(--wg-muted)">YER</span>
                        </div>
                        <div class="wg-blue" style="font-size:17px;font-weight:850" dir="ltr">
                            {{ number_format($shiftData['sar']['total_transfer_in'] ?? 0, 0) }} <span style="font-size:11px;color:var(--wg-muted)">SAR</span>
                        </div>
                    </div>
                @else
                    <strong class="wg-blue" style="font-size:24px" dir="ltr">{{ number_format($shiftData['total_transfer_in'], 0) }} <span style="font-size:13px;color:var(--wg-muted)">{{ $shiftCurrency }}</span></strong>
                @endif
                <div class="wg-stat-note">حوالات وشبكة مصرفية موثقة</div>
            </div>

            {{-- Card 4: Cash Expenses --}}
            <div class="wg-card wg-stat">
                <small style="font-size:13px">مصروفات الصندوق المنصرفة</small>
                @if($shiftCurrency === 'ALL')
                    <div style="display:flex;flex-direction:column;gap:3px;margin-top:4px">
                        <div class="wg-red" style="font-size:17px;font-weight:850" dir="ltr">
                            {{ number_format($shiftData['yer']['cash_expenses'] ?? 0, 0) }} <span style="font-size:11px;color:var(--wg-muted)">YER</span>
                        </div>
                        <div class="wg-red" style="font-size:17px;font-weight:850" dir="ltr">
                            {{ number_format($shiftData['sar']['cash_expenses'] ?? 0, 0) }} <span style="font-size:11px;color:var(--wg-muted)">SAR</span>
                        </div>
                    </div>
                @else
                    <strong class="wg-red" style="font-size:24px" dir="ltr">{{ number_format($shiftData['cash_expenses'], 0) }} <span style="font-size:13px;color:var(--wg-muted)">{{ $shiftCurrency }}</span></strong>
                @endif
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
                    @if($shiftCurrency === 'ALL')
                        <div style="font-size:11px;font-weight:700;color:var(--wg-primary);margin-top:3px">ريال يمني (YER):</div>
                        <div style="display:flex;justify-content:space-between;font-size:12px">
                            <span style="color:var(--wg-muted)">نقد:</span> <strong dir="ltr">{{ number_format($shiftData['yer']['subscription_cash'] ?? 0, 0) }} YER</strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:12px">
                            <span style="color:var(--wg-muted)">تحويل:</span> <strong dir="ltr">{{ number_format($shiftData['yer']['subscription_transfer'] ?? 0, 0) }} YER</strong>
                        </div>
                        <div style="font-size:11px;font-weight:700;color:var(--wg-primary);margin-top:6px">ريال سعودي (SAR):</div>
                        <div style="display:flex;justify-content:space-between;font-size:12px">
                            <span style="color:var(--wg-muted)">نقد:</span> <strong dir="ltr">{{ number_format($shiftData['sar']['subscription_cash'] ?? 0, 0) }} SAR</strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:12px">
                            <span style="color:var(--wg-muted)">تحويل:</span> <strong dir="ltr">{{ number_format($shiftData['sar']['subscription_transfer'] ?? 0, 0) }} SAR</strong>
                        </div>
                    @else
                        <div style="display:flex;justify-content:space-between;font-size:12.5px;margin-bottom:4px">
                            <span style="color:var(--wg-muted)">نقد (كاش):</span>
                            <strong dir="ltr">{{ number_format($shiftData['subscription_cash'], 0) }} {{ $shiftCurrency }}</strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:12.5px">
                            <span style="color:var(--wg-muted)">تحويل بنكي:</span>
                            <strong dir="ltr">{{ number_format($shiftData['subscription_transfer'], 0) }} {{ $shiftCurrency }}</strong>
                        </div>
                    @endif
                </div>

                {{-- Sales --}}
                <div style="padding:12px;border:1px solid var(--wg-border);border-radius:10px;background:var(--wg-panel-2)">
                    <div style="font-weight:800;font-size:13.5px;color:#0284c7;margin-bottom:8px">مبيعات المتجر (POS)</div>
                    @if($shiftCurrency === 'ALL')
                        <div style="font-size:11px;font-weight:700;color:#0284c7;margin-top:3px">ريال يمني (YER):</div>
                        <div style="display:flex;justify-content:space-between;font-size:12px">
                            <span style="color:var(--wg-muted)">نقد:</span> <strong dir="ltr">{{ number_format($shiftData['yer']['sales_cash'] ?? 0, 0) }} YER</strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:12px">
                            <span style="color:var(--wg-muted)">تحويل:</span> <strong dir="ltr">{{ number_format($shiftData['yer']['sales_transfer'] ?? 0, 0) }} YER</strong>
                        </div>
                        <div style="font-size:11px;font-weight:700;color:#0284c7;margin-top:6px">ريال سعودي (SAR):</div>
                        <div style="display:flex;justify-content:space-between;font-size:12px">
                            <span style="color:var(--wg-muted)">نقد:</span> <strong dir="ltr">{{ number_format($shiftData['sar']['sales_cash'] ?? 0, 0) }} SAR</strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:12px">
                            <span style="color:var(--wg-muted)">تحويل:</span> <strong dir="ltr">{{ number_format($shiftData['sar']['sales_transfer'] ?? 0, 0) }} SAR</strong>
                        </div>
                    @else
                        <div style="display:flex;justify-content:space-between;font-size:12.5px;margin-bottom:4px">
                            <span style="color:var(--wg-muted)">نقد (كاش):</span>
                            <strong dir="ltr">{{ number_format($shiftData['sales_cash'], 0) }} {{ $shiftCurrency }}</strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:12.5px">
                            <span style="color:var(--wg-muted)">تحويل بنكي:</span>
                            <strong dir="ltr">{{ number_format($shiftData['sales_transfer'], 0) }} {{ $shiftCurrency }}</strong>
                        </div>
                    @endif
                </div>

                {{-- Nutrition --}}
                <div style="padding:12px;border:1px solid var(--wg-border);border-radius:10px;background:var(--wg-panel-2)">
                    <div style="font-weight:800;font-size:13.5px;color:#7c3aed;margin-bottom:8px">جلسات التغذية</div>
                    @if($shiftCurrency === 'ALL')
                        <div style="font-size:11px;font-weight:700;color:#7c3aed;margin-top:3px">ريال يمني (YER):</div>
                        <div style="display:flex;justify-content:space-between;font-size:12px">
                            <span style="color:var(--wg-muted)">نقد:</span> <strong dir="ltr">{{ number_format($shiftData['yer']['nutrition_cash'] ?? 0, 0) }} YER</strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:12px">
                            <span style="color:var(--wg-muted)">تحويل:</span> <strong dir="ltr">{{ number_format($shiftData['yer']['nutrition_transfer'] ?? 0, 0) }} YER</strong>
                        </div>
                        <div style="font-size:11px;font-weight:700;color:#7c3aed;margin-top:6px">ريال سعودي (SAR):</div>
                        <div style="display:flex;justify-content:space-between;font-size:12px">
                            <span style="color:var(--wg-muted)">نقد:</span> <strong dir="ltr">{{ number_format($shiftData['sar']['nutrition_cash'] ?? 0, 0) }} SAR</strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:12px">
                            <span style="color:var(--wg-muted)">تحويل:</span> <strong dir="ltr">{{ number_format($shiftData['sar']['nutrition_transfer'] ?? 0, 0) }} SAR</strong>
                        </div>
                    @else
                        <div style="display:flex;justify-content:space-between;font-size:12.5px;margin-bottom:4px">
                            <span style="color:var(--wg-muted)">نقد (كاش):</span>
                            <strong dir="ltr">{{ number_format($shiftData['nutrition_cash'], 0) }} {{ $shiftCurrency }}</strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:12.5px">
                            <span style="color:var(--wg-muted)">تحويل بنكي:</span>
                            <strong dir="ltr">{{ number_format($shiftData['nutrition_transfer'], 0) }} {{ $shiftCurrency }}</strong>
                        </div>
                    @endif
                </div>

                {{-- Total Collection --}}
                <div style="padding:12px;border:1px solid var(--wg-border);border-radius:10px;background:var(--wg-panel-2)">
                    <div style="font-weight:800;font-size:13.5px;color:#059669;margin-bottom:8px">إجمالي المحصل العام</div>
                    @if($shiftCurrency === 'ALL')
                        <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:3px">
                            <span style="color:var(--wg-muted)">دخل (YER):</span>
                            <strong dir="ltr" style="color:#059669">{{ number_format($shiftData['yer']['total_collected'] ?? 0, 0) }} YER</strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:3px">
                            <span style="color:var(--wg-muted)">دخل (SAR):</span>
                            <strong dir="ltr" style="color:#059669">{{ number_format($shiftData['sar']['total_collected'] ?? 0, 0) }} SAR</strong>
                        </div>
                    @else
                        <div style="display:flex;justify-content:space-between;font-size:12.5px;margin-bottom:4px">
                            <span style="color:var(--wg-muted)">إجمالي الدخل:</span>
                            <strong dir="ltr" style="color:#059669">{{ number_format($shiftData['total_collected'], 0) }} {{ $shiftCurrency }}</strong>
                        </div>
                    @endif
                    <div style="display:flex;justify-content:space-between;font-size:12.5px;margin-top:4px">
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
                                    <td>
                                        @if($shiftCurrency === 'ALL')
                                            <div dir="ltr" style="font-weight:750;color:#059669;font-size:12px">{{ number_format($cRow['cash_in_yer'] ?? 0, 0) }} <span style="font-size:10px;color:var(--wg-muted)">YER</span></div>
                                            <div dir="ltr" style="font-weight:750;color:#059669;font-size:12px">{{ number_format($cRow['cash_in_sar'] ?? 0, 0) }} <span style="font-size:10px;color:var(--wg-muted)">SAR</span></div>
                                        @else
                                            <div dir="ltr" style="font-weight:750;color:#059669">{{ number_format($cRow['cash_in'], 0) }} {{ $shiftCurrency }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($shiftCurrency === 'ALL')
                                            <div dir="ltr" style="font-weight:750;color:#1d4ed8;font-size:12px">{{ number_format($cRow['transfer_in_yer'] ?? 0, 0) }} <span style="font-size:10px;color:var(--wg-muted)">YER</span></div>
                                            <div dir="ltr" style="font-weight:750;color:#1d4ed8;font-size:12px">{{ number_format($cRow['transfer_in_sar'] ?? 0, 0) }} <span style="font-size:10px;color:var(--wg-muted)">SAR</span></div>
                                        @else
                                            <div dir="ltr" style="font-weight:750;color:#1d4ed8">{{ number_format($cRow['transfer_in'], 0) }} {{ $shiftCurrency }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($shiftCurrency === 'ALL')
                                            <div dir="ltr" style="font-weight:750;color:#dc2626;font-size:12px">{{ number_format($cRow['cash_out_yer'] ?? 0, 0) }} <span style="font-size:10px;color:var(--wg-muted)">YER</span></div>
                                            <div dir="ltr" style="font-weight:750;color:#dc2626;font-size:12px">{{ number_format($cRow['cash_out_sar'] ?? 0, 0) }} <span style="font-size:10px;color:var(--wg-muted)">SAR</span></div>
                                        @else
                                            <div dir="ltr" style="font-weight:750;color:#dc2626">{{ number_format($cRow['cash_out'], 0) }} {{ $shiftCurrency }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($shiftCurrency === 'ALL')
                                            <div dir="ltr" style="font-weight:850;font-size:13px;color:#059669">{{ number_format($cRow['net_handover_yer'] ?? 0, 0) }} <span style="font-size:10px;color:var(--wg-muted)">YER</span></div>
                                            <div dir="ltr" style="font-weight:850;font-size:13px;color:#059669">{{ number_format($cRow['net_handover_sar'] ?? 0, 0) }} <span style="font-size:10px;color:var(--wg-muted)">SAR</span></div>
                                        @else
                                            <div dir="ltr" style="font-weight:850;font-size:14px;color:#059669">{{ number_format($cRow['net_handover'], 0) }} {{ $shiftCurrency }}</div>
                                        @endif
                                    </td>
                                    <td><strong>{{ $cRow['transactions_count'] }}</strong></td>
                                    <td>
                                        <button type="button" wire:click="openShiftDetails({{ $cRow['id'] }})" class="wg-btn wg-btn-sm" style="font-size:12px;display:inline-flex;align-items:center;gap:6px">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                            <span>عرض تفاصيل ورديته</span>
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
                        المحاسب: <strong>{{ $shiftData['cashier_name'] }}</strong> • التاريخ: <strong>{{ $shiftDate }}</strong> • العملة: <strong>{{ $shiftCurrency === 'ALL' ? 'كل العملات (YER + SAR)' : $shiftCurrency }}</strong>
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
                                        {{ $tx['is_out'] ? '-' : '+' }}{{ number_format($tx['amount'], 0) }} {{ $tx['currency'] ?? $shiftCurrency }}
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
        </div>

        {{-- Printable Shift Handover Document (Hidden on screen, Visible on print) --}}
        <div id="wg-print-shift-receipt" class="wg-print-only">
            <style>
                @media screen {
                    #wg-print-shift-receipt {
                        display: none !important;
                    }
                }
                @page {
                    size: A4 portrait;
                    margin: 12mm 15mm;
                }
                @media print {
                    .no-print,
                    .wg-sidebar,
                    aside,
                    nav,
                    .wg-nav,
                    header,
                    .wg-topbar,
                    .wg-sidebar-scrim,
                    .wg-topbar-tools,
                    .wg-search,
                    .wg-user,
                    .wg-user-menu,
                    .wg-theme-toggle,
                    .wg-mobile-menu-toggle,
                    .wg-page-head,
                    .wg-toolbar,
                    .wg-card,
                    .wg-grid-stats,
                    .wg-table-wrap,
                    .wg-flash,
                    .wg-errors,
                    button,
                    .wg-btn {
                        display: none !important;
                        visibility: hidden !important;
                        height: 0 !important;
                        width: 0 !important;
                        margin: 0 !important;
                        padding: 0 !important;
                        border: none !important;
                        overflow: hidden !important;
                        position: absolute !important;
                        top: -9999px !important;
                        left: -9999px !important;
                        opacity: 0 !important;
                        pointer-events: none !important;
                    }
                    body, .wg-body, .wg-app, .wg-main, .wg-page {
                        background: #ffffff !important;
                        color: #000000 !important;
                        margin: 0 !important;
                        padding: 0 !important;
                        width: 100% !important;
                        max-width: 100% !important;
                        position: static !important;
                        display: block !important;
                        overflow: visible !important;
                    }
                    #wg-print-shift-receipt {
                        display: block !important;
                        position: static !important;
                        width: 100% !important;
                        margin: 0 auto !important;
                        padding: 0 !important;
                        background: #ffffff !important;
                        color: #000000 !important;
                        font-family: 'Cairo', Arial, sans-serif !important;
                        direction: rtl !important;
                    }
                    .wg-print-table {
                        width: 100% !important;
                        border-collapse: collapse !important;
                        margin: 14px 0 !important;
                    }
                    .wg-print-table th, .wg-print-table td {
                        border: 1px solid #1e293b !important;
                        padding: 6px 10px !important;
                        font-size: 11px !important;
                    }
                    .wg-print-table th {
                        background: #f1f5f9 !important;
                        color: #0f172a !important;
                        font-weight: bold !important;
                        text-align: right !important;
                    }
                    .wg-print-table tr {
                        page-break-inside: avoid !important;
                    }
                }
            </style>
            @include('reports.partials.header', [
                'title' => 'كشف تسوية وإقفال وردية الصندوق (Handover Summary)',
                'subtitle' => 'تقرير تفصيلي بمقبوضات ومصروفات وصافي نقدية الدرج',
                'meta' => [
                    'تاريخ الوردية' => $shiftDate,
                    'العملة' => $shiftCurrency === 'ALL' ? 'كل العملات (عزل كامل YER + SAR)' : $shiftCurrency,
                    'المحاسب المسلّم' => $shiftData['cashier_name'] ?: 'الكل',
                ]
            ])

            @if($shiftCurrency === 'ALL')
                <table class="wg-print-table">
                    <thead>
                        <tr>
                            <th style="width:40%;">البيان</th>
                            <th style="width:20%;text-align:center;">المبلغ (YER)</th>
                            <th style="width:20%;text-align:center;">المبلغ (SAR)</th>
                            <th style="width:20%;text-align:center;">ملاحظات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>إجمالي مقبوضات الاشتراكات (كاش)</td>
                            <td dir="ltr" style="text-align:center;"><b>{{ number_format($shiftData['yer']['subscription_cash'] ?? 0, 2) }}</b></td>
                            <td dir="ltr" style="text-align:center;"><b>{{ number_format($shiftData['sar']['subscription_cash'] ?? 0, 2) }}</b></td>
                            <td style="text-align:center;">صندوق</td>
                        </tr>
                        <tr>
                            <td>إجمالي مبيعات المتجر (كاش)</td>
                            <td dir="ltr" style="text-align:center;"><b>{{ number_format($shiftData['yer']['sales_cash'] ?? 0, 2) }}</b></td>
                            <td dir="ltr" style="text-align:center;"><b>{{ number_format($shiftData['sar']['sales_cash'] ?? 0, 2) }}</b></td>
                            <td style="text-align:center;">صندوق</td>
                        </tr>
                        <tr>
                            <td>إجمالي جلسات التغذية (كاش)</td>
                            <td dir="ltr" style="text-align:center;"><b>{{ number_format($shiftData['yer']['nutrition_cash'] ?? 0, 2) }}</b></td>
                            <td dir="ltr" style="text-align:center;"><b>{{ number_format($shiftData['sar']['nutrition_cash'] ?? 0, 2) }}</b></td>
                            <td style="text-align:center;">صندوق</td>
                        </tr>
                        <tr style="background:#f9f9f9">
                            <td><strong>إجمالي الكاش المستلم بالصندوق</strong></td>
                            <td dir="ltr" style="text-align:center;"><strong>{{ number_format($shiftData['yer']['total_cash_in'] ?? 0, 2) }}</strong></td>
                            <td dir="ltr" style="text-align:center;"><strong>{{ number_format($shiftData['sar']['total_cash_in'] ?? 0, 2) }}</strong></td>
                            <td style="text-align:center;"><strong>مجموع المقبوضات النقدية</strong></td>
                        </tr>
                        <tr>
                            <td>مصروفات الصندوق المنصرفة</td>
                            <td dir="ltr" style="text-align:center;"><b>- {{ number_format($shiftData['yer']['cash_expenses'] ?? 0, 2) }}</b></td>
                            <td dir="ltr" style="text-align:center;"><b>- {{ number_format($shiftData['sar']['cash_expenses'] ?? 0, 2) }}</b></td>
                            <td style="text-align:center;">صرف نقدي من الدرج</td>
                        </tr>
                        <tr style="background:#e8f4ec">
                            <td style="font-size:12px;font-weight:900">صافي النقد المسلّم (كاش الدرج الفعلي)</td>
                            <td dir="ltr" style="font-size:13px;font-weight:900;text-align:center;"><strong>{{ number_format($shiftData['yer']['net_cash_handover'] ?? 0, 2) }} YER</strong></td>
                            <td dir="ltr" style="font-size:13px;font-weight:900;text-align:center;"><strong>{{ number_format($shiftData['sar']['net_cash_handover'] ?? 0, 2) }} SAR</strong></td>
                            <td style="text-align:center;"><strong>المبلغ الفعلي في الدرج</strong></td>
                        </tr>
                        <tr>
                            <td>إجمالي التحويلات المصرفية (غير نقدية)</td>
                            <td dir="ltr" style="text-align:center;"><b>{{ number_format($shiftData['yer']['total_transfer_in'] ?? 0, 2) }}</b></td>
                            <td dir="ltr" style="text-align:center;"><b>{{ number_format($shiftData['sar']['total_transfer_in'] ?? 0, 2) }}</b></td>
                            <td style="text-align:center;">حسابات بنك / صرافة</td>
                        </tr>
                        <tr style="background:#f0f0f0">
                            <td><strong>إجمالي إيراد الوردية العام</strong></td>
                            <td dir="ltr" style="text-align:center;"><strong>{{ number_format($shiftData['yer']['total_collected'] ?? 0, 2) }} YER</strong></td>
                            <td dir="ltr" style="text-align:center;"><strong>{{ number_format($shiftData['sar']['total_collected'] ?? 0, 2) }} SAR</strong></td>
                            <td style="text-align:center;">كاش + تحويلات</td>
                        </tr>
                    </tbody>
                </table>
            @else
                <table class="wg-print-table">
                    <thead>
                        <tr>
                            <th style="width:52%;">البيان</th>
                            <th style="width:28%;text-align:center;">المبلغ ({{ $shiftCurrency }})</th>
                            <th style="width:20%;text-align:center;">ملاحظات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>إجمالي مقبوضات الاشتراكات (كاش)</td>
                            <td dir="ltr" style="text-align:center;"><b>{{ number_format($shiftData['subscription_cash'], 2) }}</b></td>
                            <td style="text-align:center;">صندوق</td>
                        </tr>
                        <tr>
                            <td>إجمالي مبيعات المتجر (كاش)</td>
                            <td dir="ltr" style="text-align:center;"><b>{{ number_format($shiftData['sales_cash'], 2) }}</b></td>
                            <td style="text-align:center;">صندوق</td>
                        </tr>
                        <tr>
                            <td>إجمالي جلسات التغذية (كاش)</td>
                            <td dir="ltr" style="text-align:center;"><b>{{ number_format($shiftData['nutrition_cash'], 2) }}</b></td>
                            <td style="text-align:center;">صندوق</td>
                        </tr>
                        <tr style="background:#f9f9f9">
                            <td><strong>إجمالي الكاش المستلم بالصندوق</strong></td>
                            <td dir="ltr" style="text-align:center;"><strong>{{ number_format($shiftData['total_cash_in'], 2) }}</strong></td>
                            <td style="text-align:center;"><strong>مجموع المقبوضات النقدية</strong></td>
                        </tr>
                        <tr>
                            <td>مصروفات الصندوق المنصرفة</td>
                            <td dir="ltr" style="text-align:center;"><b>- {{ number_format($shiftData['cash_expenses'], 2) }}</b></td>
                            <td style="text-align:center;">صرف نقدي من الدرج</td>
                        </tr>
                        <tr style="background:#e8f4ec">
                            <td style="font-size:12px;font-weight:900">صافي النقد المسلّم (كاش الدرج الفعلي)</td>
                            <td dir="ltr" style="font-size:13px;font-weight:900;text-align:center;"><strong>{{ number_format($shiftData['net_cash_handover'], 2) }} {{ $shiftCurrency }}</strong></td>
                            <td style="text-align:center;"><strong>المبلغ الفعلي في الدرج</strong></td>
                        </tr>
                        <tr>
                            <td>إجمالي التحويلات المصرفية (غير نقدية)</td>
                            <td dir="ltr" style="text-align:center;"><b>{{ number_format($shiftData['total_transfer_in'], 2) }}</b></td>
                            <td style="text-align:center;">حسابات بنك / صرافة</td>
                        </tr>
                        <tr style="background:#f0f0f0">
                            <td><strong>إجمالي إيراد الوردية العام</strong></td>
                            <td dir="ltr" style="text-align:center;"><strong>{{ number_format($shiftData['total_collected'], 2) }} {{ $shiftCurrency }}</strong></td>
                            <td style="text-align:center;">كاش + تحويلات</td>
                        </tr>
                    </tbody>
                </table>
            @endif

            <h4 style="font-size:12px;margin:16px 0 6px;color:#0f172a;font-weight:bold;">تفاصيل الحركات المنفذة خلال الوردية:</h4>
            <table class="wg-print-table">
                <thead>
                    <tr>
                        <th style="width:5%;text-align:center;">#</th>
                        <th style="width:13%;text-align:center;">الوقت</th>
                        <th style="width:16%;">النوع</th>
                        <th style="width:15%;">المرجع</th>
                        <th style="width:23%;">العميل</th>
                        <th style="width:14%;">الطريقة</th>
                        <th style="width:14%;text-align:center;">المبلغ</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($shiftData['transactions'] as $idx => $t)
                        <tr>
                            <td style="text-align:center;">{{ $idx + 1 }}</td>
                            <td dir="ltr" style="text-align:center;">{{ $t['time_formatted'] }}</td>
                            <td>{{ $t['type_label'] }}</td>
                            <td>{{ $t['reference'] }}</td>
                            <td>{{ $t['client'] }}</td>
                            <td>{{ $t['method_label'] }} {{ $t['transfer_details'] ? '('.$t['transfer_details'].')' : '' }}</td>
                            <td dir="ltr" style="text-align:center;">{{ $t['is_out'] ? '-' : '' }}{{ number_format($t['amount'], 0) }} {{ $t['currency'] ?? $shiftCurrency }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @include('reports.partials.footer', [
                'signEmployee' => $shiftData['cashier_name'] ?: auth()->user()?->name,
                'signManager' => 'إدارة الصالة / المشرف',
            ])
        </div>

    {{-- TAB 2: OVERALL FINANCIAL SUMMARY --}}
    @else
        <div class="no-print">
            <div class="wg-card" style="margin-bottom:16px;padding:14px">
                <div style="display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px">
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
                        <label style="font-size:12px;font-weight:750;color:var(--wg-muted);display:block;margin-bottom:4px">الموظف / المحاسب</label>
                        <select wire:model.live="summaryCashierId" class="wg-select">
                            <option value="">كل الموظفين والمحاسبين</option>
                            @foreach($staffList as $staff)
                                <option value="{{ $staff->id }}">{{ $staff->name ?: $staff->username }} ({{ match($staff->role){'owner'=>'مالك','manager'=>'مدير','reception'=>'استقبال','accountant'=>'محاسب','nutritionist'=>'تغذية',default=>$staff->role} }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="font-size:12px;font-weight:750;color:var(--wg-muted);display:block;margin-bottom:4px">العملة</label>
                        <select wire:model.live="currency" class="wg-select">
                            <option value="YER">ريال يمني (YER)</option>
                            <option value="SAR">ريال سعودي (SAR)</option>
                            <option value="ALL">كل العملات (عزل كامل YER + SAR)</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="wg-grid-stats" style="grid-template-columns:repeat(4,minmax(0,1fr));margin-bottom:16px">
                <div class="wg-card wg-stat">
                    <small>إيراد الاشتراكات</small>
                    @if(!empty($data['is_all_currencies']))
                        <div style="display:flex;flex-direction:column;gap:3px;margin-top:4px">
                            <div class="wg-blue" style="font-size:18px;font-weight:850" dir="ltr">{{ number_format($data['yer']['subscription_revenue'] ?? 0, 0) }} <span style="font-size:12px;color:var(--wg-muted)">YER</span></div>
                            <div class="wg-blue" style="font-size:18px;font-weight:850" dir="ltr">{{ number_format($data['sar']['subscription_revenue'] ?? 0, 0) }} <span style="font-size:12px;color:var(--wg-muted)">SAR</span></div>
                        </div>
                        <div class="wg-stat-note">صافي بعد الاسترداد لكل عملة</div>
                    @else
                        <strong class="wg-blue" dir="ltr">{{ number_format($data['subscription_revenue'], 0) }} {{ $currency }}</strong>
                        <div class="wg-stat-note">صافي بعد الاسترداد</div>
                    @endif
                </div>
                <div class="wg-card wg-stat">
                    <small>إيراد المتجر</small>
                    @if(!empty($data['is_all_currencies']))
                        <div style="display:flex;flex-direction:column;gap:3px;margin-top:4px">
                            <div class="wg-green" style="font-size:18px;font-weight:850" dir="ltr">{{ number_format($data['yer']['product_revenue'] ?? 0, 0) }} <span style="font-size:12px;color:var(--wg-muted)">YER</span></div>
                            <div class="wg-green" style="font-size:18px;font-weight:850" dir="ltr">{{ number_format($data['sar']['product_revenue'] ?? 0, 0) }} <span style="font-size:12px;color:var(--wg-muted)">SAR</span></div>
                        </div>
                        <div class="wg-stat-note">أرباح: {{ number_format($data['yer']['product_profit'] ?? 0, 0) }} YER | {{ number_format($data['sar']['product_profit'] ?? 0, 0) }} SAR</div>
                    @else
                        <strong class="wg-green" dir="ltr">{{ number_format($data['product_revenue'], 0) }} {{ $currency }}</strong>
                        <div class="wg-stat-note">ربح المنتجات: {{ number_format($data['product_profit'], 0) }} {{ $currency }}</div>
                    @endif
                </div>
                <div class="wg-card wg-stat">
                    <small>إيراد التغذية</small>
                    @if(!empty($data['is_all_currencies']))
                        <div style="display:flex;flex-direction:column;gap:3px;margin-top:4px">
                            <div class="wg-purple" style="font-size:18px;font-weight:850" dir="ltr">{{ number_format($data['yer']['nutrition_revenue'] ?? 0, 0) }} <span style="font-size:12px;color:var(--wg-muted)">YER</span></div>
                            <div class="wg-purple" style="font-size:18px;font-weight:850" dir="ltr">{{ number_format($data['sar']['nutrition_revenue'] ?? 0, 0) }} <span style="font-size:12px;color:var(--wg-muted)">SAR</span></div>
                        </div>
                        <div class="wg-stat-note">الجلسات المكتملة لكل عملة</div>
                    @else
                        <strong class="wg-purple" dir="ltr">{{ number_format($data['nutrition_revenue'], 0) }} {{ $currency }}</strong>
                        <div class="wg-stat-note">الجلسات المكتملة</div>
                    @endif
                </div>
                <div class="wg-card wg-stat" style="border:2px solid var(--wg-primary);background:linear-gradient(145deg,rgba(37,99,235,0.06),transparent)">
                    <small style="color:var(--wg-primary);font-weight:800">صافي الدخل العام</small>
                    @if(!empty($data['is_all_currencies']))
                        <div style="display:flex;flex-direction:column;gap:3px;margin-top:4px">
                            <div style="color:#059669;font-size:20px;font-weight:900" dir="ltr">{{ number_format($data['yer']['net'] ?? 0, 0) }} <span style="font-size:12px;color:var(--wg-muted)">YER</span></div>
                            <div style="color:#059669;font-size:20px;font-weight:900" dir="ltr">{{ number_format($data['sar']['net'] ?? 0, 0) }} <span style="font-size:12px;color:var(--wg-muted)">SAR</span></div>
                        </div>
                        <div class="wg-stat-note" style="color:var(--wg-primary);font-weight:700">المصروفات: {{ number_format($data['yer']['expenses'] ?? 0, 0) }} YER | {{ number_format($data['sar']['expenses'] ?? 0, 0) }} SAR</div>
                    @else
                        <strong style="color:#059669;font-size:26px" dir="ltr">{{ number_format($data['net'], 0) }} {{ $currency }}</strong>
                        <div class="wg-stat-note" style="color:var(--wg-primary);font-weight:700">بعد خصم المصروفات ({{ number_format($data['expenses'], 0) }})</div>
                    @endif
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
        </div>
    @endif

    {{-- Cashier Shift Details Modal --}}
    @if($showShiftModal && $modalShiftData)
        <div class="wg-modal-backdrop no-print" style="position:fixed;inset:0;background:rgba(0,0,0,0.72);backdrop-filter:blur(5px);display:flex;align-items:center;justify-content:center;z-index:99999;padding:16px;">
            <div class="wg-modal" dir="rtl" style="max-width:860px;width:100%;max-height:92vh;display:flex;flex-direction:column;box-shadow:0 25px 50px -12px rgba(0,0,0,0.6);border:1px solid rgba(255,255,255,0.12);background:var(--wg-card-bg, #0f172a);border-radius:14px;overflow:hidden;">
                
                {{-- Modal Head --}}
                <div class="wg-modal-head" style="display:flex;justify-content:space-between;align-items:center;padding:16px 22px;border-bottom:1px solid var(--wg-border,#1e293b);background:var(--wg-panel-2,#1e293b);">
                    <div>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <span style="font-size:22px;">📋</span>
                            <h3 style="margin:0;font-size:17px;font-weight:800;color:var(--wg-text,#f8fafc);">
                                تفاصيل وردية المحاسب: {{ $modalShiftData['cashier_name'] }}
                            </h3>
                            <span class="wg-badge wg-badge-blue" style="font-size:11px;">
                                {{ match(optional(collect($staffList)->firstWhere('id', $modalCashierId))->role){'owner'=>'مالك','manager'=>'مدير','reception'=>'استقبال','accountant'=>'محاسب','nutritionist'=>'تغذية',default=>'موظف'} }}
                            </span>
                        </div>
                        <div style="font-size:12px;color:var(--wg-muted,#94a3b8);margin-top:4px;">
                            تاريخ الوردية: <strong>{{ $shiftDate }}</strong> • العملة: <strong>{{ $shiftCurrency === 'ALL' ? 'كل العملات (عزل كامل YER + SAR)' : $shiftCurrency }}</strong>
                        </div>
                    </div>
                    <button type="button" wire:click="closeShiftDetails" class="wg-modal-x" style="background:none;border:none;color:#94a3b8;font-size:24px;cursor:pointer;line-height:1;padding:4px 8px;">✕</button>
                </div>

                {{-- Modal Body --}}
                <div class="wg-modal-body" style="padding:18px 22px;overflow-y:auto;display:flex;flex-direction:column;gap:16px;">
                    
                    {{-- KPI Stat Grid --}}
                    <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;">
                        <div style="padding:12px;background:rgba(5,150,105,0.12);border:1px solid rgba(5,150,105,0.3);border-radius:10px;">
                            <div style="font-size:11px;font-weight:750;color:#059669;">صافي كاش الدرج للتسليم</div>
                            @if($shiftCurrency === 'ALL')
                                <div style="font-size:16px;font-weight:900;color:#059669;margin-top:4px;" dir="ltr">
                                    {{ number_format($modalShiftData['yer']['net_cash_handover'] ?? 0, 2) }} <span style="font-size:10px;">YER</span>
                                </div>
                                <div style="font-size:16px;font-weight:900;color:#059669;margin-top:2px;" dir="ltr">
                                    {{ number_format($modalShiftData['sar']['net_cash_handover'] ?? 0, 2) }} <span style="font-size:10px;">SAR</span>
                                </div>
                            @else
                                <div style="font-size:19px;font-weight:900;color:#059669;margin-top:4px;" dir="ltr">
                                    {{ number_format($modalShiftData['net_cash_handover'], 2) }} <span style="font-size:11px;">{{ $shiftCurrency }}</span>
                                </div>
                            @endif
                        </div>
                        <div style="padding:12px;background:var(--wg-panel-2,#1e293b);border:1px solid var(--wg-border,#334155);border-radius:10px;">
                            <div style="font-size:11px;font-weight:750;color:var(--wg-muted);">المقبوضات النقدية (كاش)</div>
                            @if($shiftCurrency === 'ALL')
                                <div style="font-size:15px;font-weight:850;color:#10b981;margin-top:4px;" dir="ltr">
                                    {{ number_format($modalShiftData['yer']['total_cash_in'] ?? 0, 2) }} <span style="font-size:10px;">YER</span>
                                </div>
                                <div style="font-size:15px;font-weight:850;color:#10b981;margin-top:2px;" dir="ltr">
                                    {{ number_format($modalShiftData['sar']['total_cash_in'] ?? 0, 2) }} <span style="font-size:10px;">SAR</span>
                                </div>
                            @else
                                <div style="font-size:18px;font-weight:850;color:#10b981;margin-top:4px;" dir="ltr">
                                    {{ number_format($modalShiftData['total_cash_in'], 2) }}
                                </div>
                            @endif
                        </div>
                        <div style="padding:12px;background:var(--wg-panel-2,#1e293b);border:1px solid var(--wg-border,#334155);border-radius:10px;">
                            <div style="font-size:11px;font-weight:750;color:var(--wg-muted);">التحويلات المصرفية</div>
                            @if($shiftCurrency === 'ALL')
                                <div style="font-size:15px;font-weight:850;color:#3b82f6;margin-top:4px;" dir="ltr">
                                    {{ number_format($modalShiftData['yer']['total_transfer_in'] ?? 0, 2) }} <span style="font-size:10px;">YER</span>
                                </div>
                                <div style="font-size:15px;font-weight:850;color:#3b82f6;margin-top:2px;" dir="ltr">
                                    {{ number_format($modalShiftData['sar']['total_transfer_in'] ?? 0, 2) }} <span style="font-size:10px;">SAR</span>
                                </div>
                            @else
                                <div style="font-size:18px;font-weight:850;color:#3b82f6;margin-top:4px;" dir="ltr">
                                    {{ number_format($modalShiftData['total_transfer_in'], 2) }}
                                </div>
                            @endif
                        </div>
                        <div style="padding:12px;background:var(--wg-panel-2,#1e293b);border:1px solid var(--wg-border,#334155);border-radius:10px;">
                            <div style="font-size:11px;font-weight:750;color:var(--wg-muted);">مصروفات الصندوق</div>
                            @if($shiftCurrency === 'ALL')
                                <div style="font-size:15px;font-weight:850;color:#ef4444;margin-top:4px;" dir="ltr">
                                    {{ number_format($modalShiftData['yer']['cash_expenses'] ?? 0, 2) }} <span style="font-size:10px;">YER</span>
                                </div>
                                <div style="font-size:15px;font-weight:850;color:#ef4444;margin-top:2px;" dir="ltr">
                                    {{ number_format($modalShiftData['sar']['cash_expenses'] ?? 0, 2) }} <span style="font-size:10px;">SAR</span>
                                </div>
                            @else
                                <div style="font-size:18px;font-weight:850;color:#ef4444;margin-top:4px;" dir="ltr">
                                    {{ number_format($modalShiftData['cash_expenses'], 2) }}
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Channel Breakdown --}}
                    <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;">
                        <div style="padding:10px 12px;background:var(--wg-panel-2,#1e293b);border:1px solid var(--wg-border,#334155);border-radius:8px;">
                            <div style="font-size:12px;font-weight:800;color:var(--wg-primary,#38bdf8);margin-bottom:6px;">الاشتراكات والأقساط</div>
                            @if($shiftCurrency === 'ALL')
                                <div style="font-size:10.5px;color:var(--wg-primary);font-weight:700">YER:</div>
                                <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--wg-muted);">
                                    <span>كاش:</span> <strong dir="ltr">{{ number_format($modalShiftData['yer']['subscription_cash'] ?? 0, 2) }}</strong>
                                    <span>تحويل:</span> <strong dir="ltr">{{ number_format($modalShiftData['yer']['subscription_transfer'] ?? 0, 2) }}</strong>
                                </div>
                                <div style="font-size:10.5px;color:var(--wg-primary);font-weight:700;margin-top:4px">SAR:</div>
                                <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--wg-muted);">
                                    <span>كاش:</span> <strong dir="ltr">{{ number_format($modalShiftData['sar']['subscription_cash'] ?? 0, 2) }}</strong>
                                    <span>تحويل:</span> <strong dir="ltr">{{ number_format($modalShiftData['sar']['subscription_transfer'] ?? 0, 2) }}</strong>
                                </div>
                            @else
                                <div style="display:flex;justify-content:space-between;font-size:11.5px;color:var(--wg-muted);">
                                    <span>كاش:</span> <strong dir="ltr">{{ number_format($modalShiftData['subscription_cash'], 2) }}</strong>
                                </div>
                                <div style="display:flex;justify-content:space-between;font-size:11.5px;color:var(--wg-muted);margin-top:2px;">
                                    <span>تحويل:</span> <strong dir="ltr">{{ number_format($modalShiftData['subscription_transfer'], 2) }}</strong>
                                </div>
                            @endif
                        </div>
                        <div style="padding:10px 12px;background:var(--wg-panel-2,#1e293b);border:1px solid var(--wg-border,#334155);border-radius:8px;">
                            <div style="font-size:12px;font-weight:800;color:#0284c7;margin-bottom:6px;">مبيعات المتجر</div>
                            @if($shiftCurrency === 'ALL')
                                <div style="font-size:10.5px;color:#0284c7;font-weight:700">YER:</div>
                                <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--wg-muted);">
                                    <span>كاش:</span> <strong dir="ltr">{{ number_format($modalShiftData['yer']['sales_cash'] ?? 0, 2) }}</strong>
                                    <span>تحويل:</span> <strong dir="ltr">{{ number_format($modalShiftData['yer']['sales_transfer'] ?? 0, 2) }}</strong>
                                </div>
                                <div style="font-size:10.5px;color:#0284c7;font-weight:700;margin-top:4px">SAR:</div>
                                <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--wg-muted);">
                                    <span>كاش:</span> <strong dir="ltr">{{ number_format($modalShiftData['sar']['sales_cash'] ?? 0, 2) }}</strong>
                                    <span>تحويل:</span> <strong dir="ltr">{{ number_format($modalShiftData['sar']['sales_transfer'] ?? 0, 2) }}</strong>
                                </div>
                            @else
                                <div style="display:flex;justify-content:space-between;font-size:11.5px;color:var(--wg-muted);">
                                    <span>كاش:</span> <strong dir="ltr">{{ number_format($modalShiftData['sales_cash'], 2) }}</strong>
                                </div>
                                <div style="display:flex;justify-content:space-between;font-size:11.5px;color:var(--wg-muted);margin-top:2px;">
                                    <span>تحويل:</span> <strong dir="ltr">{{ number_format($modalShiftData['sales_transfer'], 2) }}</strong>
                                </div>
                            @endif
                        </div>
                        <div style="padding:10px 12px;background:var(--wg-panel-2,#1e293b);border:1px solid var(--wg-border,#334155);border-radius:8px;">
                            <div style="font-size:12px;font-weight:800;color:#a855f7;margin-bottom:6px;">جلسات التغذية</div>
                            @if($shiftCurrency === 'ALL')
                                <div style="font-size:10.5px;color:#a855f7;font-weight:700">YER:</div>
                                <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--wg-muted);">
                                    <span>كاش:</span> <strong dir="ltr">{{ number_format($modalShiftData['yer']['nutrition_cash'] ?? 0, 2) }}</strong>
                                    <span>تحويل:</span> <strong dir="ltr">{{ number_format($modalShiftData['yer']['nutrition_transfer'] ?? 0, 2) }}</strong>
                                </div>
                                <div style="font-size:10.5px;color:#a855f7;font-weight:700;margin-top:4px">SAR:</div>
                                <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--wg-muted);">
                                    <span>كاش:</span> <strong dir="ltr">{{ number_format($modalShiftData['sar']['nutrition_cash'] ?? 0, 2) }}</strong>
                                    <span>تحويل:</span> <strong dir="ltr">{{ number_format($modalShiftData['sar']['nutrition_transfer'] ?? 0, 2) }}</strong>
                                </div>
                            @else
                                <div style="display:flex;justify-content:space-between;font-size:11.5px;color:var(--wg-muted);">
                                    <span>كاش:</span> <strong dir="ltr">{{ number_format($modalShiftData['nutrition_cash'], 2) }}</strong>
                                </div>
                                <div style="display:flex;justify-content:space-between;font-size:11.5px;color:var(--wg-muted);margin-top:2px;">
                                    <span>تحويل:</span> <strong dir="ltr">{{ number_format($modalShiftData['nutrition_transfer'], 2) }}</strong>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Transactions Table --}}
                    <div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                            <h4 style="font-size:13px;font-weight:800;margin:0;color:var(--wg-text,#f8fafc);">
                                الحركات والعمليات المنفذة في هذه الوردية ({{ count($modalShiftData['transactions']) }} عملية)
                            </h4>
                        </div>
                        <div style="max-height:240px;overflow-y:auto;border:1px solid var(--wg-border,#334155);border-radius:8px;">
                            <table class="wg-table" style="font-size:12px;margin:0;">
                                <thead style="position:sticky;top:0;background:var(--wg-panel-2,#1e293b);z-index:1;">
                                    <tr>
                                        <th style="padding:7px 10px;">الوقت</th>
                                        <th style="padding:7px 10px;">النوع</th>
                                        <th style="padding:7px 10px;">المرجع</th>
                                        <th style="padding:7px 10px;">العميل / البيان</th>
                                        <th style="padding:7px 10px;">طريقة الدفع</th>
                                        <th style="padding:7px 10px;text-align:center;">المبلغ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($modalShiftData['transactions'] as $tx)
                                        <tr>
                                            <td dir="ltr" style="font-size:11px;">{{ $tx['time_formatted'] }}</td>
                                            <td><span class="wg-badge {{ match($tx['type']){'subscription'=>'wg-badge-blue','sale'=>'wg-badge-green','nutrition'=>'wg-badge-purple',default=>'wg-badge-red'} }}">{{ $tx['type_label'] }}</span></td>
                                            <td><strong>{{ $tx['reference'] }}</strong></td>
                                            <td>{{ $tx['client'] }}</td>
                                            <td>{{ $tx['method_label'] }} {{ $tx['transfer_details'] ? '('.$tx['transfer_details'].')' : '' }}</td>
                                            <td dir="ltr" style="text-align:center;font-weight:800;color:{{ $tx['is_out'] ? '#ef4444' : '#10b981' }};">
                                                {{ $tx['is_out'] ? '-' : '+' }}{{ number_format($tx['amount'], 2) }} {{ $tx['currency'] ?? $shiftCurrency }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" style="text-align:center;padding:24px;color:var(--wg-muted);">
                                                لا توجد حركات مالية مسجلة لهذا المحاسب في هذا اليوم.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                {{-- Modal Foot --}}
                <div class="wg-modal-foot" style="display:flex;justify-content:space-between;align-items:center;padding:12px 22px;border-top:1px solid var(--wg-border,#1e293b);background:var(--wg-panel-2,#1e293b);">
                    <button type="button" wire:click="closeShiftDetails" class="wg-btn" style="padding:8px 20px;">
                        إغلاق
                    </button>
                    <button type="button" wire:click="printCashierShift({{ $modalCashierId }})" class="wg-btn wg-btn-primary" style="display:inline-flex;align-items:center;gap:6px;padding:8px 20px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
                        <span>طباعة كشف تسليم الوردية للمحاسب</span>
                    </button>
                </div>

            </div>
        </div>
    @endif
</div>
