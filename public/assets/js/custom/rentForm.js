// Rental Form JavaScript - Minimal and Safe
(function () {
    'use strict';

    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function () {
        console.log('Rental form JS loaded');

        // Get form element
        const form = document.getElementById('rental-form');
        if (!form) {
            console.error('Rental form not found');
            return;
        }

        // Track if info tab is confirmed
        let isInfoConfirmed = false;

        // Show/hide tabs
        window.showTab = function (tab) {
            const infoContent = document.getElementById('info-content');
            const termsContent = document.getElementById('terms-content');

            if (!infoContent || !termsContent) return;

            if (tab === 'terms' && !isInfoConfirmed) {
                return;
            }

            infoContent.classList.add('hidden');
            termsContent.classList.add('hidden');

            if (tab === 'info') {
                infoContent.classList.remove('hidden');
            } else if (tab === 'terms') {
                termsContent.classList.remove('hidden');
            }
        };

        // Go to terms tab
        window.goToTermsTab = function () {
            // Validate required fields in info tab
            const infoContent = document.getElementById('info-content');
            if (infoContent) {
                const infoInputs = infoContent.querySelectorAll('input[required], select[required]');
                let isValid = true;
                infoInputs.forEach(input => {
                    if (!input.value) {
                        isValid = false;
                        input.classList.add('border-red-500');
                    } else {
                        input.classList.remove('border-red-500');
                    }
                });

                if (!isValid) {
                    alert('Vui lòng nhập đầy đủ thông tin!');
                    return;
                }
            }

            // Call updateTermsTab if it exists (from inline script)
            if (typeof window.updateTermsTab === 'function') {
                window.updateTermsTab();
            }

            isInfoConfirmed = true;
            const termsTab = document.getElementById('terms-tab');
            if (termsTab) {
                termsTab.disabled = false;
            }
            window.showTab('terms');
        };

        // Confirm payment - just submit the form
        // Confirm payment
        window.confirmPayment = function () {
            // Check if terms are agreed
            const agreeCheckbox = document.getElementById('agree-terms');
            if (!agreeCheckbox || !agreeCheckbox.checked) {
                alert('Vui lòng đồng ý với các điều khoản và dịch vụ.');
                return;
            }

            // Call calculateTotal if it exists (from inline script)
            if (typeof window.calculateTotal === 'function') {
                window.calculateTotal();
            }

            // Validate required fields
            const formInputs = form.querySelectorAll('input[required], select[required]');
            let isValid = true;
            formInputs.forEach(input => {
                if (!input.value) {
                    isValid = false;
                    input.classList.add('border-red-500');
                } else {
                    input.classList.remove('border-red-500');
                }
            });

            if (!isValid) {
                alert('Vui lòng nhập đầy đủ thông tin!');
                window.showTab('info');
                return;
            }

            // Submit form
            form.submit();
        };

        // Enable/disable payment button based on checkbox
        const agreeCheckbox = document.getElementById('agree-terms');
        const confirmButton = document.getElementById('confirm-payment');

        if (agreeCheckbox && confirmButton) {
            agreeCheckbox.addEventListener('change', function () {
                if (this.checked) {
                    confirmButton.disabled = false;
                    confirmButton.classList.remove('disabled:opacity-50', 'disabled:cursor-not-allowed');
                } else {
                    confirmButton.disabled = true;
                    confirmButton.classList.add('disabled:opacity-50', 'disabled:cursor-not-allowed');
                }
            });
        }

        console.log('Rental form JS initialized');
    });
})();
