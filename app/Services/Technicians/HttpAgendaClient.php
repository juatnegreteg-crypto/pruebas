<?php

namespace App\Services\Technicians;

use App\Services\Technicians\Contracts\AgendaCancelAppointmentData;
use App\Services\Technicians\Contracts\AgendaCreateAppointmentData;
use App\Services\Technicians\Contracts\AgendaReassignTechnicianData;
use App\Services\Technicians\Contracts\AgendaRescheduleAppointmentData;
use App\Services\Technicians\Contracts\AgendaSlotAvailabilityData;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class HttpAgendaClient implements AgendaClient
{
    /**
     * @param  array{create: string, reschedule: string, reassign_technician: string, cancel: string, availability: string}  $endpoints
     */
    public function __construct(
        private readonly string $baseUrl,
        private readonly int $timeout,
        private readonly int $connectTimeout,
        private readonly int $retryTimes,
        private readonly int $retrySleepMs,
        private readonly ?string $token,
        private readonly array $endpoints,
    ) {
        if ($this->baseUrl === '') {
            throw new RuntimeException('La URL base del cliente HTTP de Agenda no está configurada.');
        }
    }

    public function createAppointment(AgendaCreateAppointmentData $data): void
    {
        $this->sendMutation(
            endpoint: $this->endpoints['create'],
            payload: [
                'appointmentReference' => (string) $data->appointmentReference,
                'startsAt' => $data->slotWindow->startsAt->format('Y-m-d H:i:s'),
                'endsAt' => $data->slotWindow->endsAt->format('Y-m-d H:i:s'),
                'technicianReference' => $data->technicianReference?->value,
            ],
        );
    }

    public function rescheduleAppointment(AgendaRescheduleAppointmentData $data): void
    {
        $this->sendMutation(
            endpoint: $this->resolveEndpointWithAppointment(
                template: $this->endpoints['reschedule'],
                appointmentReference: (string) $data->appointmentReference,
            ),
            payload: [
                'appointmentReference' => (string) $data->appointmentReference,
                'startsAt' => $data->slotWindow->startsAt->format('Y-m-d H:i:s'),
                'endsAt' => $data->slotWindow->endsAt->format('Y-m-d H:i:s'),
                'technicianReference' => $data->technicianReference?->value,
            ],
        );
    }

    public function reassignTechnician(AgendaReassignTechnicianData $data): void
    {
        $this->sendMutation(
            endpoint: $this->resolveEndpointWithAppointment(
                template: $this->endpoints['reassign_technician'],
                appointmentReference: (string) $data->appointmentReference,
            ),
            payload: [
                'appointmentReference' => (string) $data->appointmentReference,
                'startsAt' => $data->slotWindow->startsAt->format('Y-m-d H:i:s'),
                'endsAt' => $data->slotWindow->endsAt->format('Y-m-d H:i:s'),
                'technicianReference' => $data->technicianReference->value,
            ],
        );
    }

    public function cancelAppointment(AgendaCancelAppointmentData $data): void
    {
        $this->sendMutation(
            endpoint: $this->resolveEndpointWithAppointment(
                template: $this->endpoints['cancel'],
                appointmentReference: (string) $data->appointmentReference,
            ),
            payload: [
                'appointmentReference' => (string) $data->appointmentReference,
            ],
        );
    }

    public function isSlotAvailable(AgendaSlotAvailabilityData $data): bool
    {
        $response = $this->newPendingRequest(withRetry: true)->post($this->endpoints['availability'], [
            'startsAt' => $data->slotWindow->startsAt->format('Y-m-d H:i:s'),
            'endsAt' => $data->slotWindow->endsAt->format('Y-m-d H:i:s'),
            'technicianReference' => $data->technicianReference?->value,
            'excludingAppointmentReference' => $data->excludingAppointmentReference?->value,
        ]);

        if ($response->failed()) {
            if ($this->isUnavailableStatus($response)) {
                return false;
            }

            throw new RuntimeException(
                $this->extractMessage($response, 'No fue posible consultar disponibilidad en Agenda.')
            );
        }

        $isAvailable = data_get($response->json(), 'data.isAvailable', data_get($response->json(), 'isAvailable'));

        if (! is_bool($isAvailable)) {
            throw new RuntimeException('Respuesta inválida de Agenda al consultar disponibilidad.');
        }

        return $isAvailable;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function sendMutation(string $endpoint, array $payload): void
    {
        $response = $this->newPendingRequest(withRetry: false)->post($endpoint, $payload);

        if ($response->successful()) {
            return;
        }

        if ($this->isUnavailableStatus($response)) {
            throw new TechnicianAppoimentSlotUnavailableException(
                $this->extractMessage($response, 'La franja seleccionada no está disponible.')
            );
        }

        throw new RuntimeException(
            $this->extractMessage($response, 'No fue posible sincronizar la cita con Agenda.')
        );
    }

    private function newPendingRequest(bool $withRetry): \Illuminate\Http\Client\PendingRequest
    {
        $request = Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeout)
            ->connectTimeout($this->connectTimeout);

        if ($withRetry) {
            $request = $request->retry($this->retryTimes, $this->retrySleepMs);
        }

        if ($this->token !== null && $this->token !== '') {
            $request = $request->withToken($this->token);
        }

        return $request;
    }

    private function isUnavailableStatus(Response $response): bool
    {
        return in_array($response->status(), [409, 422], true);
    }

    private function extractMessage(Response $response, string $fallback): string
    {
        $message = data_get($response->json(), 'message');

        if (! is_string($message) || $message === '') {
            return $fallback;
        }

        return $message;
    }

    private function resolveEndpointWithAppointment(string $template, string $appointmentReference): string
    {
        return str_replace('{appointment}', $appointmentReference, $template);
    }
}
