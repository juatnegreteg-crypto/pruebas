/* Generated file. Do not edit manually. */
export default {
    "common": {
        "search": "Buscar",
        "clear": "Limpiar",
        "close": "Cerrar",
        "retry": "Reintentar",
        "loading": "Cargando...",
        "previous": "Anterior",
        "next": "Siguiente",
        "perPage": "Resultados por página",
        "noResults": "No se encontraron resultados.",
        "notAvailable": "No disponible"
    },
    "sidebar": {
        "grouping": {
            "label": "Agrupación de navegación",
            "operational": "Operacional",
            "contractual": "Contractual"
        },
        "groups": {
            "platform": "Platform",
            "operations": "Operación",
            "services": "Servicios",
            "certification": "Certificación",
            "administration": "Administración",
            "configuration": "Configuración",
            "contractedModules": "Módulos contratados",
            "technicalMechanicalQuotation": "Cotización Técnico-Mecánica",
            "schedule": "Agenda",
            "digitalCertificates": "Certificados Digitales",
            "customerService": "Servicio al Cliente",
            "other": "Otros"
        }
    },
    "iam": {
        "permissions": {
            "headTitle": "IAM · Permisos",
            "title": "Catálogo de permisos",
            "description": "Catálogo de solo lectura gestionado por código y seeder.",
            "note": "Los permisos no se crean por UI. Cambios solo en database\/seeders\/IamSeeder.php.",
            "actions": {
                "assignToProfiles": "Asignar permisos en perfiles"
            },
            "table": {
                "title": "Permisos",
                "name": "Nombre",
                "module": "Módulo",
                "action": "Acción",
                "description": "Descripción"
            },
            "loading": "Cargando...",
            "errors": {
                "load": "No se pudieron cargar los permisos.",
                "connection": "Error de conexión al cargar permisos."
            }
        },
        "skills": {
            "headTitle": "IAM · Habilidades",
            "title": "Catálogo de habilidades",
            "description": "Catálogo técnico de solo lectura, versionado con código.",
            "note": "Las habilidades no se crean por UI. Cambios por semilla\/migración según catálogo operativo.",
            "actions": {
                "assignToProfiles": "Asignar habilidades en perfiles"
            },
            "table": {
                "title": "Habilidades",
                "name": "Nombre",
                "slug": "Slug",
                "description": "Descripción",
                "status": "Estado"
            },
            "status": {
                "active": "Activa",
                "inactive": "Inactiva"
            },
            "loading": "Cargando...",
            "errors": {
                "load": "No se pudieron cargar las habilidades.",
                "connection": "Error de conexión al cargar habilidades."
            }
        },
        "profiles": {
            "headTitle": "IAM · Perfiles",
            "title": "Perfiles y matriz de acceso",
            "description": "Una capacidad combina permisos requeridos y opcionales. Los requeridos determinan si la capacidad queda activa.",
            "note": "La gestión se hace por capacidades; no se edita un listado atómico independiente.",
            "loading": "Cargando...",
            "actions": {
                "goToUsers": "Ir a usuarios",
                "create": "Crear",
                "createAndAssign": "Crear y asignar capacidades",
                "update": "Actualizar",
                "edit": "Editar",
                "delete": "Eliminar",
                "assignCapabilities": "Asignar capacidades",
                "cancel": "Cancelar",
                "saving": "Guardando..."
            },
            "form": {
                "createTitle": "Crear perfil",
                "editTitle": "Editar perfil",
                "name": "Nombre",
                "slug": "Slug",
                "slugPlaceholder": "operador",
                "description": "Descripción",
                "active": "Perfil activo",
                "technicianProfile": "Perfil técnico (crea vínculo 1:1 con técnicos)"
            },
            "skills": {
                "title": "Habilidades base"
            },
            "table": {
                "title": "Perfiles existentes",
                "profile": "Perfil",
                "users": "Usuarios",
                "permissions": "Permisos",
                "skills": "Skills",
                "actions": "Acciones"
            },
            "errors": {
                "load": "No se pudieron cargar los perfiles.",
                "save": "No se pudo guardar el perfil.",
                "skills": "No se pudieron actualizar las habilidades del perfil.",
                "connection": "Error de conexión al guardar perfil.",
                "delete": "No se pudo eliminar el perfil.",
                "deleteConnection": "Error de conexión al eliminar perfil."
            },
            "toast": {
                "created": "Perfil creado.",
                "updated": "Perfil actualizado.",
                "deleted": "Perfil eliminado."
            },
            "capabilities": {
                "headTitle": "IAM · Capacidades de perfil",
                "title": "Capacidades del perfil",
                "description": "Gestiona los permisos del perfil por capacidad.",
                "actions": {
                    "back": "Volver a perfiles",
                    "save": "Guardar capacidades",
                    "saving": "Guardando..."
                },
                "errors": {
                    "save": "No se pudieron guardar las capacidades del perfil.",
                    "saveConnection": "Error de conexión al guardar capacidades del perfil."
                },
                "toast": {
                    "saved": "Capacidades del perfil actualizadas."
                }
            }
        },
        "capabilities": {
            "title": "Capacidades",
            "showPermissions": "Ver permisos",
            "hidePermissions": "Ocultar permisos",
            "permissionsTitle": "Permisos",
            "actions": {
                "cancel": "Cancelar"
            },
            "subject": {
                "user": "Configurando permisos directos para {name}.",
                "profile": "Configurando permisos del perfil {name}."
            },
            "breakage": {
                "title": "Advertencia de capacidades",
                "description": "Este cambio desactivará estas capacidades porque perderían permisos requeridos:",
                "confirm": "Continuar"
            },
            "warnings": {
                "title": "Advertencias del catálogo de capacidades",
                "description": "Algunos permisos definidos en configuración no existen en base de datos."
            }
        },
        "users": {
            "headTitle": "IAM · Usuarios",
            "title": "Gestión de usuarios",
            "description": "Administra usuarios, perfil único, estado y habilidades directas.",
            "loading": "Cargando...",
            "form": {
                "createTitle": "Crear usuario",
                "editTitle": "Editar usuario",
                "description": "Completa los datos de acceso, perfil y habilidades directas del usuario. Al crear, se enviará una contraseña temporal al correo registrado.",
                "fields": {
                    "username": "Usuario",
                    "fullName": "Nombre",
                    "email": "Correo",
                    "profile": "Perfil"
                },
                "active": "Usuario activo",
                "directSkills": "Habilidades directas",
                "checkingAvailability": "Validando disponibilidad...",
                "checkingMatches": "Buscando coincidencias...",
                "nameMatches": "Posibles coincidencias por nombre",
                "emailMatches": "Posibles coincidencias por correo",
                "link": "Vincular",
                "unlink": "Quitar vínculo",
                "linkedTo": "Vinculado a:",
                "noEmail": "Sin correo"
            },
            "profile": {
                "none": "Sin perfil",
                "search": "Buscar perfil",
                "empty": "No hay perfiles disponibles."
            },
            "candidate": {
                "and": "y"
            },
            "validation": {
                "usernameRequired": "El usuario es obligatorio.",
                "fullNameRequired": "El nombre es obligatorio.",
                "emailRequired": "El correo es obligatorio.",
                "emailInvalid": "Ingresa un correo válido.",
                "valueInUse": "Este valor ya está en uso."
            },
            "actions": {
                "create": "Crear usuario",
                "createAndAssign": "Crear y asignar permisos",
                "saving": "Guardando...",
                "cancel": "Cancelar",
                "edit": "Editar",
                "saveChanges": "Guardar cambios",
                "assignCapabilities": "Asignar capacidades",
                "activate": "Activar",
                "deactivate": "Desactivar",
                "export": "Exportar XLS",
                "import": "Importar XLS",
                "selectSaveAction": "Seleccionar acción de guardado"
            },
            "search": {
                "label": "Buscar usuarios",
                "placeholder": "Usuario, nombre o email",
                "submit": "Buscar",
                "clear": "Limpiar búsqueda"
            },
            "pagination": {
                "perPage": "Registros",
                "total": "Total"
            },
            "table": {
                "username": "Usuario",
                "fullName": "Nombre completo",
                "email": "Correo",
                "profile": "Perfil",
                "status": "Estado",
                "actions": "Acciones",
                "noFullName": "Sin nombre completo"
            },
            "status": {
                "active": "Activo",
                "inactive": "Inactivo"
            },
            "empty": "No hay usuarios para los filtros seleccionados.",
            "errors": {
                "load": "No se pudieron cargar los usuarios.",
                "connection": "Error de conexión al consultar usuarios.",
                "save": "No se pudo guardar el usuario.",
                "saveConnection": "Error de conexión al guardar usuario.",
                "validation": "Errores de validación.",
                "sessionExpired": "Tu sesión expiró. Recarga la página para continuar.",
                "statusUpdate": "No se pudo actualizar el estado.",
                "statusConnection": "Error de conexión al actualizar estado."
            },
            "toast": {
                "created": "Usuario creado.",
                "updated": "Usuario actualizado.",
                "statusUpdated": "Estado del usuario actualizado."
            },
            "capabilities": {
                "headTitle": "IAM · Permisos de usuario",
                "title": "Permisos directos del usuario",
                "description": "Asigna permisos directos por capacidad. No reemplaza el perfil del usuario.",
                "actions": {
                    "back": "Volver a usuarios",
                    "save": "Guardar permisos",
                    "saving": "Guardando..."
                },
                "errors": {
                    "save": "No se pudieron guardar los permisos del usuario.",
                    "saveConnection": "Error de conexión al guardar permisos del usuario."
                },
                "toast": {
                    "saved": "Permisos del usuario actualizados."
                }
            }
        }
    },
    "products": {
        "title": "Productos",
        "subtitle": "Gestiona los productos disponibles para cotizaciones.",
        "listTitle": "Listado de productos",
        "searchLabel": "Buscar por nombre",
        "searchPlaceholder": "Ej: Inspección técnica",
        "summary": "Mostrando {from}-{to} de {total}",
        "summaryEmpty": "Sin productos registrados",
        "pageSummary": "Página {current} de {last}",
        "table": {
            "name": "Producto",
            "description": "Descripción",
            "price": "Precio",
            "status": "Estado",
            "actions": "Acciones"
        },
        "form": {
            "open": "Crear producto",
            "title": "Nuevo producto",
            "description": "Completa la información básica para registrar el producto.",
            "fields": {
                "name": "Nombre",
                "description": "Descripción",
                "observations": "Observaciones",
                "cost": "Costo",
                "price": "Precio",
                "currency": "Moneda",
                "active": "Activo"
            },
            "placeholders": {
                "name": "Ej: Inspección técnica vehicular",
                "description": "Detalle breve del producto",
                "observations": "Observaciones internas del producto",
                "cost": "0.00",
                "price": "0.00",
                "currency": "COP"
            },
            "cancel": "Cancelar",
            "save": "Guardar",
            "saving": "Guardando...",
            "errors": {
                "title": "No se pudo crear el producto.",
                "generic": "Ocurrió un error al crear el producto.",
                "nameRequired": "El nombre es obligatorio.",
                "priceRequired": "El precio es obligatorio."
            }
        },
        "edit": {
            "action": "Editar",
            "title": "Editar producto",
            "description": "Actualiza la información del producto seleccionado.",
            "save": "Guardar cambios",
            "saving": "Guardando...",
            "errors": {
                "title": "No se pudo actualizar el producto.",
                "generic": "Ocurrió un error al actualizar el producto."
            }
        },
        "actions": {
            "enable": "Habilitar",
            "disable": "Deshabilitar",
            "more": "Más acciones",
            "moreSoon": "Más acciones próximamente",
            "export": "Exportar XLS",
            "exporting": "Exportando...",
            "errorTitle": "No se pudo actualizar el estado.",
            "error": "Ocurrió un error al actualizar el estado."
        },
        "status": {
            "active": "Activo",
            "inactive": "Inactivo",
            "ariaActive": "Producto activo",
            "ariaInactive": "Producto inactivo"
        },
        "errors": {
            "load": "Ocurrió un error al cargar los productos."
        },
        "export": {
            "errors": {
                "generic": "Ocurrió un error al exportar los productos."
            }
        },
        "empty": {
            "noProducts": "No hay productos registrados todavía.",
            "noMatch": "No se encontraron productos con “{query}”."
        }
    },
    "services": {
        "title": "Servicios",
        "subtitle": "Gestiona los servicios disponibles para cotizaciones.",
        "listTitle": "Listado de servicios",
        "searchLabel": "Buscar por nombre",
        "searchPlaceholder": "Ej: Diagnóstico eléctrico",
        "summary": "Mostrando {from}-{to} de {total}",
        "summaryEmpty": "Sin servicios registrados",
        "pageSummary": "Página {current} de {last}",
        "table": {
            "name": "Servicio",
            "description": "Descripción",
            "price": "Precio",
            "status": "Estado",
            "actions": "Acciones"
        },
        "form": {
            "open": "Crear servicio",
            "title": "Nuevo servicio",
            "description": "Completa la información básica para registrar el servicio.",
            "fields": {
                "name": "Nombre",
                "description": "Descripción",
                "observations": "Observaciones",
                "price": "Precio",
                "active": "Activo"
            },
            "placeholders": {
                "name": "Ej: Diagnóstico eléctrico",
                "description": "Detalle breve del servicio",
                "observations": "Observaciones internas del servicio",
                "price": "0.00"
            },
            "cancel": "Cancelar",
            "save": "Guardar",
            "saving": "Guardando...",
            "errors": {
                "title": "No se pudo crear el servicio.",
                "generic": "Ocurrió un error al crear el servicio.",
                "nameRequired": "El nombre es obligatorio.",
                "priceRequired": "El precio es obligatorio."
            }
        },
        "import": {
            "open": "Importar XLS",
            "title": "Importar servicios",
            "description": "Carga un archivo XLS\/XLSX\/ODS para crear o actualizar servicios en lote.",
            "recommendation": "Recomendamos descargar primero la plantilla para asegurar que los datos estén en el formato correcto y evitar errores durante la importación.",
            "downloadTemplate": "Descargar plantilla",
            "fields": {
                "file": "Archivo XLS"
            },
            "helper": "Formatos permitidos: .xls, .xlsx, .ods. Maximo 10 MB.",
            "cancel": "Cancelar",
            "submit": "Importar",
            "processing": "Procesando...",
            "progressBatches": "Procesando lote {processed} de {total}...",
            "timeout": "La importación está tardando demasiado. Intenta con un archivo más pequeño.",
            "summary": "Se procesaron {total} filas: {created} creados, {updated} actualizados, {skipped} duplicados omitidos, {failed} fallidos.",
            "errors": {
                "title": "No se pudo importar el archivo.",
                "generic": "Ocurrió un error al procesar la importación.",
                "fileRequired": "Debes seleccionar un archivo.",
                "fileType": "El archivo debe ser .xls, .xlsx u .ods.",
                "row": "Fila",
                "field": "Campo",
                "value": "Valor",
                "message": "Mensaje",
                "truncated": "Mostrando {shown} de {total} errores. Corrige los primeros y vuelve a importar."
            }
        },
        "export": {
            "open": "Exportar XLS",
            "processing": "Exportando...",
            "progressBatches": "Exportando lote {processed} de {total}...",
            "timeout": "La exportación tardó demasiado. Intenta de nuevo más tarde.",
            "errors": {
                "title": "No se pudo exportar los servicios.",
                "generic": "Ocurrió un error al procesar la exportación."
            }
        },
        "edit": {
            "action": "Editar",
            "title": "Editar servicio",
            "description": "Actualiza la información del servicio seleccionado.",
            "save": "Guardar cambios",
            "saving": "Guardando...",
            "errors": {
                "title": "No se pudo actualizar el servicio.",
                "generic": "Ocurrió un error al actualizar el servicio."
            }
        },
        "actions": {
            "enable": "Habilitar",
            "disable": "Deshabilitar",
            "more": "Más acciones",
            "moreSoon": "Más acciones próximamente",
            "errorTitle": "No se pudo actualizar el estado.",
            "error": "Ocurrió un error al actualizar el estado."
        },
        "status": {
            "active": "Activo",
            "inactive": "Inactivo",
            "ariaActive": "Servicio activo",
            "ariaInactive": "Servicio inactivo"
        },
        "errors": {
            "load": "Ocurrió un error al cargar los servicios."
        },
        "empty": {
            "noServices": "No hay servicios registrados todavía.",
            "noMatch": "No se encontraron servicios con “{query}”."
        }
    },
    "taxes": {
        "title": "Impuestos",
        "subtitle": "Administra los impuestos disponibles para los catalogos.",
        "searchLabel": "Buscar por nombre o codigo",
        "searchPlaceholder": "Ej: IVA 19%",
        "table": {
            "name": "Impuesto",
            "code": "Codigo",
            "jurisdiction": "Jurisdiccion",
            "rate": "Tasa (%)",
            "actions": "Acciones"
        },
        "actions": {
            "more": "Más acciones"
        },
        "form": {
            "open": "Crear impuesto",
            "title": "Nuevo impuesto",
            "description": "Completa la informacion basica para registrar el impuesto.",
            "fields": {
                "name": "Nombre",
                "code": "Codigo",
                "jurisdiction": "Jurisdiccion",
                "rate": "Tasa (%)"
            },
            "placeholders": {
                "name": "Ej: IVA",
                "code": "IVA-19",
                "jurisdiction": "Colombia",
                "rate": "Ej: 19"
            },
            "cancel": "Cancelar",
            "save": "Guardar",
            "saving": "Guardando...",
            "errors": {
                "title": "No se pudo crear el impuesto.",
                "generic": "Ocurrio un error al crear el impuesto.",
                "nameRequired": "El nombre es obligatorio.",
                "codeRequired": "El codigo es obligatorio.",
                "jurisdictionRequired": "La jurisdiccion es obligatoria.",
                "rateRequired": "La tasa es obligatoria.",
                "rateInvalid": "La tasa debe ser numerica."
            },
            "help": {
                "codeDescription": "El codigo se usa como identificador interno del impuesto.",
                "codeAuto": "Codigo autogenerado: {code}.",
                "codeAutoPlaceholder": "Se generara al guardar.",
                "jurisdictionReadonly": "Por ahora la jurisdiccion es fija en Colombia."
            },
            "defaults": {
                "jurisdiction": "Colombia"
            }
        },
        "edit": {
            "action": "Editar",
            "title": "Editar impuesto",
            "description": "Actualiza la informacion del impuesto seleccionado.",
            "save": "Guardar cambios",
            "saving": "Guardando...",
            "errors": {
                "title": "No se pudo actualizar el impuesto.",
                "generic": "Ocurrio un error al actualizar el impuesto."
            }
        },
        "delete": {
            "action": "Eliminar",
            "confirm": "Eliminar impuesto \"{name}\"?",
            "errors": {
                "generic": "No se pudo eliminar el impuesto."
            }
        },
        "toast": {
            "created": "Impuesto creado.",
            "updated": "Impuesto actualizado.",
            "deleted": "Impuesto eliminado."
        },
        "errors": {
            "load": "Ocurrio un error al cargar los impuestos."
        },
        "empty": {
            "noTaxes": "No hay impuestos registrados todavia.",
            "noMatch": "No se encontraron impuestos con “{query}”."
        }
    },
    "catalogItemTaxes": {
        "title": "Impuestos",
        "subtitle": "Asocia impuestos al item y define su vigencia.",
        "fields": {
            "tax": "Impuesto",
            "rate": "Tasa (%)",
            "startAt": "Inicio",
            "endAt": "Fin"
        },
        "placeholders": {
            "tax": "Selecciona impuesto",
            "rate": "Ej: 19"
        },
        "actions": {
            "addTax": "Agregar impuesto",
            "removeTax": "Eliminar impuesto",
            "setRange": "Definir rango",
            "clearEnd": "Sin fin",
            "addRange": "Agregar rango",
            "saveRange": "Guardar rango"
        },
        "range": {
            "todayOnwards": "De hoy en adelante",
            "fromOnwards": "{date} en adelante",
            "fromTo": "{start} a {end}"
        },
        "empty": "No hay impuestos asociados.",
        "emptyUnavailable": "No hay impuestos disponibles para asociar."
    },
    "vehicles": {
        "title": "Vehiculos",
        "subtitle": "Registra y actualiza la informacion clave de cada vehiculo.",
        "listTitle": "Listado de vehiculos",
        "searchLabel": "Buscar por placa, marca o modelo",
        "searchPlaceholder": "Ej: ABC-123 o Mazda",
        "summary": "Mostrando {from}-{to} de {total}",
        "summaryEmpty": "Sin vehiculos registrados",
        "pageSummary": "Pagina {current} de {last}",
        "table": {
            "plate": "Placa",
            "vehicle": "Vehiculo",
            "year": "Ano",
            "type": "Tipo",
            "status": "Estado",
            "actions": "Acciones",
            "noVin": "Sin VIN registrado",
            "noType": "Sin tipo"
        },
        "form": {
            "open": "Registrar vehiculo",
            "title": "Nuevo vehiculo",
            "description": "Completa los datos principales para identificar el vehiculo.",
            "sections": {
                "core": "Identificacion",
                "specs": "Especificaciones",
                "observations": "Observaciones",
                "status": "Estado"
            },
            "fields": {
                "customer": "Cliente",
                "plate": "Placa",
                "vin": "VIN",
                "make": "Marca",
                "model": "Modelo",
                "year": "Ano",
                "type": "Tipo de vehiculo",
                "color": "Color",
                "fuel": "Combustible",
                "transmission": "Transmision",
                "mileage": "Kilometraje",
                "observations": "Observaciones",
                "active": "Disponible"
            },
            "placeholders": {
                "customer": "Selecciona un cliente",
                "plate": "ABC-123",
                "vin": "VIN opcional",
                "make": "Ej: Mazda",
                "model": "Ej: CX-5",
                "year": "2023",
                "type": "Selecciona un tipo",
                "color": "Ej: Gris",
                "fuel": "Selecciona el combustible",
                "transmission": "Selecciona la transmision",
                "mileage": "Ej: 45000",
                "observations": "Observaciones internas del vehiculo"
            },
            "help": {
                "vin": "Opcional, pero recomendado para trazabilidad.",
                "active": "Controla si el vehiculo esta disponible."
            },
            "options": {
                "empty": "Sin seleccionar",
                "type": {
                    "sedan": "Sedan",
                    "suv": "SUV",
                    "pickup": "Pickup",
                    "van": "Van",
                    "motorcycle": "Moto",
                    "truck": "Camion",
                    "other": "Otro"
                },
                "fuel": {
                    "gasoline": "Gasolina",
                    "diesel": "Diesel",
                    "electric": "Electrico",
                    "hybrid": "Hibrido",
                    "gas": "Gas",
                    "other": "Otro"
                },
                "transmission": {
                    "manual": "Manual",
                    "automatic": "Automatica"
                }
            },
            "cancel": "Cancelar",
            "save": "Guardar",
            "saving": "Guardando...",
            "errors": {
                "title": "No se pudo registrar el vehiculo.",
                "generic": "Ocurrio un error al registrar el vehiculo.",
                "customerRequired": "El cliente es obligatorio.",
                "plateRequired": "La placa es obligatoria.",
                "makeRequired": "La marca es obligatoria.",
                "modelRequired": "El modelo es obligatorio.",
                "yearRequired": "El ano es obligatorio."
            }
        },
        "edit": {
            "action": "Editar",
            "title": "Editar vehiculo",
            "description": "Actualiza los datos del vehiculo seleccionado.",
            "save": "Guardar cambios",
            "saving": "Guardando...",
            "errors": {
                "title": "No se pudo actualizar el vehiculo.",
                "generic": "Ocurrio un error al actualizar el vehiculo."
            }
        },
        "actions": {
            "enable": "Habilitar",
            "disable": "Deshabilitar",
            "more": "Más acciones",
            "moreSoon": "Mas acciones proximamente",
            "errorTitle": "No se pudo actualizar el estado.",
            "error": "Ocurrio un error al actualizar el estado."
        },
        "status": {
            "active": "Activo",
            "inactive": "Inactivo",
            "ariaActive": "Vehiculo activo",
            "ariaInactive": "Vehiculo inactivo"
        },
        "errors": {
            "load": "Ocurrio un error al cargar los vehiculos."
        },
        "empty": {
            "noVehicles": "No hay vehiculos registrados todavia.",
            "noMatch": "No se encontraron vehiculos con “{query}”."
        }
    },
    "bundles": {
        "title": "Paquetes",
        "subtitle": "Gestiona los paquetes compuestos por productos y servicios.",
        "listTitle": "Listado de paquetes",
        "actions": {
            "view": "Detalle",
            "more": "Más acciones"
        },
        "form": {
            "open": "Crear paquete",
            "title": "Nuevo paquete",
            "description": "Completa la información básica para registrar el paquete.",
            "fields": {
                "name": "Nombre",
                "description": "Descripción",
                "observations": "Observaciones",
                "price": "Precio",
                "active": "Activo"
            },
            "placeholders": {
                "name": "Ej: Paquete de mantenimiento",
                "description": "Detalle breve del paquete",
                "observations": "Observaciones internas del paquete",
                "price": "0.00"
            },
            "cancel": "Cancelar",
            "save": "Guardar",
            "saving": "Guardando...",
            "errors": {
                "title": "No se pudo crear el paquete.",
                "generic": "Ocurrió un error al crear el paquete.",
                "catalogLoad": "No se pudieron cargar los items disponibles.",
                "nameRequired": "El nombre es obligatorio.",
                "priceRequired": "El precio es obligatorio."
            },
            "itemTypes": {
                "product": "Producto",
                "service": "Servicio",
                "bundle": "Paquete"
            },
            "items": {
                "title": "Items del paquete",
                "search": "Buscar item",
                "searchPlaceholder": "Busca por nombre o tipo",
                "item": "Item",
                "quantity": "Cantidad",
                "placeholder": "Selecciona un item",
                "add": "Agregar item",
                "remove": "Quitar",
                "empty": "Aún no agregaste items a este paquete."
            }
        },
        "edit": {
            "action": "Editar",
            "title": "Editar paquete",
            "description": "Actualiza la información del paquete seleccionado.",
            "save": "Guardar cambios",
            "saving": "Guardando...",
            "errors": {
                "title": "No se pudo actualizar el paquete.",
                "generic": "Ocurrió un error al actualizar el paquete.",
                "load": "No se pudo cargar el detalle del paquete."
            }
        },
        "searchLabel": "Buscar por nombre",
        "searchPlaceholder": "Ej: Mantenimiento preventivo",
        "summary": "Mostrando {from}-{to} de {total}",
        "summaryEmpty": "Sin paquetes registrados",
        "pageSummary": "Página {current} de {last}",
        "table": {
            "actions": "Detalle",
            "name": "Paquete",
            "description": "Descripción",
            "price": "Precio",
            "items": "Items",
            "status": "Estado"
        },
        "status": {
            "active": "Activo",
            "inactive": "Inactivo",
            "ariaActive": "Paquete activo",
            "ariaInactive": "Paquete inactivo"
        },
        "errors": {
            "load": "Ocurrió un error al cargar los paquetes."
        },
        "empty": {
            "noBundles": "No hay paquetes registrados todavía.",
            "noMatch": "No se encontraron paquetes con “{query}”."
        },
        "inline": {
            "expand": "Ver items",
            "collapse": "Ocultar items",
            "empty": "Este paquete no tiene items todavía.",
            "errors": {
                "load": "Ocurrió un error al cargar los items del paquete."
            }
        },
        "detail": {
            "label": "Detalle de paquete",
            "back": "Volver a paquetes",
            "noDescription": "Sin descripción registrada.",
            "itemsTitle": "Items del paquete",
            "itemsSummary": "{total} items en este paquete",
            "empty": "Este paquete no tiene items todavía.",
            "table": {
                "name": "Item",
                "type": "Tipo",
                "quantity": "Cantidad",
                "price": "Precio",
                "status": "Estado"
            }
        }
    },
    "quotes": {
        "title": "Cotizaciones",
        "subtitle": "Gestiona las cotizaciones del sistema.",
        "listTitle": "Listado de cotizaciones",
        "searchLabel": "Buscar por ID, total, cliente o placa",
        "searchPlaceholder": "Ej: 123 o 50000",
        "summary": "Mostrando {from}-{to} de {total}",
        "summaryEmpty": "Sin cotizaciones registradas",
        "pageSummary": "Página {current} de {last}",
        "table": {
            "id": "ID",
            "customer": "Cliente",
            "vehicle": "Vehículo",
            "date": "Fecha",
            "status": "Estado",
            "items": "Items",
            "subtotal": "Subtotal",
            "tax": "IVA",
            "total": "Total",
            "actions": "Acciones"
        },
        "status": {
            "label": "Estado",
            "values": {
                "draft": "Borrador",
                "confirmed": "Confirmada",
                "cancelled": "Anulada"
            }
        },
        "actions": {
            "view": "Ver",
            "edit": "Editar",
            "more": "Más acciones",
            "viewPdf": "Ver PDF",
            "downloadPdf": "Descargar PDF",
            "exportDetailedXls": "Exportar detallado XLS",
            "exportingDetailedXls": "Exportando detallado XLS...",
            "exportXls": "Exportar XLS",
            "exportingXls": "Exportando XLS...",
            "exportError": "No se pudo exportar el archivo XLS.",
            "confirm": "Confirmar",
            "cancel": "Anular",
            "errorTitle": "No se pudo realizar la acción.",
            "confirmSuccess": "Cotización confirmada exitosamente.",
            "cancelSuccess": "Cotización anulada exitosamente."
        },
        "form": {
            "open": "Nueva cotización",
            "title": "Crear cotización",
            "description": "Agrega items a la cotización.",
            "associationSearch": "Cliente o vehículo",
            "associationPlaceholder": "Buscar por cliente, documento o placa...",
            "resultsVehicles": "Vehículos",
            "resultsCustomers": "Clientes",
            "selected": "Asociado",
            "vehicleForCustomer": "Vehículo del cliente",
            "vehicleForCustomerPlaceholder": "Selecciona un vehículo",
            "selectItem": "Seleccionar item",
            "selectItemPlaceholder": "Buscar producto, servicio o paquete...",
            "quantity": "Cantidad",
            "addItem": "Agregar",
            "actions": {
                "removeItem": "Quitar item"
            },
            "itemsTitle": "Items agregados",
            "noItems": "No hay items agregados.",
            "taxLabels": "Impuestos: {labels}",
            "taxLabelsEmpty": "Sin impuestos",
            "cancel": "Cancelar",
            "save": "Crear cotización",
            "saving": "Creando...",
            "errors": {
                "title": "No se pudo crear la cotización.",
                "generic": "Ocurrió un error al crear la cotización.",
                "noVehicle": "Debe seleccionar un vehículo (puede buscar por cliente o placa).",
                "noItems": "Debe agregar al menos un item."
            }
        },
        "errors": {
            "load": "Ocurrió un error al cargar las cotizaciones."
        },
        "empty": {
            "noQuotes": "No hay cotizaciones registradas todavía.",
            "noMatch": "No se encontraron cotizaciones con \"{query}\"."
        },
        "itemTypes": {
            "product": "Producto",
            "service": "Servicio",
            "bundle": "Paquete"
        },
        "detail": {
            "title": "Cotización",
            "createdAt": "Creada el",
            "customer": "Cliente",
            "vehicle": "Vehículo",
            "subtotal": "Subtotal",
            "tax": "Impuestos",
            "total": "Total",
            "itemsTitle": "Items de la cotización",
            "itemsCount": "{count} items",
            "noItems": "Esta cotización no tiene items.",
            "table": {
                "description": "Descripción",
                "type": "Tipo",
                "quantity": "Cantidad",
                "unitPrice": "Precio Unit.",
                "taxRate": "Impuestos",
                "subtotal": "Subtotal",
                "total": "Total"
            }
        },
        "edit": {
            "title": "Editar cotización",
            "description": "Actualiza el vehículo y los items mientras esté en borrador.",
            "save": "Guardar cambios",
            "saving": "Guardando...",
            "actions": {
                "removeItem": "Quitar item"
            },
            "errors": {
                "title": "No se pudo actualizar la cotización.",
                "generic": "Ocurrió un error al actualizar la cotización."
            }
        }
    },
    "catalog": {
        "unit": {
            "label": "Unidad",
            "placeholder": "Selecciona unidad",
            "values": {
                "unit": "Unidad",
                "gram": "Gramo",
                "kilogram": "Kilogramo",
                "meter": "Metro",
                "centimeter": "Centímetro",
                "millimeter": "Milímetro",
                "liter": "Litro",
                "milliliter": "Mililitro"
            }
        }
    },
    "customers": {
        "picker": {
            "placeholder": "Selecciona un cliente",
            "searchPlaceholder": "Buscar cliente...",
            "error": "No se pudieron cargar los clientes."
        },
        "index": {
            "headTitle": "Clientes",
            "title": "Clientes",
            "description": "Administre sus clientes y mantenga su información actualizada.",
            "actions": {
                "export": "Exportar XLS",
                "import": "Importar XLS",
                "create": "Crear cliente",
                "edit": "Editar",
                "delete": "Eliminar",
                "more": "Más acciones"
            },
            "perPage": "Por página",
            "searchLabel": "Buscar",
            "searchPlaceholder": "Buscar por nombre, correo o documento...",
            "summary": "Mostrando {from} a {to} de {total} clientes",
            "summaryEmpty": "No hay clientes",
            "pageSummary": "Página {current} de {last}",
            "table": {
                "fullName": "Nombre completo",
                "email": "Correo electrónico",
                "document": "Documento",
                "phone": "Teléfono",
                "actions": "Acciones"
            },
            "empty": "No se encontraron clientes.",
            "emptyValue": "—",
            "toast": {
                "deleteSuccess": "Cliente eliminado exitosamente",
                "deleteError": "No se pudo eliminar el cliente"
            }
        },
        "import": {
            "title": "Importar Clientes desde Excel",
            "description": "Arrastre un archivo Excel o selecciónelo desde su dispositivo para importar clientes.",
            "fileLabel": "Archivo Excel",
            "templateHelpText": "¿No tiene el archivo en el formato correcto?",
            "templateButtonText": "Descargar plantilla de clientes",
            "note": "El archivo debe contener las columnas: nombre completo, correo, tipo de documento, número de documento y teléfono.",
            "processingText": "Importando clientes...",
            "resultTitleSuccess": "Finalizado",
            "resultTitleWithErrors": "Finalizado con errores",
            "toast": {
                "queued": "Archivo recibido. La importación se procesará en segundo plano.",
                "failedAll": "No se pudo importar ningún registro. {failed} filas con errores.",
                "completedWithErrors": "Importación completada con advertencias. {failed} filas fallaron.",
                "completed": "Finalizado. Cierre la ventana para actualizar el listado."
            },
            "attributes": {
                "fullName": "Nombre completo",
                "email": "Correo electrónico",
                "documentType": "Tipo de documento",
                "documentNumber": "Número de documento",
                "phoneNumber": "Teléfono",
                "street": "Dirección",
                "complement": "Complemento",
                "neighborhood": "Barrio",
                "city": "Ciudad",
                "state": "Departamento",
                "postalCode": "Código postal",
                "country": "País",
                "reference": "Referencias"
            }
        },
        "delete": {
            "title": "¿Eliminar cliente?",
            "description": "Esta acción no se puede deshacer. El cliente será eliminado permanentemente del sistema.",
            "cancel": "Cancelar",
            "confirm": "Eliminar",
            "deleting": "Eliminando..."
        },
        "documentType": {
            "label": "Tipo de documento",
            "values": {
                "CC": "Cédula de Ciudadanía",
                "CE": "Cédula de Extranjería",
                "NIT": "NIT",
                "PP": "Pasaporte",
                "TI": "Tarjeta de Identidad"
            }
        },
        "addressType": {
            "label": "Tipo de dirección",
            "values": {
                "primary": "Principal",
                "billing": "Facturación",
                "shipping": "Envío"
            }
        },
        "form": {
            "fields": {
                "fullName": "Nombre completo",
                "email": "Correo electrónico",
                "phoneNumber": "Teléfono (opcional)",
                "documentType": "Tipo de documento",
                "documentNumber": "Número de documento",
                "observations": "Observaciones"
            },
            "placeholders": {
                "fullName": "Ej: Juan Pérez García",
                "email": "ejemplo{'@'}correo.com",
                "phoneNumber": "Ej: 3001234567",
                "documentType": "Seleccionar tipo",
                "documentNumber": "Ej: 1234567890",
                "observations": "Observaciones internas del cliente"
            },
            "cancel": "Cancelar",
            "saving": "Guardando..."
        },
        "addresses": {
            "title": "Direcciones",
            "subtitle": "Agrega las direcciones asociadas al perfil.",
            "empty": "No hay direcciones registradas.",
            "actions": {
                "add": "Agregar dirección",
                "remove": "Eliminar dirección"
            },
            "fields": {
                "type": "Tipo",
                "isPrimary": "Dirección principal",
                "street": "Dirección",
                "complement": "Complemento",
                "neighborhood": "Barrio",
                "city": "Ciudad",
                "state": "Departamento",
                "postalCode": "Código postal",
                "country": "País",
                "reference": "Referencias"
            },
            "placeholders": {
                "type": "Selecciona tipo",
                "street": "Ej: Calle 12 # 34 - 56",
                "complement": "Ej: Apt 402",
                "neighborhood": "Ej: Laureles",
                "city": "Ej: Medellín",
                "state": "Ej: Antioquia",
                "postalCode": "Ej: 050021",
                "country": "Ej: Colombia",
                "reference": "Ej: Frente al parque principal"
            }
        },
        "create": {
            "headTitle": "Crear Cliente",
            "title": "Crear cliente",
            "description": "Complete la información del nuevo cliente.",
            "actions": {
                "back": "← Volver a clientes",
                "submit": "Crear cliente"
            },
            "toast": {
                "success": "Cliente creado exitosamente",
                "error": "No se pudo crear el cliente"
            }
        },
        "edit": {
            "headTitle": "Editar Cliente",
            "title": "Editar cliente",
            "description": "Actualice la información del cliente.",
            "actions": {
                "back": "← Volver a clientes",
                "submit": "Actualizar cliente"
            },
            "toast": {
                "success": "Cliente actualizado exitosamente",
                "error": "No se pudo actualizar el cliente"
            }
        }
    },
    "dayOfWeek": {
        "label": "Día de la semana",
        "values": [
            "Lunes",
            "Martes",
            "Miércoles",
            "Jueves",
            "Viernes",
            "Sábado",
            "Domingo"
        ]
    },
    "technicians": {
        "index": {
            "headTitle": "Técnicos",
            "title": "Técnicos",
            "description": "Gestiona los técnicos y su disponibilidad.",
            "actions": {
                "create": "Crear técnico"
            }
        },
        "filters": {
            "searchLabel": "Buscar técnico",
            "searchPlaceholder": "Nombre, correo o teléfono",
            "total": "Total"
        },
        "form": {
            "fields": {
                "name": "Nombre",
                "email": "Correo",
                "phone": "Teléfono",
                "active": "Activo"
            },
            "placeholders": {
                "name": "Ej: Juan Pérez",
                "email": "correo{'@'}ejemplo.com",
                "phone": "3001234567"
            },
            "actions": {
                "cancel": "Cancelar",
                "save": "Guardar",
                "saving": "Guardando..."
            },
            "errors": {
                "nameRequired": "El nombre es obligatorio."
            }
        },
        "create": {
            "title": "Nuevo técnico",
            "description": "Completa la información del técnico."
        },
        "edit": {
            "title": "Editar técnico",
            "description": "Actualiza la información del técnico."
        },
        "table": {
            "name": "Nombre",
            "email": "Correo",
            "phone": "Teléfono",
            "status": "Estado",
            "availability": "Disponibilidad",
            "actions": "Acciones",
            "actionsEdit": "Editar",
            "actionsDisable": "Desactivar",
            "actionsEnable": "Activar",
            "actionsAvailability": "Disponibilidad"
        },
        "status": {
            "active": "Activo",
            "inactive": "Inactivo"
        },
        "availability": {
            "configured": "Configurada",
            "unconfigured": "Sin configurar",
            "headTitle": "{name} — Disponibilidad",
            "nonWorkingDay": "El CDA no labora este día",
            "banner": {
                "noAvailability": "Este técnico no tiene disponibilidad configurada y no aporta cupos de atención."
            },
            "section": {
                "title": "Disponibilidad semanal"
            },
            "table": {
                "day": "Día",
                "available": "Disponible",
                "startTime": "Hora inicio",
                "endTime": "Hora fin",
                "cdaSchedule": "Horario CDA"
            },
            "cdaClosed": "El CDA no labora este día.",
            "cdaLabel": "CDA:",
            "actions": {
                "save": "Guardar disponibilidad",
                "saving": "Guardando..."
            },
            "errors": {
                "validation": "Revise los campos y vuelva a intentar.",
                "save": "No se pudo guardar la disponibilidad.",
                "connection": "Error de conexión al guardar la disponibilidad."
            },
            "success": {
                "save": "Disponibilidad actualizada correctamente."
            }
        },
        "blocks": {
            "section": {
                "title": "Excepciones \/ Bloqueos"
            },
            "actions": {
                "add": "Agregar bloqueo",
                "delete": "Eliminar",
                "cancel": "Cancelar",
                "save": "Guardar",
                "saving": "Guardando..."
            },
            "filters": {
                "future": "Futuros",
                "past": "Pasados",
                "all": "Todos"
            },
            "loading": "Cargando bloqueos...",
            "table": {
                "period": "Periodo",
                "schedule": "Horario",
                "reason": "Motivo",
                "actions": "Acciones"
            },
            "empty": "No hay bloqueos registrados para este técnico.",
            "emptyReason": "—",
            "emptyReasonText": "Sin motivo",
            "fullDay": "Día completo",
            "dialogs": {
                "create": {
                    "title": "Agregar bloqueo",
                    "description": "Registre un periodo en el que el técnico no estará disponible."
                }
            },
            "conflicts": {
                "title": "No se puede crear el bloqueo: existen {count} cita(s) agendadas.",
                "item": "{date} a las {time}",
                "hint": "Cancele o reagende las citas antes de registrar el bloqueo."
            },
            "overlap": {
                "title": "El bloqueo se solapa con otro existente."
            },
            "form": {
                "startDate": "Fecha inicio",
                "endDate": "Fecha fin",
                "fullDay": "Día completo",
                "startTime": "Hora inicio",
                "endTime": "Hora fin",
                "reasonLabel": "Motivo (opcional)",
                "reasonPlaceholder": "Ej: Vacaciones, cita médica..."
            },
            "errors": {
                "load": "No se pudieron cargar los bloqueos.",
                "connection": "Error de conexión al cargar los bloqueos.",
                "validation": "Revise los campos del bloqueo.",
                "create": "No se pudo crear el bloqueo.",
                "createConnection": "Error de conexión al crear el bloqueo.",
                "delete": "No se pudo eliminar el bloqueo.",
                "deleteConnection": "Error de conexión al eliminar el bloqueo."
            },
            "success": {
                "create": "Bloqueo creado correctamente.",
                "delete": "Bloqueo eliminado correctamente."
            },
            "delete": {
                "title": "¿Eliminar bloqueo?",
                "description": "Se eliminará el bloqueo del {period}. Esta acción no se puede deshacer.",
                "cancel": "Cancelar",
                "confirm": "Eliminar",
                "deleting": "Eliminando..."
            }
        },
        "errors": {
            "title": "Ocurrió un error.",
            "create": "No se pudo crear el técnico. Intente nuevamente.",
            "update": "No se pudo actualizar el técnico. Intente nuevamente.",
            "toggleStatus": "No se pudo actualizar el estado del técnico."
        },
        "common": {
            "empty": "No disponible",
            "noEmail": "Sin correo",
            "noPhone": "Sin teléfono"
        },
        "appointment": {
            "status": {
                "label": "Estado",
                "values": {
                    "pending": "Pendiente",
                    "confirmed": "Confirmada",
                    "cancelled": "Cancelada"
                }
            }
        }
    },
    "agenda": {
        "headTitle": "Agenda",
        "title": "Agenda",
        "description": "Gestiona la agenda semanal de citas.",
        "actions": {
            "previousWeek": "Semana anterior",
            "nextWeek": "Semana siguiente",
            "refresh": "Actualizar",
            "new": "Nueva cita"
        },
        "filters": {
            "title": "Filtros",
            "technicianLabel": "Técnico",
            "technicianPlaceholder": "Selecciona un técnico",
            "technicianAll": "Todos los técnicos",
            "note": "¿Necesitas configurar la disponibilidad?",
            "noteLinkPrefix": "Visita ",
            "noteLinkLabel": "configuración de agenda",
            "noteLinkSuffix": " para ajustar los horarios."
        },
        "status": {
            "available": "Disponible",
            "unavailable": "Sin cupo"
        },
        "labels": {
            "appointment": "Cita #{id}"
        },
        "empty": {
            "noSlots": "No hay cupos disponibles para esta fecha."
        },
        "dialogs": {
            "create": {
                "title": "Nueva cita",
                "description": "Registre una cita para el horario seleccionado.",
                "slotLabel": "Horario seleccionado",
                "technicianLabel": "Técnico",
                "technicianPlaceholder": "Selecciona un técnico",
                "technicianNone": "Sin técnico asignado",
                "observationLabel": "Observaciones del cliente",
                "observationPlaceholder": "Escribe una observación si aplica",
                "close": "Cerrar",
                "submit": "Crear cita"
            },
            "details": {
                "title": "Detalle de cita",
                "description": "Administre la cita seleccionada.",
                "reassignLabel": "Reasignar técnico",
                "reassignPlaceholder": "Selecciona un técnico",
                "requestObservationLabel": "Observación del cliente",
                "technicianObservationLabel": "Observación del técnico",
                "adminObservationLabel": "Observación administrativa",
                "adminObservationPlaceholder": "Agrega una observación para confirmar la cita",
                "shareCustomerObservationLabel": "Compartir observación del cliente",
                "shareCustomerObservationHelp": "Permite que el técnico vea la observación del cliente.",
                "close": "Cerrar",
                "reschedule": "Reagendar",
                "confirm": "Confirmar",
                "reassign": "Reasignar",
                "cancel": "Cancelar cita"
            },
            "reschedule": {
                "title": "Reagendar cita",
                "description": "Seleccione una nueva franja disponible.",
                "slotLabel": "Nueva franja",
                "slotPlaceholder": "Selecciona una franja",
                "close": "Cerrar",
                "save": "Guardar cambios"
            }
        },
        "errors": {
            "loadAvailability": "No se pudo cargar la disponibilidad.",
            "loadAvailabilityConnection": "Error de conexión al cargar la disponibilidad.",
            "loadAppointments": "No se pudieron cargar las citas.",
            "loadAppointmentsConnection": "Error de conexión al cargar las citas.",
            "validation": "Revise los datos e intente nuevamente.",
            "create": "No se pudo crear la cita.",
            "createConnection": "Error de conexión al crear la cita.",
            "cancel": "No se pudo cancelar la cita.",
            "cancelConnection": "Error de conexión al cancelar la cita.",
            "confirm": "No se pudo confirmar la cita.",
            "confirmConnection": "Error de conexión al confirmar la cita.",
            "reassign": "No se pudo reasignar la cita.",
            "reassignConnection": "Error de conexión al reasignar la cita.",
            "loadRescheduleAvailability": "No se pudo cargar la disponibilidad para reagendar.",
            "reschedule": "No se pudo reagendar la cita.",
            "rescheduleConnection": "Error de conexión al reagendar la cita.",
            "selectTechnician": "Seleccione un técnico."
        },
        "success": {
            "create": "Cita creada correctamente.",
            "cancel": "Cita cancelada correctamente.",
            "confirm": "Cita confirmada correctamente.",
            "reassign": "Cita reasignada correctamente.",
            "reschedule": "Cita reagendada correctamente."
        }
    },
    "schedule": {
        "actions": {
            "saveConfiguration": "Guardar configuración",
            "saving": "Guardando..."
        },
        "overrides": {
            "actions": {
                "create": "Agregar feriado\/cierre",
                "delete": "Eliminar",
                "more": "Más acciones"
            }
        }
    },
    "brand": {
        "logoAlt": "Logotipo CDA San Jorge",
        "name": "CDA San Jorge",
        "legalName": "Del San Jorge S.A.S"
    },
    "auth": {
        "login": {
            "headTitle": "Iniciar sesión",
            "title": "Inicia sesión en tu cuenta",
            "description": "Ingresa tu correo y contraseña para continuar.",
            "emailLabel": "Correo electrónico",
            "emailPlaceholder": "correo{'@'}ejemplo.com",
            "passwordLabel": "Contraseña",
            "passwordPlaceholder": "Contraseña",
            "forgotPassword": "¿Olvidaste tu contraseña?",
            "remember": "Recordarme",
            "submit": "Iniciar sesión"
        }
    },
    "settings": {
        "appearance": {
            "breadcrumb": "Apariencia",
            "title": "Apariencia",
            "description": "Actualiza la apariencia de tu cuenta.",
            "currencyFormat": {
                "title": "Formato de moneda",
                "description": "Define cómo se muestran los valores monetarios.",
                "locale": "Formato regional",
                "display": "Mostrar moneda",
                "decimals": "Decimales",
                "preview": "Vista previa",
                "locales": {
                    "esCO": "Colombia (es-CO)",
                    "enUS": "Estados Unidos (en-US)",
                    "esES": "España (es-ES)"
                },
                "displayOptions": {
                    "symbol": "Símbolo",
                    "code": "Código ISO"
                },
                "decimalsOptions": {
                    "zero": "0 decimales",
                    "two": "2 decimales",
                    "four": "4 decimales"
                }
            },
            "options": {
                "light": "Claro",
                "dark": "Oscuro",
                "system": "Sistema"
            },
            "navigationGrouping": {
                "title": "Agrupación del menú",
                "description": "Elige cómo se organiza la navegación principal.",
                "operational": "Operacional",
                "contractual": "Contractual"
            }
        },
        "twoFactor": {
            "headTitle": "Autenticación de dos factores",
            "breadcrumbsTitle": "Autenticación de dos factores",
            "srTitle": "Configuración de autenticación de dos factores",
            "headingTitle": "Autenticación de dos factores",
            "headingDescription": "Gestiona la configuración de autenticación de dos factores.",
            "status": {
                "enabled": "Habilitado",
                "disabled": "Deshabilitado"
            },
            "description": {
                "disabled": "Al habilitar la autenticación de dos factores, se le solicitará un PIN seguro durante el inicio de sesión. Este PIN puede obtenerse desde una aplicación compatible con TOTP en su teléfono.",
                "enabled": "Con la autenticación de dos factores habilitada, se le solicitará un PIN seguro y aleatorio durante el inicio de sesión, que puede obtener desde la aplicación compatible con TOTP en su teléfono."
            },
            "actions": {
                "continueSetup": "Continuar configuración",
                "enable": "Habilitar 2FA",
                "disable": "Deshabilitar 2FA"
            }
        }
    },
    "publicLanding": {
        "headTitle": "Inicio",
        "badge": "Landing en progreso",
        "title": "Agenda pública de citas",
        "description": "Consulta fechas y franjas disponibles para reservar tu cita. Este acceso está disponible para visitantes sin autenticación.",
        "upcoming": "Próximamente: flujo completo de reserva desde esta landing.",
        "actions": {
            "dashboard": "Dashboard",
            "login": "Iniciar sesión",
            "register": "Registrarse",
            "checkAvailability": "Consultar disponibilidad"
        },
        "i18n": {
            "showTranslations": "Mostrar traducciones",
            "showKeys": "Mostrar claves i18n"
        }
    },
    "publicAvailability": {
        "headTitle": "Disponibilidad pública",
        "heading": {
            "title": "Consulta de disponibilidad",
            "description": "Revise fechas y franjas disponibles para agendar su cita."
        },
        "i18n": {
            "showTranslations": "Mostrar traducciones",
            "showKeys": "Mostrar claves i18n"
        },
        "empty": {
            "notConfigured": {
                "title": "Calendario no configurado",
                "description": "El calendario del CDA no está configurado. Configure los días y horarios de atención antes de consultar disponibilidad.",
                "cta": "Ir a configuración del CDA"
            },
            "noTechnicians": {
                "title": "Sin técnicos disponibles",
                "description": "No hay técnicos con disponibilidad configurada. Configure la disponibilidad de al menos un técnico.",
                "cta": "Ir a técnicos"
            }
        },
        "controls": {
            "previousMonth": "Mes anterior",
            "nextMonth": "Mes siguiente"
        },
        "filters": {
            "technician": "Técnico (opcional)",
            "allTechnicians": "Todos los técnicos"
        },
        "weekdays": {
            "sun": "Dom",
            "mon": "Lun",
            "tue": "Mar",
            "wed": "Mié",
            "thu": "Jue",
            "fri": "Vie",
            "sat": "Sáb"
        },
        "availabilityLabels": {
            "unavailable": "Sin cupo",
            "low": "Pocos cupos",
            "available": "Disponible"
        },
        "slots": {
            "summary": "{date} — {count} franjas disponibles",
            "selectDateHint": "Seleccione un día para ver sus franjas.",
            "noSlots": "No hay franjas disponibles para esta fecha.",
            "selected": "Seleccionada",
            "select": "Seleccionar",
            "unavailable": "No disponible"
        },
        "messages": {
            "slotSelected": "Franja seleccionada. Continuaremos con el formulario de reserva."
        },
        "errors": {
            "loadMonth": "No fue posible cargar la disponibilidad del mes.",
            "connection": "Error de conexión al consultar la disponibilidad."
        }
    }
} as const;
