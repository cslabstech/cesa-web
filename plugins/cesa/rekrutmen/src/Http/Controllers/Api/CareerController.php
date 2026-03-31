<?php

namespace Cesa\Rekrutmen\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Cesa\Rekrutmen\Enums\JobApplicationGender;
use Cesa\Rekrutmen\Enums\JobApplicationMaritalStatus;
use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Cesa\Rekrutmen\Models\JobApplication;
use Cesa\Rekrutmen\Models\JobApplicationHistory;
use Cesa\Rekrutmen\Models\JobPosting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;

class CareerController extends Controller
{
    public function index(): JsonResponse
    {
        $jobs = JobPosting::query()
            ->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('closing_date')
                    ->orWhere('closing_date', '>=', now());
            })
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
        $job = JobPosting::query()
            ->where('slug', $slug)
            ->where('is_published', true)
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
                'message' => __('rekrutmen::api/career.messages.job_not_found'),
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
        $job = JobPosting::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('closing_date')
                    ->orWhere('closing_date', '>=', now());
            })
            ->first();

        if (! $job) {
            return response()->json([
                'success' => false,
                'message' => __('rekrutmen::api/career.messages.job_not_open'),
            ], 404);
        }

        $validated = Validator::make($request->all(), [
            'full_name'                  => ['required', 'string', 'max:255'],
            'email'                      => ['required', 'email', 'max:255'],
            'gender'                     => ['required', new Enum(JobApplicationGender::class)],
            'birth_date'                 => ['required', 'date'],
            'marital_status'             => ['required', new Enum(JobApplicationMaritalStatus::class)],
            'address_ktp'                => ['required', 'string'],
            'address_domicile'           => ['required', 'string'],
            'whatsapp_number'            => ['required', 'string', 'max:30'],
            'active_phone'               => ['required', 'string', 'max:30'],
            'emergency_contact_name'     => ['required', 'string', 'max:255'],
            'emergency_contact_relation' => ['required', 'string', 'max:255'],
            'emergency_contact_phone'    => ['required', 'string', 'max:30'],
            'photo'                      => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'resume'                     => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ], trans('rekrutmen::api/career.validation.messages'), trans('rekrutmen::api/career.validation.attributes'))->validate();

        $resumePath = $request->file('resume')->store(
            JobApplication::RESUME_DIRECTORY,
            JobApplication::resumeDisk()
        );
        $photoPath = $request->file('photo')->store(
            JobApplication::PHOTO_DIRECTORY,
            JobApplication::resumeDisk()
        );

        $firstStageId = $job->rekrutmen_pipeline_id
            ? $job->rekrutmenPipeline?->stages()->orderBy('order_column')->value('id')
            : null;

        $application = JobApplication::query()->create([
            'job_posting_id'             => $job->getKey(),
            'current_stage_id'           => $firstStageId,
            'full_name'                  => $validated['full_name'],
            'email'                      => $validated['email'],
            'gender'                     => $validated['gender'],
            'birth_date'                 => $validated['birth_date'],
            'marital_status'             => $validated['marital_status'],
            'address_ktp'                => $validated['address_ktp'],
            'address_domicile'           => $validated['address_domicile'],
            'whatsapp_number'            => $validated['whatsapp_number'],
            'active_phone'               => $validated['active_phone'],
            'emergency_contact_name'     => $validated['emergency_contact_name'],
            'emergency_contact_relation' => $validated['emergency_contact_relation'],
            'emergency_contact_phone'    => $validated['emergency_contact_phone'],
            'photo_path'                 => $photoPath,
            'resume_path'                => $resumePath,
            'status'                     => JobApplicationStatus::IN_PROGRESS,
        ]);

        JobApplicationHistory::query()->create([
            'job_application_id' => $application->getKey(),
            'from_stage_id'      => null,
            'to_stage_id'        => $firstStageId,
            'status'             => JobApplicationStatus::IN_PROGRESS,
            'notes'              => __('rekrutmen::api/career.application.submitted_via_public_api'),
            'performed_by'       => null,
        ]);

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
        $defaultFields = config('rekrutmen.application_form.default_fields', []);
        $slugFields = config("rekrutmen.application_form.by_slug.{$slug}", []);

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
        }, [...$defaultFields, ...$slugFields]);
    }
}
