<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\AppointmentRequest;
use App\Http\Requests\AppointmentUpdateRequest;
use App\Http\Resources\AppointmentCollection;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\DataTrueResource;
use App\Models\Appointment;
use App\Services\AppointmentService;
use App\Traits\ApiResponseTrait;
use App\Traits\HandlesApiFilters;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * Appointment API Controller.
 *
 * This controller handles the appointments API endpoints:
 * - index: List appointments with filters, search, and pagination
 * - show: Get a single appointment
 * - store: Create a new appointment
 * - update: Update an existing appointment
 * - destroy: Delete an appointment
 * - deleteAll: Delete multiple appointments
 *
 * @package App\Http\Controllers\API
 */
class AppointmentAPIController extends Controller
{
    use ApiResponseTrait;
    use HandlesApiFilters;

    /**
     * Create a new controller instance.
     *
     * @param AppointmentService $service
     */
    public function __construct(
        private AppointmentService $service
    ) {}
    /**
     * List appointments with filters, search, and pagination.
     *
     * @param Request $request
     * @return AppointmentCollection
     */
    public function index(Request $request): AppointmentCollection
    {
        $isLight = $request->get('is_light', false);
        $search = $request->get('search');
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'DESC');
        $perPage = $request->get('per_page', config('constants.apiPerPage', 15));
        $page = $request->get('page', config('constants.apiPage', 1));

        // Get filters from request using trait method
        $filters = $this->parseFilters($request);

        // Get data from service
        $result = $this->service->getAll(
            $filters,
            $search,
            $sortBy,
            $sortOrder,
            (int) $perPage,
            (int) $page,
            $isLight
        );

        return new AppointmentCollection(AppointmentResource::collection($result), AppointmentResource::class);
    }

    /**
     * Get a single appointment by ID.
     *
     * @param Appointment $appointment
     * @return AppointmentCollection
     */
    public function show(Appointment $appointment): AppointmentCollection
    {
        // Use Policy to check authorization: Clinicians can only view their own appointments
        Gate::authorize('view', $appointment);

        // Get appointment with eager loaded relationships from service
        $appointment = $this->service->getById($appointment->id);

        if (!$appointment) {
            return new AppointmentCollection(
                AppointmentResource::collection([]),
                AppointmentResource::class
            );
        }

        return new AppointmentCollection(
            AppointmentResource::collection([$appointment]),
            AppointmentResource::class
        );
    }

    /**
     * Create a new appointment.
     *
     * @param AppointmentRequest $request
     * @return AppointmentCollection
     */
    public function store(AppointmentRequest $request): AppointmentCollection
    {
        // Use Policy to check if user can create appointments
        Gate::authorize('create', Appointment::class);

        // Create appointment using service
        $appointment = $this->service->create($request->validated());

        return new AppointmentCollection(
            AppointmentResource::collection([$appointment]),
            AppointmentResource::class,
            trans('messages.api.create_success', ['model' => 'Appointment'])
        );
    }

    /**
     * Update an existing appointment.
     *
     * @param AppointmentUpdateRequest $request
     * @param int|string $id
     * @return AppointmentCollection
     */
    public function update(AppointmentUpdateRequest $request, $id): AppointmentCollection
    {
        $appointment = Appointment::findOrFail($id);

        // Use Policy to check authorization: Clinicians can only update their own appointments
        Gate::authorize('update', $appointment);

        // Update appointment using service
        $appointment = $this->service->update($appointment, $request->validated());

        return new AppointmentCollection(
            AppointmentResource::collection([$appointment]),
            AppointmentResource::class,
            trans('messages.api.update_success', ['model' => 'Appointment'])
        );
    }

    /**
     * Delete an appointment.
     *
     * @param Request $request
     * @param Appointment $appointment
     * @return DataTrueResource
     */
    public function destroy(Request $request, Appointment $appointment): DataTrueResource
    {
        // Use Policy to check authorization: Clinicians can only delete their own appointments
        Gate::authorize('delete', $appointment);

        // Delete appointment using service
        $this->service->delete($appointment);

        return new DataTrueResource($appointment, trans('messages.api.delete_success', ['model' => 'Appointment']));
    }

    /**
     * Delete multiple appointments.
     *
     * @param Request $request
     * @return DataTrueResource|JsonResponse
     */
    public function deleteAll(Request $request): DataTrueResource|JsonResponse
    {
        /** @var array<int>|null $ids */
        $ids = $request->ids ?? null;

        if (empty($ids) || !is_array($ids)) {
            return $this->errorResponse(
                trans('messages.api.delete_multiple_error'),
                null,
                config('constants.validation_codes.unprocessable_entity')
            );
        }

        // Check authorization for each appointment before deletion
        $user = $request->user();
        if ($user) {
            $appointments = Appointment::whereIn('id', $ids)->get();
            foreach ($appointments as $appointment) {
                if (!$user->can('delete', $appointment)) {
                    return $this->forbiddenResponse(__('messages.appointment.messages.can_only_delete_own'));
                }
            }
        }

        // Delete appointments using service
        $deletedCount = $this->service->deleteMultiple($ids);

        if ($deletedCount > 0) {
            return new DataTrueResource(
                true,
                trans('messages.api.delete_multiple_success', ['models' => Str::plural('Appointment')])
            );
        }

        return $this->errorResponse(
            trans('messages.api.delete_multiple_error'),
            null,
            config('constants.validation_codes.unprocessable_entity')
        );
    }
}
