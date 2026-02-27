<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customers\ImportCustomersRequest;
use App\Http\Requests\Customers\ListCustomersRequest;
use App\Http\Requests\Customers\StoreCustomerRequest;
use App\Http\Requests\Customers\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Imports\CustomersUpsertImport;
use App\Jobs\ProcessCustomerImport;
use App\Models\Customer;
use App\Models\CustomerImport;
use App\Services\CustomerService;
use App\Services\Imports\CustomerImportProcessor;
use App\Services\Imports\CustomerImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CustomerController extends Controller
{
    public function index(ListCustomersRequest $request): AnonymousResourceCollection
    {
        $perPage = (int) ($request->validated('perPage') ?? $request->validated('per_page') ?? 10);
        $search = $request->searchTerm();
        $hasVehicles = $request->hasVehiclesFilter();
        $sort = $request->validated('sort');
        $sortDirection = $request->validated('direction');

        $customers = Customer::query()
            ->with(['party.person', 'party.organization', 'party.emails', 'party.phones', 'party.addresses'])
            ->when($hasVehicles, fn ($query) => $query->whereHas('vehicles'))
            ->thatAreMatchingSearchTerm($search)
            ->orderedBy($sort, $sortDirection)
            ->paginate($perPage)
            ->withQueryString();

        return CustomerResource::collection($customers)->additional([
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $sortDirection,
            ],
        ]);
    }

    public function store(StoreCustomerRequest $request, CustomerService $customerService): JsonResponse
    {
        $customer = $customerService->createCustomer($request->validated());

        return response()->json([
            'message' => 'Cliente creado exitosamente',
            'customer' => CustomerResource::make($customer),
        ], 201);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer, CustomerService $customerService): JsonResponse
    {
        $customerService->updateCustomer($customer, $request->validated());

        return response()->json([
            'message' => 'Cliente actualizado exitosamente',
            'customer' => CustomerResource::make($customer->refresh()),
        ]);
    }

    public function destroy(Customer $customer, CustomerService $customerService): JsonResponse
    {
        $customerService->deleteCustomer($customer);

        return response()->json([
            'message' => 'Cliente eliminado exitosamente',
        ]);
    }

    public function import(
        ImportCustomersRequest $request,
        CustomerImportProcessor $processor,
        CustomerImportService $importService
    ): JsonResponse {
        $this->disableExecutionTimeLimit();

        $file = $request->file('file');

        if ($importService->shouldQueueImport($file)) {
            $disk = (string) config('filesystems.default', 'local');
            $filePath = $file->store('imports/customers', $disk);

            if ($filePath === false) {
                throw new \RuntimeException('No fue posible almacenar el archivo de importación.');
            }

            $customerImport = CustomerImport::query()->create([
                'status' => CustomerImport::STATUS_QUEUED,
                'disk' => $disk,
                'file_path' => $filePath,
                'queued_at' => now(),
                'message' => 'Archivo recibido. En espera para procesamiento.',
            ]);

            $queueConnection = (string) config('queue.default', 'sync');
            if ($queueConnection === 'sync') {
                $queueConnection = 'database';
            }

            ProcessCustomerImport::dispatch($customerImport->id, $filePath, $disk)
                ->onConnection($queueConnection)
                ->onQueue((string) config('imports.customers.queue_name', 'imports'));

            return response()->json([
                'message' => 'Archivo recibido. La importación se procesará en segundo plano.',
                'queued' => true,
                'import_id' => $customerImport->id,
            ], 202);
        }

        $importService->validateFile($file);

        $customersImport = new CustomersUpsertImport($processor, $importService);

        $result = DB::transaction(function () use ($customersImport, $file) {
            $customersImport->import($file);

            return $customersImport->result();
        });

        return response()->json([
            'message' => 'Importación procesada exitosamente',
            'result' => $result->toArray(),
            'queued' => false,
        ]);
    }

    public function export(): BinaryFileResponse
    {
        $rows = Customer::query()
            ->with(['party.person', 'party.organization', 'party.emails', 'party.phones', 'party.addresses'])
            ->orderBy('full_name')
            ->get()
            ->map(function (Customer $customer): array {
                $party = $customer->party;
                $primaryEmail = $party?->emails->firstWhere('is_primary', true);
                $primaryPhone = $party?->phones->firstWhere('is_primary', true);
                $primaryAddress = $party?->addresses->firstWhere('is_primary', true)
                    ?? $party?->addresses->first();
                $displayName = $party?->type === 'organization'
                    ? ($party->organization?->legal_name ?? $party->display_name)
                    : ($party?->person?->full_name ?? $party?->display_name);

                return [
                    'full_name' => $displayName ?? $customer->full_name,
                    'email' => $primaryEmail?->email ?? $customer->email,
                    'document_type' => $customer->document_type?->value,
                    'document_number' => $customer->document_number,
                    'phone_number' => $primaryPhone?->phone_number ?? $customer->phone_number,
                    'street' => $primaryAddress?->street,
                    'complement' => $primaryAddress?->complement,
                    'neighborhood' => $primaryAddress?->neighborhood,
                    'city' => $primaryAddress?->city,
                    'state' => $primaryAddress?->state,
                    'postal_code' => $primaryAddress?->postal_code,
                    'country' => $primaryAddress?->country,
                    'reference' => $primaryAddress?->reference,
                    'created_at' => $customer->created_at?->format('Y-m-d H:i:s'),
                ];
            })
            ->all();

        return Excel::download(
            new class($rows) implements FromArray, WithHeadings
            {
                public function __construct(private readonly array $rows) {}

                public function array(): array
                {
                    return $this->rows;
                }

                public function headings(): array
                {
                    return [
                        'Nombre completo',
                        'Correo electrónico',
                        'Tipo de documento',
                        'Número de documento',
                        'Teléfono',
                        'Dirección',
                        'Complemento',
                        'Barrio',
                        'Ciudad',
                        'Departamento',
                        'Código postal',
                        'País',
                        'Referencias',
                        'Fecha de creación',
                    ];
                }
            },
            'clientes-'.now()->format('Ymd_His').'.xlsx'
        );
    }

    public function template(CustomerImportService $importService): BinaryFileResponse
    {
        $headers = $importService->requiredHeaders();
        $columnWidths = $importService->templateColumnWidths();

        return Excel::download(
            new class($headers, $columnWidths) implements FromArray, WithColumnWidths, WithHeadings
            {
                public function __construct(
                    private readonly array $headers,
                    private readonly array $columnWidths
                ) {}

                public function array(): array
                {
                    return [];
                }

                public function headings(): array
                {
                    return $this->headers;
                }

                public function columnWidths(): array
                {
                    return $this->columnWidths;
                }
            },
            'plantilla-clientes.xlsx'
        );
    }

    public function importStatus(CustomerImport $customerImport): JsonResponse
    {
        return response()->json([
            'id' => $customerImport->id,
            'status' => $customerImport->status,
            'message' => $customerImport->message,
            'error' => $customerImport->error,
            'result' => $customerImport->result,
            'queued_at' => $customerImport->queued_at?->toIso8601String(),
            'started_at' => $customerImport->started_at?->toIso8601String(),
            'finished_at' => $customerImport->finished_at?->toIso8601String(),
        ]);
    }

    private function disableExecutionTimeLimit(): void
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        @ini_set('max_execution_time', '0');
    }
}
