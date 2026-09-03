/**
 * WINNER GYM - Universal Money Formatter (Thousands Separator)
 * Automatically formats financial inputs with commas (e.g. 1,000,000)
 * Preserves cursor position, supports decimals, and ensures clean numeric sync.
 */
(function () {
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
        if (input._wgMoneyAttached) return;
        input._wgMoneyAttached = true;

        input.setAttribute('inputmode', 'decimal');
        input.setAttribute('autocomplete', 'off');
        input.style.direction = 'ltr';
        input.style.textAlign = 'right';

        // Format initial value if present
        if (input.value) {
            input.value = formatMoneyString(input.value);
        }

        const handleInput = function (e) {
            const oldVal = input.value;
            const oldCursor = input.selectionStart || 0;
            const digitsBefore = oldVal.slice(0, oldCursor).replace(/\D/g, '').length;

            const formatted = formatMoneyString(oldVal);
            input.value = formatted;

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

        input.addEventListener('input', handleInput);
        input.addEventListener('blur', function () {
            if (input.value) {
                input.value = formatMoneyString(input.value);
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
    document.addEventListener('livewire:initialized', initAllMoneyInputs);

    // Mutation observer for dynamically opened modals / elements
    const observer = new MutationObserver(() => {
        initAllMoneyInputs();
    });
    observer.observe(document.documentElement, { childList: true, subtree: true });

    // Expose helpers globally
    window.wgFormatMoney = formatMoneyString;
    window.wgCleanMoney = cleanNumberString;
})();
