<div class="min-h-screen bg-[#EFF6FF] px-4 py-8 font-sans antialiased sm:px-6 lg:px-8">
    <div class="mx-auto max-w-2xl">
        @php
            $recaptchaEnabled = $this->isRecaptchaEnabled();
            $recaptchaSiteKey = $recaptchaEnabled ? $this->getRecaptchaSiteKey() : null;
            $recaptchaAction = $recaptchaEnabled ? $this->getRecaptchaAction() : null;
        @endphp

        <form
            x-data="leadRecaptchaForm({
                enabled: {{ $recaptchaEnabled ? 'true' : 'false' }},
                siteKey: @js($recaptchaSiteKey),
                action: @js($recaptchaAction),
            })"
            x-on:submit.prevent="handleSubmit"
        >
            <div class="mb-4 rounded-lg border-t-[10px] cesa-primary-border bg-white shadow-sm">
                <div class="px-6 pt-5 pb-6">
                    <h1 class="text-[32px] font-normal leading-tight text-gray-900">{{ __('lead::views/public-lead-form.title') }}</h1>
                    <p class="mt-2 text-sm text-gray-600">{{ __('lead::views/public-lead-form.description') }}</p>
                </div>
                <div class="border-t border-gray-200 px-6 py-3">
                    <p class="text-xs text-[#D93025]">{{ __('lead::views/public-lead-form.required') }}</p>
                </div>
            </div>

            @error('data')
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $message }}
                </div>
            @enderror

            @error('data.recaptcha_token')
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $message }}
                </div>
            @enderror

            <div class="space-y-4">
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    {{ $this->form }}
                </div>
            </div>

            <div class="mt-4 flex items-center justify-between gap-3 px-1">
                <p class="text-xs text-gray-600">
                    {{ __('lead::views/public-lead-form.pagination.single_page', ['current' => 1, 'total' => 1]) }}
                </p>

                <x-filament::actions
                    :actions="$this->getCachedFormActions()"
                    :full-width="false"
                />
            </div>
        </form>

        @push('scripts')
            @if ($recaptchaEnabled && $recaptchaSiteKey)
                <script src="https://www.google.com/recaptcha/api.js?render={{ $recaptchaSiteKey }}" defer></script>
            @endif
            <script>
                document.addEventListener('alpine:init', () => {
                    Alpine.data('leadRecaptchaForm', ({ enabled, siteKey, action }) => ({
                        enabled,
                        siteKey,
                        action,
                        isProcessing: false,
                        handleSubmit() {
                            if (this.isProcessing) {
                                return;
                            }

                            if (! this.enabled || ! this.siteKey) {
                                this.isProcessing = true;
                                this.$wire.call('submit');

                                return;
                            }

                            if (typeof grecaptcha === 'undefined') {
                                console.warn('reCAPTCHA script not yet loaded.');
                                return;
                            }

                            this.isProcessing = true;

                            grecaptcha.ready(() => {
                                grecaptcha.execute(this.siteKey, { action: this.action || 'submit' })
                                    .then((token) => {
                                        this.$wire.set('data.recaptcha_token', token);
                                        this.$wire.call('submit');
                                    })
                                    .catch(() => {
                                        this.isProcessing = false;
                                    });
                            });
                        },
                    }));
                });
            </script>
        @endpush
    </div>
</div>
