@php
    $user = auth()->user();
    $canManageAllMembers = $user->hasGymPermission('members.manage');
    $canManageMembers = $canManageAllMembers || $user->hasGymPermission('members.update');
    $canCreateMembers = $canManageAllMembers || $user->hasGymPermission('members.create');
    $canManageAllSubscriptions = $user->hasGymPermission('subscriptions.manage');
    $canCreateSubscriptions = $canManageAllSubscriptions || $user->hasGymPermission('subscriptions.create');
@endphp

<div class="wg-members-page" dir="rtl"
    x-data="{
        createOpen:@js(request()->boolean('create') && $canCreateMembers), viewOpen:false, editOpen:false, suspendOpen:false, archiveOpen:false, reactivateOpen:false, moreOpen:null,
        selectedMember:null,
        openView(member) {
            this.selectedMember = member;
            this.moreOpen = null;
            this.viewOpen = true;
        },
        openEdit(member) {
            this.selectedMember = member;
            this.moreOpen = null;
            $wire.$set('editing_id', member.id, false);
            $wire.$set('edit_full_name', member.full_name, false);
            $wire.$set('edit_phone', member.phone, false);
            $wire.$set('edit_gender', member.gender, false);
            $wire.$set('edit_assigned_period', member.assigned_period, false);
            $wire.$set('edit_address', member.address || '', false);
            $wire.$set('edit_identity_number', member.identity_number || '', false);
            $wire.$set('edit_notes', member.notes || '', false);
            $wire.$set('edit_birth_date', member.birth_date || null, false);
            $wire.$set('edit_age', member.age || null, false);
            this.editOpen = true;
        },
        openAction(member, action) {
            this.selectedMember = member;
            this.moreOpen = null;
            $wire.$set('action_member_id', member.id, false);
            $wire.$set('suspension_reason', '', false);
            $wire.$set('suspension_notes', '', false);
            this[action + 'Open'] = true;
        }
    }"
    x-on:member-created.window="createOpen=false"
    x-on:member-view-open.window="viewOpen=true"
    x-on:member-edit-open.window="editOpen=true"
    x-on:member-edit-close.window="editOpen=false"
    x-on:member-suspend-close.window="suspendOpen=false"
    x-on:member-archive-close.window="archiveOpen=false"
    x-on:member-reactivate-close.window="reactivateOpen=false">

    @if(session('success'))
        <div class="wg-members-flash">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="wg-members-errors">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <section class="wg-members-summary-grid">
        @php
            $summaryCards = [
                ['label'=>'إجمالي الأعضاء','value'=>$counts['total'],'tone'=>'blue','note'=>'إجمالي المسجلين','icon'=>'total'],
                ['label'=>'أعضاء نشطون','value'=>$counts['active'],'tone'=>'green','note'=>($counts['total'] ? number_format(($counts['active']/$counts['total'])*100,1) : '0.0').'% من الإجمالي','icon'=>'active'],
                ['label'=>'أعضاء موقوفون','value'=>$counts['suspended'],'tone'=>'orange','note'=>($counts['total'] ? number_format(($counts['suspended']/$counts['total'])*100,1) : '0.0').'% من الإجمالي','icon'=>'suspended'],
                ['label'=>'أعضاء مؤرشفون','value'=>$counts['archived'],'tone'=>'purple','note'=>($counts['total'] ? number_format(($counts['archived']/$counts['total'])*100,1) : '0.0').'% من الإجمالي','icon'=>'archived'],
            ];
        @endphp

        @foreach($summaryCards as $card)
            <article class="wg-members-stat">
                <div>
                    <span class="wg-members-stat-label">{{ $card['label'] }}</span>
                    <strong>{{ number_format($card['value']) }}</strong>
                    <small class="tone-{{ $card['tone'] }}">{{ $card['note'] }}</small>
                </div>
                <div class="wg-members-stat-icon tone-{{ $card['tone'] }}">
                    @if($card['icon'] === 'total')
                        <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M19 8v6M22 11h-6"/></svg>
                    @elseif($card['icon'] === 'active')
                        <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M16 11l2 2 4-4"/></svg>
                    @elseif($card['icon'] === 'suspended')
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M9 8v8M15 8v8"/></svg>
                    @else
                        <svg viewBox="0 0 24 24"><path d="M4 7h16v13H4zM3 4h18v3H3zM9 11h6"/></svg>
                    @endif
                </div>
            </article>
        @endforeach

        <div class="wg-members-summary-actions">
            @if($canCreateMembers)
            <button class="wg-members-primary" type="button" x-on:click="createOpen=true">
                <span>إضافة عضو جديد</span>
                <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
            </button>
            @endif
            <button class="wg-members-secondary" type="button" onclick="document.getElementById('member-search').focus()">
                <span>بحث متقدم</span>
                <svg viewBox="0 0 24 24"><path d="M4 5h16M7 12h10M10 19h4"/></svg>
            </button>
        </div>
    </section>

    <section class="wg-members-filter-panel">
        <div class="wg-members-search-control">
            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
            <input id="member-search" type="search" wire:model.live.debounce.500ms="search" placeholder="ابحث عن اسم العضو أو رقم الهاتف أو كود العضوية...">
        </div>

        <label class="wg-members-select-control">
            <span>حالة العضو</span>
            <select wire:model.live="status_filter">
                <option value="all">الكل</option>
                <option value="active">نشط</option>
                <option value="suspended">موقوف</option>
                <option value="archived">مؤرشف</option>
            </select>
        </label>

        <label class="wg-members-select-control">
            <span>نوع الاشتراك</span>
            <select wire:model.live="package_filter">
                <option value="all">الكل</option>
                @foreach($packages as $package)
                    <option value="{{ $package->id }}">{{ $package->name }}</option>
                @endforeach
            </select>
        </label>

        <label class="wg-members-select-control">
            <span>حالة الاشتراك</span>
            <select wire:model.live="subscription_status_filter">
                <option value="all">الكل</option>
                <option value="active">نشط</option>
                <option value="financial_overdue">متأخر ماليًا</option>
                <option value="expiring_soon">ينتهي قريبًا</option>
                <option value="upcoming">قادم</option>
                <option value="expired">منتهي</option>
                <option value="cancelled">ملغي</option>
                <option value="refunded">مسترد</option>
            </select>
        </label>

        <button class="wg-members-reset" type="button" wire:click="resetFilters">
            <svg viewBox="0 0 24 24"><path d="M3 12a9 9 0 1 0 3-6.7L3 8M3 3v5h5"/></svg>
            <span>إعادة تعيين</span>
        </button>
    </section>

    <section class="wg-members-table-shell">
        <div class="wg-members-table-scroll">
            <table class="wg-members-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>العضو</th>
                        <th>رقم الهاتف</th>
                        <th>نوع الاشتراك</th>
                        <th>تاريخ الاشتراك</th>
                        <th>تاريخ الانتهاء</th>
                        <th>حالة الاشتراك</th>
                        <th>حالة العضو</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($members as $m)
                    @php
                        $sub = $m->latestSubscription;
                        $nextSub = $m->upcomingSubscription;
                        $memberStatus = match($m->status) {
                            'active' => ['نشط','green'],
                            'suspended' => ['موقوف','orange'],
                            'archived' => ['مؤرشف','purple'],
                            default => [$m->status,'blue'],
                        };
                        $subStatus = $sub ? match($sub->status) {
                            'active' => ['نشط','blue'],
                            'financial_overdue' => ['متأخر ماليًا','red'],
                            'expiring_soon' => ['ينتهي قريبًا','orange'],
                            'upcoming' => ['قادم','purple'],
                            'expired' => ['منتهي','red'],
                            'cancelled' => ['ملغي','red'],
                            'refunded' => ['مسترد','purple'],
                            default => [$sub->status,'blue'],
                        } : ['لا يوجد','muted'];
                        $remainingDays = $sub?->end_date && $sub->status === 'active'
                            ? max(0, (int) now('Asia/Aden')->startOfDay()->diffInDays(
                                \Illuminate\Support\Carbon::parse($sub->end_date->toDateString(), 'Asia/Aden')->startOfDay(),
                                false
                            ))
                            : null;
                        $initial = mb_substr($m->full_name, 0, 1);
                        $memberClient = [
                            'id' => $m->id,
                            'full_name' => $m->full_name,
                            'phone' => $m->phone,
                            'membership_code' => $m->membership_code,
                            'gender' => $m->gender,
                            'assigned_period' => $m->assigned_period,
                            'status' => $m->status,
                            'status_label' => $memberStatus[0],
                            'status_tone' => $memberStatus[1],
                            'birth_date' => $m->birth_date?->format('Y-m-d'),
                            'age' => $m->age,
                            'address' => $m->address,
                            'identity_number' => $m->identity_number,
                            'notes' => $m->notes,
                            'registration_date' => $m->registration_date?->format('Y-m-d'),
                            'period_label' => $m->assigned_period === 'men' ? 'الرجال' : 'النساء',
                            'gender_label' => $m->gender === 'male' ? 'ذكر' : 'أنثى',
                            'subscription' => $sub ? [
                                'name' => $sub->package_name_snapshot,
                                'status_label' => $subStatus[0],
                                'start_date' => $sub->start_date?->format('Y-m-d'),
                                'end_date' => $sub->end_date?->format('Y-m-d'),
                            ] : null,
                            'upcoming_subscription' => $nextSub ? [
                                'name' => $nextSub->package_name_snapshot,
                                'status_label' => 'قادم',
                                'start_date' => $nextSub->start_date?->format('Y-m-d'),
                                'end_date' => $nextSub->end_date?->format('Y-m-d'),
                            ] : null,
                            'subscription_url' => route('subscriptions.index', ['member' => $m->id, 'create' => 1]),
                        ];
                    @endphp
                    <tr wire:key="member-row-{{ $m->id }}">
                        <td class="wg-members-row-number">{{ $m->id }}</td>
                        <td>
                            <div class="wg-members-person">
                                <div class="wg-members-avatar">{{ $initial }}</div>
                                <div>
                                    <strong>{{ $m->full_name }}</strong>
                                    <span dir="ltr">{{ $m->membership_code }}</span>
                                </div>
                            </div>
                        </td>
                        <td dir="ltr">{{ $m->phone }}</td>
                        <td>
                            @if($sub)
                                <strong class="wg-members-sub-name">{{ $sub->package_name_snapshot }}</strong>
                                <span class="wg-members-sub-duration">
                                    {{ $sub->duration_value_snapshot }}
                                    {{ match($sub->duration_unit_snapshot){'day'=>'يوم','week'=>'أسبوع','month'=>'شهر','year'=>'سنة',default=>$sub->duration_unit_snapshot} }}
                                </span>
                                @if($nextSub)
                                    <div class="wg-members-next-sub">
                                        <span class="wg-members-next-label">التجديد القادم</span>
                                        <strong>{{ $nextSub->package_name_snapshot }}</strong>
                                        <span>
                                            {{ $nextSub->duration_value_snapshot }}
                                            {{ match($nextSub->duration_unit_snapshot){'day'=>'يوم','week'=>'أسبوع','month'=>'شهر','year'=>'سنة',default=>$nextSub->duration_unit_snapshot} }}
                                        </span>
                                    </div>
                                @endif
                            @else
                                <span class="wg-members-empty">لا يوجد اشتراك</span>
                            @endif
                        </td>
                        <td>
                            <span class="wg-members-date-line">{{ $sub?->start_date?->format('Y-m-d') ?? '—' }}</span>
                            @if($nextSub?->start_date)
                                <span class="wg-members-next-date"><b>التالي:</b> {{ $nextSub->start_date->format('Y-m-d') }}</span>
                            @endif
                        </td>
                        <td>
                            @if($sub?->end_date)
                                <strong class="{{ in_array($sub->status,['active','upcoming']) ? 'wg-members-date-good' : (in_array($sub->status,['financial_overdue','expired','cancelled']) ? 'wg-members-date-bad' : '') }}">{{ $sub->end_date->format('Y-m-d') }}</strong>
                                @if($sub->status === 'active')
                                    <span class="wg-members-days">{{ $remainingDays }} يوم متبقي</span>
                                @endif
                                @if($nextSub?->end_date)
                                    <span class="wg-members-next-date"><b>نهاية التالي:</b> {{ $nextSub->end_date->format('Y-m-d') }}</span>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            <div class="wg-members-status-stack">
                                <span class="wg-members-badge tone-{{ $subStatus[1] }}">{{ $subStatus[0] }}</span>
                                @if($nextSub)
                                    <span class="wg-members-renewed-badge">مجدّد مسبقًا</span>
                                @endif
                            </div>
                        </td>
                        <td><span class="wg-members-badge tone-{{ $memberStatus[1] }}">{{ $memberStatus[0] }}</span></td>
                        <td class="wg-members-actions-cell">
                            <div class="wg-members-actions" dir="ltr">
                                <button type="button" title="عرض التفاصيل" x-on:click="openView(@js($memberClient))">
                                    <svg viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                                </button>

                                @if($canCreateSubscriptions && $m->status === 'active')
                                    <a class="wg-members-quick-subscribe {{ $sub ? '' : 'has-label' }}" title="{{ $sub ? 'تجديد أو إضافة اشتراك جديد' : 'إضافة اشتراك' }}" href="{{ route('subscriptions.index', ['member' => $m->id, 'create' => 1]) }}" wire:navigate>
                                        <svg viewBox="0 0 24 24"><path d="M4 5h16v15H4zM8 3v4M16 3v4M4 10h16M12 13v6M9 16h6"/></svg>
                                        @unless($sub)<span>إضافة اشتراك</span>@endunless
                                    </a>
                                @endif

                                @if($canManageMembers && $m->status !== 'archived')
                                    <button type="button" title="تعديل بيانات العضو" x-on:click="openEdit(@js($memberClient))">
                                        <svg viewBox="0 0 24 24"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>
                                    </button>
                                @endif

                                @if($canManageMembers && $m->status === 'active')
                                    <button type="button" title="تعليق العضو" x-on:click="openAction(@js($memberClient), 'suspend')">
                                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M9 8v8M15 8v8"/></svg>
                                    </button>
                                @elseif($canManageMembers && in_array($m->status, ['suspended','archived']))
                                    <button type="button" title="إعادة تفعيل العضو" class="is-success" x-on:click="openAction(@js($memberClient), 'reactivate')">
                                        <svg viewBox="0 0 24 24"><path d="M20 11a8 8 0 1 0 2 5M20 4v7h-7"/></svg>
                                    </button>
                                @endif

                                @if($canManageMembers)
                                    <div class="wg-members-more-wrap">
                                        <button type="button" title="المزيد" class="is-muted" x-on:click.stop="moreOpen = moreOpen === {{ $m->id }} ? null : {{ $m->id }}">
                                            <svg viewBox="0 0 24 24"><circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/></svg>
                                        </button>
                                        <div class="wg-members-more-menu" x-cloak x-show="moreOpen === {{ $m->id }}" x-transition.origin.top.left x-on:click.outside="moreOpen=null">
                                            @if($m->status !== 'archived')
                                                <button type="button" x-on:click="openEdit(@js($memberClient))">
                                                    <svg viewBox="0 0 24 24"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg><span>تعديل البيانات</span>
                                                </button>
                                            @endif
                                            @if($m->status === 'active')
                                                <button type="button" x-on:click="openAction(@js($memberClient), 'suspend')">
                                                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M9 8v8M15 8v8"/></svg><span>تعليق العضو</span>
                                                </button>
                                            @elseif(in_array($m->status, ['suspended','archived']))
                                                <button type="button" class="is-success-menu" x-on:click="openAction(@js($memberClient), 'reactivate')">
                                                    <svg viewBox="0 0 24 24"><path d="M20 11a8 8 0 1 0 2 5M20 4v7h-7"/></svg><span>إعادة تفعيل العضو</span>
                                                </button>
                                            @endif
                                            @if($m->status !== 'archived')
                                                <button type="button" class="is-danger-menu" x-on:click="openAction(@js($memberClient), 'archive')">
                                                    <svg viewBox="0 0 24 24"><path d="M4 7h16v13H4zM3 4h18v3H3zM9 11h6"/></svg><span>أرشفة العضو</span>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="wg-members-no-results">لا توجد نتائج مطابقة للفلاتر الحالية.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="wg-members-table-footer">
            <div class="wg-members-page-size">10 <span>لكل صفحة</span></div>
            <div class="wg-members-result-count">عرض {{ $members->firstItem() ?? 0 }} - {{ $members->lastItem() ?? 0 }} من {{ number_format($members->total()) }} عضو</div>
            <div class="wg-members-pagination">{{ $members->onEachSide(1)->links() }}</div>
        </div>
    </section>

    {{-- Add Member --}}
    <div class="wg-modal-backdrop" x-cloak x-show="createOpen" x-transition.opacity>
        <div class="wg-members-modal wg-members-modal-form" x-on:click.outside="createOpen=false">
            <form wire:submit="create">
                <div class="wg-members-modal-head">
                    <div>
                        <span class="wg-members-modal-kicker">الأعضاء</span>
                        <h3>إضافة عضو جديد</h3>
                        <p>أدخل بيانات العضو الأساسية. كود العضوية وBarcode وQR يتم توليدها تلقائيًا.</p>
                    </div>
                    <button type="button" x-on:click="createOpen=false">×</button>
                </div>
                <div class="wg-members-modal-body">
                    <div class="wg-members-form-grid">
                        <label><span>الاسم الكامل <b>*</b></span><input wire:model="full_name" placeholder="أدخل الاسم الكامل"></label>
                        <label><span>رقم الهاتف <b>*</b></span><input wire:model="phone" dir="ltr" placeholder="777123456"></label>
                        <label><span>الجنس <b>*</b></span><select wire:model.live="gender"><option value="male">ذكر</option><option value="female">أنثى</option></select></label>
                        <label><span>فترة العضو <b>*</b></span><select wire:model="assigned_period"><option value="men">فترة الرجال</option><option value="women">فترة النساء</option></select></label>
                        <label><span>تاريخ الميلاد</span><input wire:model="birth_date" type="date"></label>
                        <label><span>أو العمر</span><input wire:model="age" type="number" min="5" max="100" placeholder="العمر"></label>
                        <label><span>العنوان (اختياري)</span><input wire:model="address" placeholder="العنوان"></label>
                        <label><span>رقم الهوية (اختياري)</span><input wire:model="identity_number" placeholder="رقم الهوية"></label>
                    </div>
                    <label class="wg-members-full-field"><span>ملاحظات (اختياري)</span><textarea wire:model="notes" placeholder="أي ملاحظات إضافية..."></textarea></label>
                    <div class="wg-members-info-strip">يجب إدخال تاريخ الميلاد أو العمر. حالة العضو تبدأ «نشط». الاشتراك سجل مستقل ويمكن إضافته مباشرة من قائمة المزيد أو من شاشة تفاصيل العضو بعد الحفظ.</div>
                </div>
                <div class="wg-members-modal-foot">
                    <button class="wg-members-primary" type="submit">حفظ العضو</button>
                    <button class="wg-members-secondary" type="button" x-on:click="createOpen=false">إلغاء</button>
                </div>
            </form>
        </div>
    </div>

    {{-- View Member --}}
    <div class="wg-modal-backdrop" x-cloak x-show="viewOpen" x-transition.opacity>
        <div class="wg-members-modal wg-members-modal-form" x-on:click.outside="viewOpen=false">
            <div class="wg-members-modal-head">
                <div><span class="wg-members-modal-kicker">تفاصيل العضو</span><h3 x-text="selectedMember?.full_name || 'العضو'"></h3><p>عرض البيانات الأساسية والاشتراك الجاري والتجديد القادم دون تعديل.</p></div>
                <button type="button" x-on:click="viewOpen=false">×</button>
            </div>
            <div class="wg-members-modal-body" x-show="selectedMember">
                <div class="wg-members-profile-summary">
                    <div class="wg-members-profile-avatar" x-text="selectedMember?.full_name?.charAt(0) || 'ع'"></div>
                    <div><strong x-text="selectedMember?.full_name"></strong><span dir="ltr" x-text="selectedMember?.membership_code"></span></div>
                    <span class="wg-members-badge" :class="'tone-' + (selectedMember?.status_tone || 'blue')" x-text="selectedMember?.status_label"></span>
                </div>
                <div class="wg-members-detail-grid">
                    <div><span>رقم الهاتف</span><strong dir="ltr" x-text="selectedMember?.phone || '—'"></strong></div>
                    <div><span>الجنس</span><strong x-text="selectedMember?.gender_label || '—'"></strong></div>
                    <div><span>الفترة</span><strong x-text="selectedMember?.period_label || '—'"></strong></div>
                    <div><span>تاريخ التسجيل</span><strong x-text="selectedMember?.registration_date || '—'"></strong></div>
                    <div><span>تاريخ الميلاد / العمر</span><strong x-text="selectedMember?.birth_date || (selectedMember?.age ? selectedMember.age + ' سنة' : '—')"></strong></div>
                    <div><span>رقم الهوية</span><strong x-text="selectedMember?.identity_number || '—'"></strong></div>
                    <div class="span-2"><span>العنوان</span><strong x-text="selectedMember?.address || '—'"></strong></div>
                </div>
                <div class="wg-members-subscription-card">
                    <div><span>الاشتراك الحالي</span><strong x-text="selectedMember?.subscription?.name || 'لا يوجد اشتراك'"></strong></div>
                    <div><span>الحالة</span><strong x-text="selectedMember?.subscription?.status_label || '—'"></strong></div>
                    <div><span>البداية</span><strong x-text="selectedMember?.subscription?.start_date || '—'"></strong></div>
                    <div><span>النهاية</span><strong x-text="selectedMember?.subscription?.end_date || '—'"></strong></div>
                </div>
                <template x-if="selectedMember?.upcoming_subscription">
                    <div class="wg-members-subscription-card wg-members-upcoming-card">
                        <div><span>التجديد القادم</span><strong x-text="selectedMember.upcoming_subscription.name"></strong></div>
                        <div><span>الحالة</span><strong x-text="selectedMember.upcoming_subscription.status_label"></strong></div>
                        <div><span>البداية</span><strong x-text="selectedMember.upcoming_subscription.start_date"></strong></div>
                        <div><span>النهاية</span><strong x-text="selectedMember.upcoming_subscription.end_date"></strong></div>
                    </div>
                </template>
                <div class="wg-members-notes-box" x-show="selectedMember?.notes"><span>ملاحظات</span><p x-text="selectedMember?.notes"></p></div>
            </div>
            <div class="wg-members-modal-foot wg-members-detail-actions">
                @if($canCreateSubscriptions)
                    <a class="wg-members-primary" :href="selectedMember?.subscription_url || '#'" wire:navigate x-text="selectedMember?.subscription ? 'تجديد / اشتراك جديد' : 'إضافة اشتراك'"></a>
                @endif
                @if($canManageMembers)
                    <button class="wg-members-secondary" type="button" x-show="selectedMember?.status !== 'archived'" x-on:click="viewOpen=false; openEdit(selectedMember)">تعديل البيانات</button>
                @endif
                <button class="wg-members-secondary" type="button" x-on:click="viewOpen=false">إغلاق</button>
            </div>
        </div>
    </div>

    {{-- Edit Member --}}
    <div class="wg-modal-backdrop" x-cloak x-show="editOpen" x-transition.opacity>
        <div class="wg-members-modal wg-members-modal-form" x-on:click.outside="editOpen=false">
            <form wire:submit="updateMember">
                <div class="wg-members-modal-head">
                    <div><span class="wg-members-modal-kicker">تحديث معلومات العضو</span><h3>تعديل بيانات العضو</h3><p>البيانات هنا مطابقة لمشروع WINNER GYM فقط؛ كود العضوية لا يتغير.</p></div>
                    <button type="button" x-on:click="editOpen=false">×</button>
                </div>
                <div class="wg-members-modal-body">
                    <div class="wg-members-form-grid">
                        <label><span>الاسم الكامل <b>*</b></span><input wire:model="edit_full_name"></label>
                        <label><span>رقم الهاتف <b>*</b></span><input wire:model="edit_phone" dir="ltr"></label>
                        <label><span>الجنس <b>*</b></span><select wire:model="edit_gender"><option value="male">ذكر</option><option value="female">أنثى</option></select></label>
                        <label><span>الفترة <b>*</b></span><select wire:model="edit_assigned_period"><option value="men">فترة الرجال</option><option value="women">فترة النساء</option></select></label>
                        <label><span>تاريخ الميلاد</span><input wire:model="edit_birth_date" type="date"></label>
                        <label><span>أو العمر</span><input wire:model="edit_age" type="number" min="5" max="100"></label>
                        <label><span>العنوان (اختياري)</span><input wire:model="edit_address"></label>
                        <label><span>رقم الهوية (اختياري)</span><input wire:model="edit_identity_number"></label>
                    </div>
                    <label class="wg-members-full-field"><span>ملاحظات (اختياري)</span><textarea wire:model="edit_notes"></textarea></label>
                    <div class="wg-members-info-strip">حالة العضو لا تُعدّل من هذه النافذة؛ استخدم تعليق / إعادة تفعيل / أرشفة للحفاظ على سجل التدقيق.</div>
                </div>
                <div class="wg-members-modal-foot">
                    <button class="wg-members-primary" type="submit">حفظ التعديلات</button>
                    <button class="wg-members-secondary" type="button" x-on:click="editOpen=false">إلغاء</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Suspend Member --}}
    <div class="wg-modal-backdrop" x-cloak x-show="suspendOpen" x-transition.opacity>
        <div class="wg-members-modal wg-members-modal-confirm" x-on:click.outside="suspendOpen=false">
            <form wire:submit="suspend">
                <button class="wg-members-confirm-close" type="button" x-on:click="suspendOpen=false">×</button>
                <div class="wg-members-confirm-icon tone-orange"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M9 8v8M15 8v8"/></svg></div>
                <h3>تعليق العضو</h3>
                <p>سيتم منع العضو من تسجيل الدخول والحضور حتى يتم إلغاء التعليق.</p>
                <div class="wg-members-confirm-member" x-show="selectedMember"><strong x-text="selectedMember?.full_name"></strong><span dir="ltr" x-text="selectedMember?.membership_code"></span></div>
                <label class="wg-members-full-field"><span>سبب التعليق <b>*</b></span>
                    <select wire:model="suspension_reason">
                        <option value="">اختر سبب التعليق</option>
                        <option value="طلب العضو">طلب العضو</option>
                        <option value="قرار إداري">قرار إداري</option>
                        <option value="مخالفة أنظمة النادي">مخالفة أنظمة النادي</option>
                        <option value="أخرى">أخرى</option>
                    </select>
                </label>
                <label class="wg-members-full-field"><span>ملاحظات إضافية (اختياري)</span><textarea wire:model="suspension_notes" maxlength="500" placeholder="اكتب ملاحظات إضافية..."></textarea></label>
                <div class="wg-members-info-strip">التأخر المالي لا يحتاج تعليق العضو يدويًا؛ النظام يمنع الحضور تلقائيًا عند وجود قسط متأخر.</div>
                <div class="wg-members-confirm-actions">
                    <button class="wg-members-primary" type="submit">تعليق العضو</button>
                    <button class="wg-members-secondary" type="button" x-on:click="suspendOpen=false">إلغاء</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Archive Member --}}
    <div class="wg-modal-backdrop" x-cloak x-show="archiveOpen" x-transition.opacity>
        <div class="wg-members-modal wg-members-modal-confirm" x-on:click.outside="archiveOpen=false">
            <button class="wg-members-confirm-close" type="button" x-on:click="archiveOpen=false">×</button>
            <div class="wg-members-confirm-icon tone-blue"><svg viewBox="0 0 24 24"><path d="M4 7h16v13H4zM3 4h18v3H3zM9 11h6"/></svg></div>
            <h3>أرشفة العضو</h3>
            <p>سيتم نقل العضو إلى الأرشيف ولن يتم حذفه نهائيًا.</p>
            <div class="wg-members-confirm-member" x-show="selectedMember"><strong x-text="selectedMember?.full_name"></strong><span dir="ltr" x-text="selectedMember?.membership_code"></span><small dir="ltr" x-text="selectedMember?.phone"></small></div>
            <div class="wg-members-info-strip">تبقى الاشتراكات والمدفوعات والحضور وجميع السجلات المالية محفوظة، ويمكن إعادة تفعيل العضو لاحقًا.</div>
            <div class="wg-members-confirm-actions">
                <button class="wg-members-primary" type="button" wire:click="archiveSelected">أرشفة العضو</button>
                <button class="wg-members-secondary" type="button" x-on:click="archiveOpen=false">إلغاء</button>
            </div>
        </div>
    </div>

    {{-- Reactivate Member --}}
    <div class="wg-modal-backdrop" x-cloak x-show="reactivateOpen" x-transition.opacity>
        <div class="wg-members-modal wg-members-modal-confirm" x-on:click.outside="reactivateOpen=false">
            <button class="wg-members-confirm-close" type="button" x-on:click="reactivateOpen=false">×</button>
            <div class="wg-members-confirm-icon tone-blue"><svg viewBox="0 0 24 24"><path d="M20 11a8 8 0 1 0 2 5M20 4v7h-7"/></svg></div>
            <h3>إعادة تفعيل العضو</h3>
            <p>سيتم تغيير حالة العضو إلى «نشط» فقط.</p>
            <div class="wg-members-confirm-member" x-show="selectedMember"><strong x-text="selectedMember?.full_name"></strong><span dir="ltr" x-text="selectedMember?.membership_code"></span><small x-text="'الحالة الحالية: ' + (selectedMember?.status_label || '—')"></small></div>
            <div class="wg-members-info-strip">إعادة التفعيل لا تنشئ اشتراكًا جديدًا ولا تمدد تاريخ الاشتراك الحالي. صلاحية الحضور تبقى مرتبطة بحالة الاشتراك والدفع والفترة.</div>
            <div class="wg-members-confirm-actions">
                <button class="wg-members-primary" type="button" wire:click="reactivateSelected">إعادة التفعيل</button>
                <button class="wg-members-secondary" type="button" x-on:click="reactivateOpen=false">إلغاء</button>
            </div>
        </div>
    </div>
</div>
