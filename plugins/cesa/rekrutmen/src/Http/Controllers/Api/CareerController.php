<?php

namespace Cesa\Rekrutmen\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Cesa\Rekrutmen\Enums\JobApplicationGender;
use Cesa\Rekrutmen\Enums\JobApplicationMaritalStatus;
use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Cesa\Rekrutmen\Models\JobApplication;
use Cesa\Rekrutmen\Models\JobPosting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class CareerController extends Controller
{
    /**
     * @var array<string, array{type: string, required: bool, validation: array<int, string>, enum?: class-string}>
     */
    private const SUPPORTED_APPLICATION_FIELDS = [
        'full_name' => [
            'type'       => 'text',
            'required'   => true,
            'validation' => ['string', 'max:255'],
        ],
        'email' => [
            'type'       => 'email',
            'required'   => true,
            'validation' => ['email', 'max:255'],
        ],
        'gender' => [
            'type'       => 'select',
            'required'   => true,
            'validation' => [],
            'enum'       => JobApplicationGender::class,
        ],
        'birth_date' => [
            'type'       => 'date',
            'required'   => true,
            'validation' => ['date'],
        ],
        'marital_status' => [
            'type'       => 'select',
            'required'   => true,
            'validation' => [],
            'enum'       => JobApplicationMaritalStatus::class,
        ],
        'address_ktp' => [
            'type'       => 'textarea',
            'required'   => true,
            'validation' => ['string'],
        ],
        'address_domicile' => [
            'type'       => 'textarea',
            'required'   => true,
            'validation' => ['string'],
        ],
        'whatsapp_number' => [
            'type'       => 'text',
            'required'   => true,
            'validation' => ['string', 'max:30'],
        ],
        'active_phone' => [
            'type'       => 'text',
            'required'   => true,
            'validation' => ['string', 'max:30'],
        ],
        'emergency_contact_name' => [
            'type'       => 'text',
            'required'   => true,
            'validation' => ['string', 'max:255'],
        ],
        'emergency_contact_relation' => [
            'type'       => 'text',
            'required'   => true,
            'validation' => ['string', 'max:255'],
        ],
        'emergency_contact_phone' => [
            'type'       => 'text',
            'required'   => true,
            'validation' => ['string', 'max:30'],
        ],
        'photo' => [
            'type'       => 'file',
            'required'   => true,
            'validation' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ],
        'resume' => [
            'type'       => 'file',
            'required'   => true,
            'validation' => ['file', 'mimes:pdf,doc,docx', 'max:5120'],
        ],
    ];

    public function index(): JsonResponse
    {
        $jobs = $this->publicJobPostingsQuery()
            ->latest()
            ->get([
                'title',
                'slug',
                'location',
                'thumbnail_path',
                'closing_date',
            ]);

        return response()->json([
            'success' => true,
            'message' => __('rekrutmen::api/career.messages.job_listed'),
            'data'    => $jobs->map(fn (JobPosting $job): array => [
                'title'         => $job->title,
                'slug'          => $job->slug,
                'location'      => $job->location,
                'thumbnail_url' => $job->thumbnail_url,
                'closing_date'  => $job->closing_date,
            ])->values(),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $job = $this->publicJobPostingsQuery()
            ->where('slug', $slug)
            ->first([
                'title',
                'slug',
                'description',
                'requirements',
                'location',
                'thumbnail_path',
                'closing_date',
            ]);

        if (! $job) {
            return response()->json([
                'success' => false,
                'message' => __('rekrutmen::api/career.messages.job_not_open'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => __('rekrutmen::api/career.messages.job_detail_retrieved'),
            'data'    => [
                ...$job->toArray(),
                'thumbnail_url'    => $job->thumbnail_url,
                'application_form' => $this->resolveApplicationFormFields($job->slug),
            ],
        ]);
    }

    public function apply(Request $request, string $slug): JsonResponse
    {
        if (is_string($request->input('email'))) {
            $request->merge([
                'email' => Str::lower(trim($request->input('email'))),
            ]);
        }

        $job = JobPosting::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('closing_date')
                    ->orWhereDate('closing_date', '>=', today());
            })
            ->first();

        if (! $job) {
            return response()->json([
                'success' => false,
                'message' => __('rekrutmen::api/career.messages.job_not_open'),
            ], 404);
        }

        $firstStageId = JobApplication::resolveInitialStageIdForJobPostingId($job->getKey());

        if ($firstStageId === null) {
            return response()->json([
                'success' => false,
                'message' => __('rekrutmen::api/career.messages.job_not_ready'),
            ], 409);
        }

        $validated = Validator::make(
            $request->all(),
            $this->resolveApplicationValidationRules($job),
            trans('rekrutmen::api/career.validation.messages'),
            trans('rekrutmen::api/career.validation.attributes'),
        )->validate();

        $resumePath = $request->hasFile('resume')
            ? $request->file('resume')->store(
                JobApplication::RESUME_DIRECTORY,
                JobApplication::resumeDisk()
            )
            : null;
        $photoPath = $request->hasFile('photo')
            ? $request->file('photo')->store(
                JobApplication::PHOTO_DIRECTORY,
                JobApplication::resumeDisk()
            )
            : null;

        try {
            $application = JobApplication::query()->create([
                'job_posting_id'             => $job->getKey(),
                'current_stage_id'           => $firstStageId,
                'source'                     => $request->input('source', 'oceanspace.co.id'),
                'full_name'                  => $validated['full_name'] ?? null,
                'email'                      => $validated['email'] ?? null,
                'gender'                     => $validated['gender'] ?? null,
                'birth_date'                 => $validated['birth_date'] ?? null,
                'marital_status'             => $validated['marital_status'] ?? null,
                'address_ktp'                => $validated['address_ktp'] ?? null,
                'address_domicile'           => $validated['address_domicile'] ?? null,
                'whatsapp_number'            => $validated['whatsapp_number'] ?? null,
                'active_phone'               => $validated['active_phone'] ?? null,
                'emergency_contact_name'     => $validated['emergency_contact_name'] ?? null,
                'emergency_contact_relation' => $validated['emergency_contact_relation'] ?? null,
                'emergency_contact_phone'    => $validated['emergency_contact_phone'] ?? null,
                'photo_path'                 => $photoPath,
                'resume_path'                => $resumePath,
                'status'                     => JobApplicationStatus::IN_PROGRESS,
            ]);
        } catch (ValidationException $exception) {
            $this->cleanupStoredApplicationFiles($resumePath, $photoPath);

            throw $exception;
        } catch (QueryException $exception) {
            $this->cleanupStoredApplicationFiles($resumePath, $photoPath);

            if ($this->isDuplicateApplicationConstraintViolation($exception)) {
                throw ValidationException::withMessages([
                    $this->resolveDuplicateApplicationField($exception) => __(
                        'rekrutmen::api/career.validation.messages.'.$this->resolveDuplicateApplicationField($exception).'.unique'
                    ),
                ]);
            }

            throw $exception;
        }

        $application->sendSubmittedNotification();

        return response()->json([
            'success' => true,
            'message' => __('rekrutmen::api/career.messages.application_submitted'),
            'data'    => [
                'job_slug'        => $job->slug,
                'applicant_name'  => $application->full_name,
                'applicant_email' => $application->email,
                'status'          => $application->status?->value ?? JobApplicationStatus::IN_PROGRESS->value,
            ],
        ], 201);
    }

    /**
     * @return array<int, array{name: string, label: string, type: string, required: bool, options?: array<int, array{value: string, label: string}>}>
     */
    private function resolveApplicationFormFields(string $slug): array
    {
        return array_map(function (array $field): array {
            $label = $field['label'] ?? null;
            $options = $field['options'] ?? [];

            if (is_string($label) && $label !== '') {
                $field['label'] = __($label);
            }

            if (is_array($options)) {
                $field['options'] = array_map(function (array $option): array {
                    $label = $option['label'] ?? null;

                    if (is_string($label) && $label !== '') {
                        $option['label'] = __($label);
                    }

                    return $option;
                }, $options);
            }

            return $field;
        }, $this->resolveApplicationFieldConfiguration($slug));
    }

    private function cleanupStoredApplicationFiles(?string $resumePath, ?string $photoPath): void
    {
        $paths = array_values(array_filter([$resumePath, $photoPath], static fn (?string $path): bool => is_string($path) && $path !== ''));

        if ($paths === []) {
            return;
        }

        try {
            Storage::disk(JobApplication::resumeDisk())->delete($paths);
        } catch (\Throwable) {
            return;
        }
    }

    private function isDuplicateApplicationConstraintViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;
        $driverCode = $exception->errorInfo[1] ?? null;
        $message = Str::lower($exception->getMessage());

        if (in_array($sqlState, ['23000', '23505'], true) || in_array($driverCode, [19, 1062, 2067], true)) {
            return str_contains($message, 'active_email')
                || str_contains($message, 'rekrutmen_job_applications_active_email_unique')
                || str_contains($message, 'active_whatsapp')
                || str_contains($message, 'rekrutmen_job_applications_active_whatsapp_unique');
        }

        return false;
    }

    private function resolveDuplicateApplicationField(QueryException $exception): string
    {
        $message = Str::lower($exception->getMessage());

        if (
            str_contains($message, 'active_whatsapp')
            || str_contains($message, 'rekrutmen_job_applications_active_whatsapp_unique')
        ) {
            return 'whatsapp_number';
        }

        return 'email';
    }

    /**
     * @return array<int, array{name: string, label?: string, type: string, required: bool, options?: array<int, array{value: string, label: string}>}>
     */
    private function resolveApplicationFieldConfiguration(string $slug): array
    {
        $defaultFields = config('rekrutmen.application_form.default_fields', []);
        $slugFields = config("rekrutmen.application_form.by_slug.{$slug}", []);
        $mergedFields = [];
        $orderedFieldNames = [];

        foreach ([...$defaultFields, ...$slugFields] as $field) {
            $name = $field['name'] ?? null;

            if (! is_string($name) || ! array_key_exists($name, self::SUPPORTED_APPLICATION_FIELDS)) {
                continue;
            }

            if (! array_key_exists($name, $mergedFields)) {
                $orderedFieldNames[] = $name;
            }

            $existingField = $mergedFields[$name] ?? [
                'name'     => $name,
                'label'    => null,
                'type'     => self::SUPPORTED_APPLICATION_FIELDS[$name]['type'],
                'required' => self::SUPPORTED_APPLICATION_FIELDS[$name]['required'],
                'options'  => null,
            ];

            $mergedFields[$name] = [
                'name'     => $name,
                'label'    => array_key_exists('label', $field) ? $field['label'] : $existingField['label'],
                'type'     => self::SUPPORTED_APPLICATION_FIELDS[$name]['type'],
                'required' => array_key_exists('required', $field)
                    ? (bool) $field['required']
                    : $existingField['required'],
                'options'  => array_key_exists('options', $field) && is_array($field['options'])
                    ? $field['options']
                    : $existingField['options'],
            ];
        }

        return array_values(array_map(
            static fn (string $name): array => Arr::whereNotNull($mergedFields[$name]),
            $orderedFieldNames,
        ));
    }

    /**
     * @return array<string, array<int, object|string>>
     */
    private function resolveApplicationValidationRules(JobPosting $job): array
    {
        $configuredFields = collect($this->resolveApplicationFieldConfiguration($job->slug))
            ->keyBy('name');
        $rules = [];

        foreach ($configuredFields as $name => $field) {
            $fieldDefinition = self::SUPPORTED_APPLICATION_FIELDS[$name];
            $baseRules = $fieldDefinition['validation'];
            $presenceRule = ($field['required'] ?? false) ? 'required' : 'nullable';
            $rules[$name] = [$presenceRule, ...$baseRules];

            if (array_key_exists('enum', $fieldDefinition)) {
                $rules[$name][] = new Enum($fieldDefinition['enum']);
            }
        }

        if (array_key_exists('email', $rules)) {
            $rules['email'][] = Rule::unique(JobApplication::class, 'active_email')
                ->where(fn ($query) => $query->where('job_posting_id', $job->getKey()));
        }

        if (array_key_exists('whatsapp_number', $rules)) {
            $rules['whatsapp_number'][] = Rule::unique(JobApplication::class, 'active_whatsapp')
                ->where(fn ($query) => $query->where('job_posting_id', $job->getKey()));
        }

        return $rules;
    }

    private function publicJobPostingsQuery(): Builder
    {
        return JobPosting::query()
            ->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('closing_date')
                    ->orWhereDate('closing_date', '>=', today());
            })
            ->whereHas('rekrutmenPipeline.activeStages');
    }
}
