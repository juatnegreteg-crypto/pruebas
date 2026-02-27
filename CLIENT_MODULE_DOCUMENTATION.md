# Documentación del Módulo de Clientes

## 📋 Resumen Ejecutivo

El módulo de clientes es un sistema completo de gestión de información de clientes implementado en Laravel 12 con Vue.js 3 e Inertia.js. Proporciona operaciones CRUD completas con validaciones robustas tanto en backend como frontend, búsqueda y filtrado avanzado, ordenamiento dinámico, y una interfaz responsiva. Incluye notificaciones de usuario mejoradas mediante toast notifications y un sistema de búsqueda optimizado para mejor experiencia de usuario.

## 🏗️ Arquitectura del Módulo

### Estructura de Archivos

```
app/
├── Http/Controllers/Clients/
│   └── ClientController.php              # Controlador principal
├── Http/Requests/Clients/
│   ├── StoreClientRequest.php            # Validaciones para crear
│   └── UpdateClientRequest.php           # Validaciones para actualizar
├── Http/Resources/
│   └── ClientResource.php                # Transformador de recursos API
├── Models/
│   └── Client.php                        # Modelo Eloquent
├── Services/
│   └── ClientService.php                 # Lógica de negocio
└── Enums/
    └── DocumentType.php                  # Tipos de documento

resources/js/
├── pages/clients/
│   ├── Index.vue                         # Listado con búsqueda y ordenamiento
│   ├── Create.vue                        # Formulario de creación
│   └── Edit.vue                          # Formulario de edición
├── routes/clients/
│   └── index.ts                          # Definiciones de rutas (generado)
└── composables/
    ├── useClientValidation.ts            # Validaciones del frontend
    └── useToast.ts                       # Notificaciones tipo toast

database/
├── migrations/
│   └── 2026_01_28_175706_create_clients_table.php
└── factories/
    └── ClientFactory.php
```

### Patrón Arquitectónico

El módulo sigue el patrón **MVC (Model-View-Controller)** con separación clara de responsabilidades:

- **Model**: `Client` - Representa la entidad y sus relaciones
- **View**: Páginas Vue.js - Interfaz de usuario
- **Controller**: `ClientController` - Maneja solicitudes HTTP
- **Service**: `ClientService` - Lógica de negocio y transacciones
- **Request**: Validaciones del backend
- **Resource**: Transformación de datos para API

## 📊 Modelo de Datos

### Tabla `clients`

| Campo | Tipo | Nullable | Descripción |
|-------|------|----------|-------------|
| `id` | BIGINT (PK) | No | Identificador único |
| `full_name` | VARCHAR(255) | No | Nombre completo del cliente |
| `email` | VARCHAR(255) | No | Correo electrónico (único) |
| `document_type` | VARCHAR(255) | No | Tipo de documento (enum) |
| `document_number` | VARCHAR(50) | No | Número de documento (único) |
| `phone_number` | VARCHAR(10) | Sí | Número telefónico |
| `created_at` | TIMESTAMP | No | Fecha de creación |
| `updated_at` | TIMESTAMP | No | Fecha de actualización |
| `deleted_at` | TIMESTAMP | Sí | Soft delete |

## 🔧 Lógica de Negocio

### Modelo Client (`app/Models/Client.php`)

#### Características Principales
- **Soft Deletes**: Eliminación lógica para preservar integridad de datos
- **Fillable Fields**: Campos permitidos para asignación masiva
- **Casts**: Conversión automática del `document_type` a enum

#### Scopes Disponibles

```php
// Búsqueda por texto en múltiples campos
Client::search('Juan Pérez')->get()

// Ordenamiento seguro con campos permitidos
Client::sort('email', 'desc')->get()

// Cargar con paginación
Client::with('user')->paginate(10)
```

**Campos de búsqueda**: `full_name`, `email`, `document_number`

**Campos de ordenamiento permitidos**: `full_name`, `email`, `document_number`, `created_at`

### Servicio ClientService (`app/Services/ClientService.php`)

Centraliza la lógica de negocio y garantiza consistencia de datos mediante transacciones.

#### Operaciones Soportadas

1. **Crear Cliente**:
   - Validación de datos previa
   - Creación en transacción
   - Retorno del modelo creado

2. **Actualizar Cliente**:
   - Validación de integridad
   - Actualización transaccional
   - Retorno del modelo actualizado

3. **Eliminar Cliente**:
   - Eliminación lógica (soft delete)
   - Mantenimiento de integridad referencial

## 🛣️ Sistema de Rutas

### Rutas del Backend (`routes/web.php`)

```php
Route::resource('clients', ClientController::class)
    ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
    ->middleware(['auth', 'verified']);
```

**Rutas generadas**:
- `GET /clients` → `ClientController@index`
- `GET /clients/create` → `ClientController@create`
- `POST /clients` → `ClientController@store`
- `GET /clients/{client}/edit` → `ClientController@edit`
- `PUT /clients/{client}` → `ClientController@update`
- `DELETE /clients/{client}` → `ClientController@destroy`

### Rutas API (`routes/api.php`)

```php
Route::apiResource('clients', ClientController::class)
    ->middleware(['auth:sanctum', 'verified']);
```

Proporciona endpoints JSON para operaciones CRUD de clientes.

### Sistema de Rutas del Frontend

El frontend utiliza un sistema de rutas type-safe generado automáticamente.

#### Características
- **Type Safety**: Definiciones TypeScript para parámetros y métodos
- **Auto-generado**: Sincronizado con rutas Laravel
- **Soporte REST**: Métodos HTTP apropiados
- **Query Parameters**: Manejo automático de parámetros GET

## 🎛️ Controlador Principal

### ClientController (`app/Http/Controllers/Web/Clients/ClientController.php`)

#### Métodos Implementados

1. **`index(Request $request)`**
   - **Función**: Listado paginado con filtros y búsqueda
   - **Parámetros**: `search`, `sort`, `direction`
   - **Búsqueda**: Debounce de 250ms en el frontend
   - **Ordenamiento**: Dinámico según campo y dirección
   - **Paginación**: 10 elementos por página
   - **Query String**: Mantiene filtros y ordenamiento en URL

2. **`create()`**
   - **Función**: Muestra formulario de creación
   - **Datos**: Opciones de tipos de documento

3. **`store(StoreClientRequest $request)`**
   - **Función**: Crea nuevo cliente
   - **Validación**: `StoreClientRequest`
   - **Feedback**: Toast notification de éxito
   - **Redirección**: Lista con toast de confirmación

4. **`edit(Client $client)`**
   - **Función**: Muestra formulario de edición
   - **Datos**: Cliente existente + tipos de documento

5. **`update(UpdateClientRequest $request, Client $client)`**
   - **Función**: Actualiza cliente existente
   - **Validación**: `UpdateClientRequest` (ignora unicidad propia)
   - **Feedback**: Toast notification de éxito

6. **`destroy(Client $client)`**
   - **Función**: Elimina cliente (soft delete)
   - **Feedback**: Toast notification de confirmación
   - **Redirección**: Lista actualizada

### ClientController API (`app/Http/Controllers/Api/V1/Clients/ClientController.php`)

Controlador dedicado para endpoints REST que retorna `ClientResource`.

#### Métodos
- `index()` - Listado paginado de clientes
- `store()` - Crear cliente
- `show()` - Obtener cliente específico
- `update()` - Actualizar cliente
- `destroy()` - Eliminar cliente

## ✅ Validaciones

### Backend - StoreClientRequest

#### Reglas de Validación

```php
[
    'full_name' => [
        'required', 'string', 'max:255',
        'regex:/^[^\d;\'"\\-]*$/'  // Sin números ni caracteres especiales
    ],
    'email' => ['required', 'email', 'max:255', 'unique:clients,email'],
    'document_type' => ['required', new Enum(DocumentType::class)],
    'document_number' => [
        'required', 'string', 'max:50', 'unique:clients,document_number',
        'digits_between:8,10'
    ],
    'phone_number' => ['nullable', 'string', 'max:10', 'min:10'],
]
```

#### Preparación de Datos
- **Email**: Conversión a minúsculas y eliminación de espacios
- **Document Number**: Eliminación de espacios
- **Phone Number**: Eliminación de espacios (si existe)

### Backend - UpdateClientRequest

**Diferencias clave**:
- **Email**: `Rule::unique('clients', 'email')->ignore($clientId)`
- **Document Number**: `Rule::unique('clients', 'document_number')->ignore($clientId)`

### Frontend - Composable useClientValidation

#### Validaciones Sincronizadas

```typescript
const {
  errors,
  validateFullName,
  validateEmail,
  validateDocumentType,
  validateDocumentNumber,
  validatePhoneNumber,
  validateAll
} = useClientValidation(formData)
```

#### Reglas de Validación Frontend

1. **Nombre Completo**:
   - Requerido, máximo 255 caracteres
   - Sin números ni caracteres especiales (`;`, `'`, `"`, `\`, `-`)

2. **Email**:
   - Requerido, formato válido
   - Conversión automática a minúsculas
   - Máximo 255 caracteres

3. **Tipo de Documento**:
   - Requerido (selección de enum)

4. **Número de Documento**:
   - Requerido, solo dígitos
   - Entre 8 y 10 caracteres

5. **Teléfono**:
   - Opcional, exactamente 10 dígitos
   - Solo números (espacios y guiones permitidos en input)

## 🎨 Interfaz de Usuario

### Composable useToast (`resources/js/composables/useToast.ts`)

#### Función
Proporciona notificaciones tipo toast mejoradas para feedback del usuario.

#### Métodos Disponibles

```typescript
const { showToast } = useToast()

// Mostrar notificación de éxito
showToast('Cliente creado exitosamente', 'success')

// Mostrar notificación de error
showToast('Error al crear el cliente', 'error')

// Mostrar notificación de información
showToast('Operación en progreso', 'info')
```

### Página Index (`resources/js/pages/clients/Index.vue`)

#### Características

- **Vista Responsiva**: Cards en móvil/tablet, tabla en desktop
- **Búsqueda en Tiempo Real**: Con debounce de 250ms para optimización
- **Ordenamiento Dinámico**: Click en encabezados para ordenar
- **Paginación**: Navegación completa con query strings
- **Filtros Persistentes**: Mantienen estado en URL
- **Confirmación de Eliminación**: Diálogo modal
- **Toast Notifications**: Feedback inmediato de acciones
- **Estados de Carga**: Indicadores visuales

#### Funcionalidades

```vue
<!-- Búsqueda con debounce -->
<input @input="onSearchInput($event.target.value)" />

<!-- Ordenamiento al click -->
<th @click="toggleSort('full_name')">Nombre</th>

<!-- Paginación automática -->
<Link v-for="link in clients.links" :href="link.url" />

<!-- Eliminación con confirmación -->
<Dialog v-model:open="showDeleteDialog">
  <!-- Confirmación de eliminación -->
</Dialog>

<!-- Toast notification -->
<Toaster />
```

### Página Create (`resources/js/pages/clients/Create.vue`)

#### Características

- **Validación Híbrida**: Backend + Frontend
- **Feedback Visual**: Estados de error por campo
- **Toast Notifications**: Confirmación de éxito
- **Estados de Carga**: Spinner durante envío
- **Navegación Segura**: Botones de cancelar/volver
- **Preparación de Datos**: Limpieza antes del envío

### Página Edit (`resources/js/pages/clients/Edit.vue`)

Similar a Create pero con datos pre-cargados del cliente existente.

## 🔒 Seguridad y Validación

### Medidas de Seguridad

1. **Middleware de Autenticación**: Todas las rutas protegidas
2. **Validación de Autorización**: Verificación de email
3. **Sanitización de Datos**: Limpieza de espacios y conversión de tipos
4. **Prevención de Inyección**: Validaciones estrictas y regex
5. **Transacciones**: Consistencia de datos en operaciones críticas
6. **CSRF Protection**: Tokens en formularios

### Validaciones Cruzadas

- **Backend**: Validaciones de servidor (autoridad final)
- **Frontend**: Validación inmediata para mejor UX
- **Sincronización**: Reglas consistentes entre capas

## 📱 Experiencia de Usuario

### Navegación Fluida

- **SPA Experience**: Transiciones suaves con Inertia.js
- **Estados Persistentes**: Filtros, ordenamiento y paginación mantenidos
- **Feedback Inmediato**: Toast notifications de éxito/error
- **Estados de Carga**: Indicadores visuales durante operaciones

### Diseño Responsivo

- **Mobile-First**: Optimizado para dispositivos móviles
- **Progressive Enhancement**: Funcionalidad completa en todos los tamaños
- **Accesibilidad**: Etiquetas ARIA y navegación por teclado

## 🧪 Testing

### Cobertura de Tests

El módulo incluye tests exhaustivos en `tests/Feature/Clients/ClientTest.php`:

- **Tests de Creación**: Validaciones y creación exitosa
- **Tests de Actualización**: Modificación de datos existentes
- **Tests de Eliminación**: Soft delete funcional
- **Tests de Validación**: Casos de error y edge cases
- **Tests de Autorización**: Acceso controlado
- **Tests de Búsqueda y Ordenamiento**: Funcionalidad de filtros

### Escenarios de Testing

#### Test Básico de Creación

```php
test('usuario autenticado puede crear un cliente', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('clients.store'), [
            'full_name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'document_type' => 'CC',
            'document_number' => '1234567890',
            'phone_number' => '3001234567',
        ]);

    $response->assertRedirect(route('clients.index'));
    $this->assertDatabaseHas('clients', [
        'email' => 'juan@example.com',
    ]);
});
```

#### Tests de Soft Delete

```php
test('cliente puede ser eliminado con soft delete', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($user)
        ->delete(route('clients.destroy', $client));

    $response->assertRedirect(route('clients.index'));
    
    // Cliente está marcado como eliminado
    expect($client->fresh()->deleted_at)->not->toBeNull();
    
    // Pero aún existe en la base de datos
    $this->assertDatabaseHas('clients', ['id' => $client->id]);
});
```

## 🚀 Rendimiento y Escalabilidad

### Optimizaciones Implementadas

1. **Paginación**: Limitación de resultados (10 por página)
2. **Búsqueda con Debounce**: Reducción de peticiones innecesarias
3. **Lazy Loading**: Relaciones cargadas bajo demanda
4. **Query Optimization**: Scopes eficientes
5. **Transacciones**: Consistencia sin bloqueos prolongados
6. **Caching**: Posible para enums y datos estáticos

### Consideraciones de Escalabilidad

- **Índices de Base de Datos**: Para campos de búsqueda y filtros
- **Cache**: Implementación futura para tipos de documento
- **Queue Jobs**: Para operaciones masivas si es necesario
- **API Rate Limiting**: Protección contra abuso
- **Paginación Cursora**: Para datasets muy grandes

## 🔄 Ciclo de Vida de los Datos

### Flujo de Creación

1. **Frontend**: Usuario ingresa datos → Validación inmediata
2. **Envío**: Datos preparados y enviados via POST
3. **Backend**: Validación de `StoreClientRequest`
4. **Service**: Creación transaccional en `ClientService`
5. **Respuesta**: Redirección con toast de éxito
6. **UI Update**: Actualización automática via Inertia.js

### Flujo de Actualización

Similar al de creación, pero con:
- **Ignorar Unicidad Propia**: En validaciones de email/documento
- **Model Binding**: Inyección automática del modelo `Client`
- **PUT Request**: Método HTTP apropiado

### Flujo de Eliminación

1. **Confirmación**: Diálogo modal de confirmación
2. **DELETE Request**: Envío seguro con CSRF
3. **Soft Delete**: Eliminación lógica en base de datos
4. **Feedback**: Toast de confirmación y actualización de lista

## 📦 Transformador de Recursos API

### ClientResource (`app/Http/Resources/ClientResource.php`)

Transforma modelos `Client` en respuestas JSON estructuradas.

#### Estructura de Respuesta

```json
{
    "id": 1,
    "full_name": "Juan Pérez García",
    "email": "juan.perez@example.com",
    "document_type": "CC",
    "document_number": "1234567890",
    "phone_number": "3001234567",
    "created_at": "2026-02-04T10:30:00Z",
    "updated_at": "2026-02-04T10:30:00Z"
}
```

## 📚 Documentación Técnica Adicional

### Enums y Constantes

#### DocumentType Enum

```php
enum DocumentType: string
{
    case CC = 'CC';          // Cédula de Ciudadanía
    case CE = 'CE';          // Cédula de Extranjería
    case NIT = 'NIT';        // NIT
    case PP = 'PP';          // Pasaporte
    case TI = 'TI';          // Tarjeta de Identidad
}
```

### Formatos de Datos Esperados

- **Email**: `usuario@dominio.com` (convertido a minúsculas)
- **Documento**: Solo dígitos, 8-10 caracteres
- **Teléfono**: 10 dígitos (opcional)
- **Nombre**: Texto sin números ni caracteres especiales

### Manejo de Errores

#### Tipos de Error

1. **Validación**: Datos incorrectos o faltantes
2. **Unicidad**: Email o documento ya existen
3. **Autorización**: Usuario no autenticado
4. **Servidor**: Errores del sistema

#### Mensajes de Error

- **Consistentes**: Mismos mensajes en frontend y backend
- **Específicos**: Indicación clara del campo y problema
- **Accionables**: Guía para corregir el error

## 🎯 Cambios Recientes (2026-02-04)

### ✅ Configuración de pnpm
- Migración completa del proyecto a **pnpm**
- Archivo `.npmrc` con configuraciones optimizadas
- Campo `packageManager` en `package.json`
- Workflows de GitHub Actions actualizados a pnpm

### ✅ Eliminación de Relación Vehículos
- Removido modelo `Vehicle`
- Removida tabla pivote `client_vehicle`
- Simplificación de la estructura de datos
- Actualización de tests relacionados

### ✅ Mejoras en Búsqueda y Notificaciones
- Búsqueda en tiempo real con debounce
- Implementación de toast notifications
- Ordenamiento dinámico mejorado
- Mejor feedback visual del usuario
- Composable `useToast.ts` para notificaciones

### ✅ Optimización de API
- Implementación de `ClientResource`
- Estandarización de respuestas JSON
- Separación clara entre controladores web y API

## 🎯 Conclusiones

El módulo de clientes representa una implementación robusta y completa de gestión de datos de clientes, con:

- ✅ **Arquitectura Limpia**: Separación clara de responsabilidades
- ✅ **Validaciones Exhaustivas**: Backend y frontend sincronizados
- ✅ **Experiencia de Usuario**: Interfaz intuitiva, responsiva y con feedback inmediato
- ✅ **Seguridad**: Autenticación, sanitización de datos y CSRF protection
- ✅ **Mantenibilidad**: Código bien estructurado y testeado
- ✅ **Escalabilidad**: Optimizaciones de rendimiento incluidas
- ✅ **Notificaciones Mejoradas**: Toast notifications para mejor UX
- ✅ **Búsqueda Optimizada**: Debounce y ordenamiento dinámico
- ✅ **Gestión de Dependencias**: Configurado con pnpm para mejor rendimiento

Esta implementación sirve como modelo para otros módulos del sistema, demostrando las mejores prácticas de desarrollo con Laravel, Vue.js e Inertia.js, incluyendo patrones modernos de gestión de estado y notificaciones de usuario.
