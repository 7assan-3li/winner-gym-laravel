<div>
    @if($isOpen)
        <div class="wg-modal-backdrop" style="position:fixed;inset:0;background:rgba(0,0,0,0.65);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;z-index:99999;padding:16px;">
            <div class="wg-modal" dir="rtl" style="max-width:480px;width:100%;box-shadow:0 20px 40px rgba(0,0,0,0.45);border:1px solid rgba(255,255,255,0.12);background:var(--wg-card-bg, #1e293b);border-radius:12px;overflow:hidden;">
                <div class="wg-modal-head" style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid rgba(255,255,255,0.08);">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span style="font-size:20px;">🔑</span>
                        <h2 class="wg-section-title" style="margin:0;font-size:16px;font-weight:800;">
                            {{ $isForced ? 'إلزام تعيين كلمة مرور جديدة' : 'تغيير كلمة المرور' }}
                        </h2>
                    </div>
                    @if(!$isForced)
                        <button type="button" wire:click="close" class="wg-modal-x" style="background:none;border:none;color:#94a3b8;font-size:22px;cursor:pointer;line-height:1;">×</button>
                    @endif
                </div>

                <form wire:submit="save">
                    <div class="wg-modal-body" style="padding:20px;display:grid;gap:14px;">
                        @if($isForced)
                            <div style="background:rgba(245,158,11,0.12);border:1px solid rgba(245,158,11,0.3);color:#fbbf24;padding:10px 14px;border-radius:8px;font-size:12px;line-height:1.5;">
                                <strong>تنبيه أمني:</strong> أنت تستخدم حالياً كلمة مرور مؤقتة تم تعيينها لحسابك. للحفاظ على سرية بياناتك، يرجى تعيين كلمة مرور خاصة بك للمتابعة.
                            </div>
                        @endif

                        @if(!$isForced)
                            <div>
                                <label class="wg-label" style="display:block;margin-bottom:6px;font-size:12px;font-weight:700;">كلمة المرور الحالية *</label>
                                <input
                                    wire:model="current_password"
                                    type="{{ $showPasswords ? 'text' : 'password' }}"
                                    class="wg-field"
                                    dir="ltr"
                                    placeholder="أدخل كلمة المرور الحالية"
                                    autocomplete="current-password"
                                    required
                                >
                                @error('current_password')
                                    <div class="wg-red" style="font-size:11px;color:#f87171;margin-top:4px;">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif

                        <div>
                            <label class="wg-label" style="display:block;margin-bottom:6px;font-size:12px;font-weight:700;">كلمة المرور الجديدة *</label>
                            <input
                                wire:model="new_password"
                                type="{{ $showPasswords ? 'text' : 'password' }}"
                                class="wg-field"
                                dir="ltr"
                                placeholder="8 خانات على الأقل"
                                autocomplete="new-password"
                                required
                                minlength="8"
                            >
                            <div class="wg-muted" style="font-size:10px;margin-top:3px;color:#94a3b8;">يجب ألا تقل عن 8 خانات.</div>
                            @error('new_password')
                                <div class="wg-red" style="font-size:11px;color:#f87171;margin-top:4px;">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="wg-label" style="display:block;margin-bottom:6px;font-size:12px;font-weight:700;">تأكيد كلمة المرور الجديدة *</label>
                            <input
                                wire:model="new_password_confirmation"
                                type="{{ $showPasswords ? 'text' : 'password' }}"
                                class="wg-field"
                                dir="ltr"
                                placeholder="أعد إدخال كلمة المرور الجديدة"
                                autocomplete="new-password"
                                required
                                minlength="8"
                            >
                            @error('new_password_confirmation')
                                <div class="wg-red" style="font-size:11px;color:#f87171;margin-top:4px;">{{ $message }}</div>
                            @enderror
                        </div>

                        <div style="display:flex;align-items:center;gap:8px;margin-top:2px;">
                            <label style="display:flex;align-items:center;gap:6px;font-size:11px;color:#94a3b8;cursor:pointer;">
                                <input type="checkbox" wire:model.live="showPasswords">
                                <span>إظهار كلمات المرور أثناء الكتابة</span>
                            </label>
                        </div>
                    </div>

                    <div class="wg-modal-foot" style="display:flex;justify-content:flex-end;gap:10px;padding:14px 20px;border-top:1px solid rgba(255,255,255,0.08);background:rgba(0,0,0,0.15);">
                        @if(!$isForced)
                            <button type="button" wire:click="close" class="wg-btn" style="padding:8px 16px;">إلغاء</button>
                        @endif
                        <button type="submit" class="wg-btn wg-btn-primary" wire:loading.attr="disabled" style="display:inline-flex;align-items:center;gap:6px;padding:8px 20px;">
                            <span wire:loading.remove wire:target="save">حفظ التغييرات</span>
                            <span wire:loading wire:target="save">جاري الحفظ...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
