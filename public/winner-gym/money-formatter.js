/**
 * WINNER GYM - Universal Money Formatter (Thousands Separator)
 * Automatically formats financial inputs with commas (e.g. 1,000,000)
 * Preserves cursor position, supports decimals, and prevents Livewire morph overrides.
 */
(function () {
    const originalGetter = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value').get;
    const originalSetter = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value').set;

    function formatMoneyString(val) {
        if (val === null || val === undefined) return '';
        let str = String(val).trim();
        if (!str) return '';

        // Allow digits and at most one decimal dot
        const hasDot = str.includes('.');
        str = str.replace(/[^0-9.]/g, '');

        if (!str) return '';

        const parts = str.split('.');
        let integerPart = parts[0] || '0';
        let decimalPart = parts.length > 1 ? '.' + parts.slice(1).join('').slice(0, 2) : (hasDot && str.endsWith('.') ? '.' : '');

        // Remove redundant leading zeros (e.g. "05" -> "5", but keep "0")
        if (integerPart.length > 1 && integerPart.startsWith('0')) {
            integerPart = integerPart.replace(/^0+/, '') || '0';
        }

        integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');

        return integerPart + decimalPart;
    }

    function cleanNumberString(val) {
        if (val === null || val === undefined) return '';
        return String(val).replace(/,/g, '').trim();
    }

    function attachMoneyFormatter(input) {
        if (!input || input._wgMoneyAttached) return;
        input._wgMoneyAttached = true;

        input.setAttribute('inputmode', 'decimal');
        input.setAttribute('autocomplete', 'off');
        input.style.direction = 'ltr';
        input.style.textAlign = 'right';

        // Override the 'value' property descriptor on this specific input
        // This ensures Livewire/Alpine morph cycles and programmatic assignments
        // never overwrite the DOM input with an unformatted number without commas!
        try {
            Object.defineProperty(input, 'value', {
                get() {
                    return originalGetter.call(this);
                },
                set(newVal) {
                    const formatted = formatMoneyString(newVal);
                    originalSetter.call(this, formatted);
                },
                configurable: true,
            });
        } catch (e) {}

        // Format initial value if present
        const currentVal = originalGetter.call(input);
        if (currentVal) {
            originalSetter.call(input, formatMoneyString(currentVal));
        }

        const handleInput = function () {
            const oldVal = originalGetter.call(input);
            const oldCursor = input.selectionStart || 0;
            const digitsBefore = oldVal.slice(0, oldCursor).replace(/\D/g, '').length;

            const formatted = formatMoneyString(oldVal);
            originalSetter.call(input, formatted);

            // Smart cursor position restoration
            let newCursor = 0;
            let count = 0;
            for (let i = 0; i < formatted.length; i++) {
                if (/\d/.test(formatted[i])) {
                    count++;
                }
                if (count === digitsBefore) {
                    newCursor = i + 1;
                    break;
                }
            }
            if (digitsBefore === 0) newCursor = 0;
            if (oldVal.endsWith('.') && formatted.endsWith('.')) {
                newCursor = formatted.length;
            }

            try {
                input.setSelectionRange(newCursor, newCursor);
            } catch (err) {}
        };

        // Smart Backspace handling over commas
        const handleKeyDown = function (e) {
            if (e.key === 'Backspace') {
                const cursor = input.selectionStart || 0;
                const val = originalGetter.call(input);
                if (cursor > 0 && val[cursor - 1] === ',') {
                    e.preventDefault();
                    const newVal = val.slice(0, cursor - 2) + val.slice(cursor - 1);
                    originalSetter.call(input, newVal);
                    input.setSelectionRange(cursor - 1, cursor - 1);
                    handleInput();
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }
        };

        input.addEventListener('input', handleInput);
        input.addEventListener('keyup', handleInput);
        input.addEventListener('keydown', handleKeyDown);
        input.addEventListener('blur', function () {
            const val = originalGetter.call(input);
            if (val) {
                originalSetter.call(input, formatMoneyString(val));
            }
        });
    }

    function initAllMoneyInputs() {
        document.querySelectorAll('input[x-money], input.wg-money-input, [data-money-input]').forEach(attachMoneyFormatter);
    }

    // Register Alpine Directive if Alpine is loaded
    document.addEventListener('alpine:init', () => {
        if (window.Alpine) {
            window.Alpine.directive('money', (el) => {
                attachMoneyFormatter(el);
            });
        }
    });

    // Handle DOM lifecycle and Livewire navigation
    document.addEventListener('DOMContentLoaded', initAllMoneyInputs);
    document.addEventListener('livewire:navigated', initAllMoneyInputs);
    document.addEventListener('livewire:initialized', () => {
        initAllMoneyInputs();

        // Hook into Livewire 3 morph cycle to ensure new or updated elements stay formatted
        if (window.Livewire && window.Livewire.hook) {
            window.Livewire.hook('morph.updated', ({ el }) => {
                if (el && el.matches && el.matches('input[x-money], input.wg-money-input, [data-money-input]')) {
                    attachMoneyFormatter(el);
                    const v = originalGetter.call(el);
                    if (v) originalSetter.call(el, formatMoneyString(v));
                }
            });
        }
    });

    // Mutation observer for dynamically opened modals / elements
    const observer = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            if (mutation.addedNodes.length) {
                initAllMoneyInputs();
            }
        }
    });
    observer.observe(document.documentElement, { childList: true, subtree: true });

    // Expose helpers globally
    window.wgFormatMoney = formatMoneyString;
    window.wgCleanMoney = cleanNumberString;
})();
