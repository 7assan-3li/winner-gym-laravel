<div class="wg-page" dir="rtl">
    <div class="wg-page-head"><div><h1 class="wg-title">إعدادات النظام</h1><div class="wg-subtitle">هوية النادي، العملات، الدفع والخصائص العامة</div></div><button form="settings-form" class="wg-btn wg-btn-primary">حفظ الإعدادات</button></div>
    @include('livewire.admin._tabs')
    @if(session('success'))<div class="wg-flash">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="wg-errors">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

    <form id="settings-form" wire:submit="save" class="wg-two">
        <div style="display:grid;gap:12px">
            <div class="wg-card wg-card-pad"><h2 class="wg-section-title">بيانات النادي</h2><div class="wg-two" style="margin-top:13px">
                <div><label class="wg-label">اسم النادي *</label><input wire:model="gym_name" class="wg-field"></div><div><label class="wg-label">الموقع *</label><input wire:model="location" class="wg-field"></div>
                <div><label class="wg-label">الهاتف</label><input wire:model="phone" class="wg-field" dir="ltr"></div><div><label class="wg-label">المسؤول العام</label><input wire:model="manager_name" class="wg-field"></div>
                <div><label class="wg-label">السجل التجاري / الترخيص</label><input wire:model="commercial_register" class="wg-field"></div>
            </div></div>
            <div class="wg-card wg-card-pad"><h2 class="wg-section-title">العملات والمنطقة الزمنية</h2><div class="wg-two" style="margin-top:13px"><div><label class="wg-label">المنطقة الزمنية</label><select wire:model="timezone" class="wg-select"><option value="Asia/Aden">Asia/Aden — اليمن</option></select></div><div><label class="wg-label">العملة الافتراضية</label><select wire:model="default_currency" class="wg-select"><option value="YER">YER — ريال يمني</option><option value="SAR">SAR — ريال سعودي</option></select></div></div><div style="display:flex;gap:18px;margin-top:13px"><label style="font-size:11px"><input wire:model="currency_yer" type="checkbox"> تفعيل YER</label><label style="font-size:11px"><input wire:model="currency_sar" type="checkbox"> تفعيل SAR</label></div></div>
        </div>
        <div style="display:grid;gap:12px">
            <div class="wg-card wg-card-pad"><h2 class="wg-section-title">سياسة الدفع</h2><div style="display:grid;gap:12px;margin-top:13px"><label class="wg-finance-box" style="display:flex;justify-content:space-between;align-items:center"><div><strong style="font-size:11px">مرجع التحويل</strong><div class="wg-muted" style="font-size:9px;margin-top:4px">إلزام رقم مرجع عند الدفع بتحويل</div></div><input wire:model="require_transfer_reference" type="checkbox"></label><label class="wg-finance-box" style="display:flex;justify-content:space-between;align-items:center"><div><strong style="font-size:11px">إثبات الدفع</strong><div class="wg-muted" style="font-size:9px;margin-top:4px">إلزام رفع صورة الإيصال</div></div><input wire:model="require_payment_proof" type="checkbox"></label></div></div>
            <div class="wg-card wg-card-pad"><h2 class="wg-section-title">التكاملات والخدمات</h2><div style="display:grid;gap:12px;margin-top:13px"><label class="wg-finance-box" style="display:flex;justify-content:space-between;align-items:center"><div><strong style="font-size:11px">واتساب</strong><div class="wg-muted" style="font-size:9px;margin-top:4px">تفعيل قواعد ورسائل واتساب</div></div><input wire:model="whatsapp_enabled" type="checkbox"></label><label class="wg-finance-box" style="display:flex;justify-content:space-between;align-items:center"><div><strong style="font-size:11px">استعلام العضو</strong><div class="wg-muted" style="font-size:9px;margin-top:4px">السماح بصفحة الاستعلام العامة</div></div><input wire:model="member_inquiry_enabled" type="checkbox"></label></div></div>
            <div class="wg-card wg-card-pad">
                <h2 class="wg-section-title">تسعيرة خدمات التغذية المعتمدة (من قبل الإدارة)</h2>
                <div class="wg-muted" style="font-size:10px;margin-top:3px">تحدد الإدارة هنا الأسعار الرسمية لمواعيد التغذية ليتم تطبيقها تلقائياً عند الحجز بدون إدخال يدوي.</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:12px">
                    <div><label class="wg-label">استشارة أولية (YER)</label><input wire:model="nutrition_consultation_yer" type="number" class="wg-field"></div>
                    <div><label class="wg-label">استشارة أولية (SAR)</label><input wire:model="nutrition_consultation_sar" type="number" class="wg-field"></div>
                    <div><label class="wg-label">جلسة متابعة (YER)</label><input wire:model="nutrition_follow_up_yer" type="number" class="wg-field"></div>
                    <div><label class="wg-label">جلسة متابعة (SAR)</label><input wire:model="nutrition_follow_up_sar" type="number" class="wg-field"></div>
                    <div><label class="wg-label">خطة غذائية (YER)</label><input wire:model="nutrition_meal_plan_yer" type="number" class="wg-field"></div>
                    <div><label class="wg-label">خطة غذائية (SAR)</label><input wire:model="nutrition_meal_plan_sar" type="number" class="wg-field"></div>
                    <div><label class="wg-label">تحليل الجسم (YER)</label><input wire:model="nutrition_body_analysis_yer" type="number" class="wg-field"></div>
                    <div><label class="wg-label">تحليل الجسم (SAR)</label><input wire:model="nutrition_body_analysis_sar" type="number" class="wg-field"></div>
                </div>
            </div>
        </div>

        {{-- قسم كليشة وهوية التقارير والمطبوعات (Header & Footer) --}}
        <div class="wg-card wg-card-pad" style="grid-column: 1 / -1; margin-top: 14px;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;border-bottom:1px solid rgba(255,255,255,0.08);padding-bottom:12px;margin-bottom:16px;">
                <div>
                    <h2 class="wg-section-title" style="font-size:16px;display:flex;align-items:center;gap:8px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        <span>كليشة وهوية التقارير والمطبوعات (Header & Footer)</span>
                    </h2>
                    <div class="wg-muted" style="font-size:11px;margin-top:4px;">تخصيص الترويسة والتذييل لكافة المطبوعات الرسمية وكشوفات تسليم الورديات وتقارير النظام مع دعم الطريقتين (بيانات وشعار أو صورة مروّسة كاملة).</div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));gap:20px;">
                {{-- إعدادات الترويسة (Header) --}}
                <div style="background:rgba(255,255,255,0.02);padding:16px;border-radius:8px;border:1px solid rgba(255,255,255,0.06);">
                    <h3 style="font-size:13px;font-weight:800;margin-bottom:12px;color:#38bdf8;display:flex;align-items:center;gap:6px;">
                        <span>1. إعدادات الترويسة (Header)</span>
                    </h3>

                    <div style="margin-bottom:14px;">
                        <label class="wg-label" style="margin-bottom:8px;display:block;">نمط الترويسة المعتمد:</label>
                        <div style="display:flex;gap:12px;">
                            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:12px;padding:8px 12px;background:rgba(255,255,255,0.04);border-radius:6px;border:1px solid rgba(255,255,255,0.1);">
                                <input type="radio" wire:model.live="report_header_mode" value="structured">
                                <strong>كليشة بيانات وشعار (منظمة)</strong>
                            </label>
                            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:12px;padding:8px 12px;background:rgba(255,255,255,0.04);border-radius:6px;border:1px solid rgba(255,255,255,0.1);">
                                <input type="radio" wire:model.live="report_header_mode" value="custom_image">
                                <strong>صورة مروّسة كاملة (جاهزة)</strong>
                            </label>
                        </div>
                    </div>

                    @if($report_header_mode === 'structured')
                        {{-- مدخلات النمط المنظم --}}
                        <div style="display:grid;gap:12px;">
                            <div>
                                <label class="wg-label">شعار النادي (Logo)</label>
                                <div style="display:flex;align-items:center;gap:14px;margin-top:6px;">
                                    @if($logo_upload)
                                        <div style="position:relative;display:inline-block;">
                                            <img src="{{ $logo_upload->temporaryUrl() }}" style="height:55px;border-radius:6px;border:1px solid #38bdf8;padding:2px;background:#fff;object-fit:contain;">
                                            <span style="display:block;font-size:9px;color:#38bdf8;text-align:center;">معاينة جديدة</span>
                                        </div>
                                    @elseif($report_logo_path)
                                        <div style="display:flex;align-items:center;gap:10px;">
                                            <img src="{{ asset('storage/'.$report_logo_path) }}" style="height:55px;border-radius:6px;border:1px solid #64748b;padding:2px;background:#fff;object-fit:contain;">
                                            <button type="button" wire:click="removeLogo" class="wg-btn" style="color:#ef4444;border-color:rgba(239,68,68,0.3);font-size:11px;padding:4px 8px;">حذف الشعار</button>
                                        </div>
                                    @endif
                                    <div style="flex:1;">
                                        <input type="file" wire:model.live="logo_upload" accept="image/png,image/jpeg,image/webp" class="wg-field" style="font-size:11px;padding:6px;">
                                        <div wire:loading wire:target="logo_upload" style="font-size:10px;color:#38bdf8;margin-top:3px;">جاري تحميل الشعار...</div>
                                        <div class="wg-muted" style="font-size:9px;margin-top:2px;">يفضل صورة مفرغة PNG أو JPG بدقة واضحة (الحد الأقصى 2MB).</div>
                                    </div>
                                </div>
                            </div>

                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                                <div>
                                    <label class="wg-label">اسم النادي بالإنجليزية</label>
                                    <input wire:model="report_header_title_en" class="wg-field" dir="ltr" placeholder="WINNER GYM">
                                </div>
                                <div>
                                    <label class="wg-label">السطر التعريفي / النشاط</label>
                                    <input wire:model="report_tagline" class="wg-field" placeholder="صالة اللياقة وبناء الأجسام">
                                </div>
                            </div>
                        </div>
                    @else
                        {{-- مدخلات نمط الصورة المروّسة الكاملة --}}
                        <div style="display:grid;gap:12px;">
                            <div>
                                <label class="wg-label">صورة الترويسة العلوية الكاملة (Header Banner)</label>
                                <div style="margin-top:6px;">
                                    @if($header_image_upload)
                                        <div style="margin-bottom:8px;">
                                            <img src="{{ $header_image_upload->temporaryUrl() }}" style="width:100%;max-height:100px;object-fit:contain;border-radius:6px;border:1px solid #38bdf8;background:#fff;">
                                            <span style="font-size:9px;color:#38bdf8;">معاينة الصورة الجديدة</span>
                                        </div>
                                    @elseif($report_header_image_path)
                                        <div style="margin-bottom:8px;display:flex;align-items:center;gap:12px;">
                                            <img src="{{ asset('storage/'.$report_header_image_path) }}" style="flex:1;max-height:80px;object-fit:contain;border-radius:6px;border:1px solid #64748b;background:#fff;">
                                            <button type="button" wire:click="removeHeaderImage" class="wg-btn" style="color:#ef4444;border-color:rgba(239,68,68,0.3);font-size:11px;padding:4px 8px;">حذف</button>
                                        </div>
                                    @endif
                                    <input type="file" wire:model.live="header_image_upload" accept="image/png,image/jpeg,image/webp" class="wg-field" style="font-size:11px;padding:6px;">
                                    <div wire:loading wire:target="header_image_upload" style="font-size:10px;color:#38bdf8;margin-top:3px;">جاري تحميل الترويسة...</div>
                                    <div class="wg-muted" style="font-size:9px;margin-top:4px;">المقاس الموصى به: عرض 1200 بكسل × ارتفاع 180 إلى 240 بكسل (أبعاد A4 الأفقية).</div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- إعدادات التذييل (Footer) --}}
                <div style="background:rgba(255,255,255,0.02);padding:16px;border-radius:8px;border:1px solid rgba(255,255,255,0.06);">
                    <h3 style="font-size:13px;font-weight:800;margin-bottom:12px;color:#38bdf8;display:flex;align-items:center;gap:6px;">
                        <span>2. إعدادات التذييل (Footer)</span>
                    </h3>

                    <div style="margin-bottom:14px;">
                        <label class="wg-label" style="margin-bottom:8px;display:block;">نمط التذييل المعتمد:</label>
                        <div style="display:flex;gap:12px;">
                            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:12px;padding:8px 12px;background:rgba(255,255,255,0.04);border-radius:6px;border:1px solid rgba(255,255,255,0.1);">
                                <input type="radio" wire:model.live="report_footer_mode" value="structured">
                                <strong>تذييل نصي واعتمادات رسمية</strong>
                            </label>
                            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:12px;padding:8px 12px;background:rgba(255,255,255,0.04);border-radius:6px;border:1px solid rgba(255,255,255,0.1);">
                                <input type="radio" wire:model.live="report_footer_mode" value="custom_image">
                                <strong>صورة تذييل سفلية كاملة</strong>
                            </label>
                        </div>
                    </div>

                    @if($report_footer_mode === 'structured')
                        <div style="display:grid;gap:12px;">
                            <div>
                                <label class="wg-label">النص الختامي / الملاحظات</label>
                                <textarea wire:model="report_footer_text" rows="2" class="wg-field" style="resize:vertical;" placeholder="هذا التقرير معتمد ومستخرج آلياً من نظام WINNER GYM"></textarea>
                            </div>
                            <div style="display:grid;gap:8px;">
                                <label style="display:flex;align-items:center;gap:8px;font-size:12px;cursor:pointer;">
                                    <input type="checkbox" wire:model="report_show_signatures">
                                    <span>إظهار مربعات التوقيع والاعتماد (الموظف المسلّم / إدارة الصالة)</span>
                                </label>
                                <label style="display:flex;align-items:center;gap:8px;font-size:12px;cursor:pointer;">
                                    <input type="checkbox" wire:model="report_show_print_meta">
                                    <span>إظهار تاريخ ووقت الطباعة واسم الموظف المستخرج في أسفل الصفحة</span>
                                </label>
                            </div>
                        </div>
                    @else
                        <div>
                            <label class="wg-label">صورة التذييل السفلية الكاملة (Footer Banner)</label>
                            <div style="margin-top:6px;">
                                @if($footer_image_upload)
                                    <div style="margin-bottom:8px;">
                                        <img src="{{ $footer_image_upload->temporaryUrl() }}" style="width:100%;max-height:80px;object-fit:contain;border-radius:6px;border:1px solid #38bdf8;background:#fff;">
                                        <span style="font-size:9px;color:#38bdf8;">معاينة الصورة الجديدة</span>
                                    </div>
                                @elseif($report_footer_image_path)
                                    <div style="margin-bottom:8px;display:flex;align-items:center;gap:12px;">
                                        <img src="{{ asset('storage/'.$report_footer_image_path) }}" style="flex:1;max-height:70px;object-fit:contain;border-radius:6px;border:1px solid #64748b;background:#fff;">
                                        <button type="button" wire:click="removeFooterImage" class="wg-btn" style="color:#ef4444;border-color:rgba(239,68,68,0.3);font-size:11px;padding:4px 8px;">حذف</button>
                                    </div>
                                @endif
                                <input type="file" wire:model.live="footer_image_upload" accept="image/png,image/jpeg,image/webp" class="wg-field" style="font-size:11px;padding:6px;">
                                <div wire:loading wire:target="footer_image_upload" style="font-size:10px;color:#38bdf8;margin-top:3px;">جاري تحميل التذييل...</div>
                                <div class="wg-muted" style="font-size:9px;margin-top:4px;">المقاس الموصى به: عرض 1200 بكسل × ارتفاع 100 إلى 150 بكسل.</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- صندوق المعاينة الحية التفاعلية (Live Interactive Preview) --}}
            <div style="margin-top:20px;padding-top:16px;border-top:1px solid rgba(255,255,255,0.08);">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                    <div style="font-size:12px;font-weight:800;color:#94a3b8;display:flex;align-items:center;gap:6px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg>
                        <span>معاينة حية لشكل ورقة المطبوعات (A4 Preview)</span>
                    </div>
                    <span style="font-size:10px;color:#64748b;">تحديث فوري مع تغيير الخيارات</span>
                </div>

                {{-- ورقة محاكاة A4 بيضاء مطابقة للمطبوعات --}}
                <div style="background:#ffffff;color:#0f172a;border-radius:8px;padding:18px 24px;box-shadow:0 8px 25px rgba(0,0,0,0.25);max-width:850px;margin:0 auto;border:1px solid #cbd5e1;font-family:sans-serif;">
                    {{-- جزء الترويسة في المعاينة --}}
                    @if($report_header_mode === 'custom_image')
                        @php
                            $previewHeaderImg = $header_image_upload ? $header_image_upload->temporaryUrl() : ($report_header_image_path ? asset('storage/'.$report_header_image_path) : null);
                        @endphp
                        @if($previewHeaderImg)
                            <div style="text-align:center;margin-bottom:12px;">
                                <img src="{{ $previewHeaderImg }}" style="max-height:85px;width:100%;object-fit:contain;">
                            </div>
                        @else
                            <div style="border:2px dashed #94a3b8;padding:16px;text-align:center;border-radius:6px;margin-bottom:12px;color:#64748b;font-size:11px;">
                                [ سيتم عرض صورة الترويسة المرفوعة هنا ]
                            </div>
                        @endif
                    @else
                        @php
                            $previewLogo = $logo_upload ? $logo_upload->temporaryUrl() : ($report_logo_path ? asset('storage/'.$report_logo_path) : null);
                        @endphp
                        <div style="display:table;width:100%;border-bottom:2px solid #0f172a;padding-bottom:10px;margin-bottom:10px;">
                            <div style="display:table-cell;width:38%;vertical-align:middle;text-align:right;">
                                <div style="font-size:15px;font-weight:900;color:#0f172a;">{{ $gym_name ?: 'WINNER GYM' }}</div>
                                <div style="font-size:10px;font-weight:700;color:#475569;">{{ $report_tagline ?: 'صالة اللياقة وبناء الأجسام' }}</div>
                                @if($commercial_register)
                                    <div style="font-size:9px;color:#64748b;">س.ت: {{ $commercial_register }}</div>
                                @endif
                                <div style="font-size:9px;color:#64748b;">{{ $location ?: 'المكلا، اليمن' }}</div>
                            </div>
                            <div style="display:table-cell;width:24%;vertical-align:middle;text-align:center;">
                                @if($previewLogo)
                                    <img src="{{ $previewLogo }}" style="max-height:50px;max-width:100px;object-fit:contain;display:inline-block;">
                                @else
                                    <div style="display:inline-block;padding:6px 12px;border:2px solid #0f172a;border-radius:6px;font-weight:900;font-size:11px;color:#0f172a;">
                                        WINNER
                                    </div>
                                @endif
                            </div>
                            <div style="display:table-cell;width:38%;vertical-align:middle;text-align:left;direction:ltr;">
                                <div style="font-size:13px;font-weight:900;color:#0f172a;">{{ $report_header_title_en ?: 'WINNER GYM' }}</div>
                                <div style="font-size:9px;color:#475569;">SPORTS & FITNESS CLUB</div>
                                @if($phone)
                                    <div style="font-size:9px;color:#64748b;">Tel: {{ $phone }}</div>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- شريط عنوان تجريبي في المعاينة --}}
                    <div style="text-align:center;margin:10px 0 14px 0;">
                        <span style="font-size:13px;font-weight:900;color:#0f172a;padding-bottom:2px;border-bottom:2px solid #0284c7;">
                            كشف تسليم الوردية / تقرير العمليات (نموذج محاكاة)
                        </span>
                    </div>

                    {{-- محتوى ورقة التقرير المحاكي --}}
                    <div style="border:1px dashed #cbd5e1;background:#f8fafc;border-radius:6px;padding:12px;margin-bottom:12px;text-align:center;font-size:10px;color:#64748b;">
                        <div style="display:flex;justify-content:space-around;font-weight:700;color:#334155;margin-bottom:6px;">
                            <span>إجمالي المتحصلات: 150,000 YER</span>
                            <span>عدد العمليات: 24 حركة</span>
                            <span>التاريخ: {{ now('Asia/Aden')->format('Y-m-d') }}</span>
                        </div>
                        <div style="font-size:9px;color:#94a3b8;">[ بيانات التقرير وجداول العمليات تظهر هنا بشكل مفصل ]</div>
                    </div>

                    {{-- جزء التذييل في المعاينة --}}
                    @if($report_footer_mode === 'custom_image')
                        @php
                            $previewFooterImg = $footer_image_upload ? $footer_image_upload->temporaryUrl() : ($report_footer_image_path ? asset('storage/'.$report_footer_image_path) : null);
                        @endphp
                        @if($previewFooterImg)
                            <div style="text-align:center;margin-top:10px;">
                                <img src="{{ $previewFooterImg }}" style="max-height:60px;width:100%;object-fit:contain;">
                            </div>
                        @else
                            <div style="border:2px dashed #94a3b8;padding:10px;text-align:center;border-radius:6px;color:#64748b;font-size:10px;margin-top:10px;">
                                [ سيتم عرض صورة التذييل المرفوعة هنا ]
                            </div>
                        @endif
                    @else
                        @if($report_show_signatures)
                            <div style="display:table;width:100%;margin-top:14px;padding-top:8px;border-top:1px dashed #cbd5e1;">
                                <div style="display:table-cell;width:48%;padding:6px 10px;border:1px solid #e2e8f0;border-radius:4px;font-size:9px;color:#334155;">
                                    <strong style="color:#0f172a;">الموظف المسلّم / الكاشير:</strong>
                                    <div style="margin-top:3px;">الاسم: أحمد محمد</div>
                                    <div style="margin-top:3px;color:#94a3b8;">التوقيع: ............................</div>
                                </div>
                                <div style="display:table-cell;width:4%;"></div>
                                <div style="display:table-cell;width:48%;padding:6px 10px;border:1px solid #e2e8f0;border-radius:4px;font-size:9px;color:#334155;">
                                    <strong style="color:#0f172a;">المستلم / إدارة الصالة:</strong>
                                    <div style="margin-top:3px;">الاسم: ............................</div>
                                    <div style="margin-top:3px;color:#94a3b8;">التوقيع والختم: ............................</div>
                                </div>
                            </div>
                        @endif

                        @if($report_footer_text)
                            <div style="margin-top:10px;text-align:center;font-size:9px;color:#64748b;font-weight:600;">
                                {{ $report_footer_text }}
                            </div>
                        @endif

                        @if($report_show_print_meta)
                            <div style="display:table;width:100%;margin-top:8px;padding-top:4px;border-top:1px solid #f1f5f9;font-size:8px;color:#94a3b8;">
                                <div style="display:table-cell;text-align:right;">وقت الطباعة: {{ now('Asia/Aden')->format('Y-m-d h:i A') }} — مستخرج التقرير: الإدارة</div>
                                <div style="display:table-cell;text-align:left;direction:ltr;">WINNER GYM System</div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </form>
    <div class="wg-card wg-card-pad"><div style="display:flex;justify-content:space-between;align-items:center;gap:12px"><div><h2 class="wg-section-title">فترات الرجال والنساء</h2><div class="wg-muted" style="font-size:10px;margin-top:4px">تم فصل الفترات عن الإعدادات العامة حتى تدير فترتين للرجال وفترتين للنساء لكل فرع.</div></div><a href="{{ route('admin.periods') }}" wire:navigate class="wg-btn">فتح إدارة الفترات</a></div></div>
</div>
