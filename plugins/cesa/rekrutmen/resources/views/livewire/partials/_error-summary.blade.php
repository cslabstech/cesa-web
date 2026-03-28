@if ($errors->any())
    <div x-ref="errorSummary" tabindex="-1" class="mb-4 space-y-3 rounded-lg outline-none">
        @if ($fieldErrorMessages->isNotEmpty())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-4 text-sm text-red-700">
                <h2 class="font-semibold text-red-900">
                    {{ $validationTitle }}
                </h2>
                <p class="mt-1">
                    {{ $validationBody }}
                </p>
                <ul class="mt-3 list-disc space-y-1 pl-5">
                    @foreach ($fieldErrorMessages as $fieldErrorMessage)
                        <li>{{ $fieldErrorMessage }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @error('data')
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $message }}
            </div>
        @enderror

        @error('data.recaptcha_token')
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $message }}
            </div>
        @enderror

        @if (isset($extraErrorKeys))
            @foreach ($extraErrorKeys as $extraErrorKey)
                @error($extraErrorKey)
                    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ $message }}
                    </div>
                @enderror
            @endforeach
        @endif
    </div>
@endif
