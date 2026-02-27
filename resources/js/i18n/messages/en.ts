/* Generated file. Do not edit manually. */
export default {
    "common": {
        "search": "Search",
        "clear": "Clear",
        "close": "Close",
        "retry": "Retry",
        "loading": "Loading...",
        "previous": "Previous",
        "next": "Next",
        "perPage": "Results per page",
        "noResults": "No results found.",
        "notAvailable": "Not available"
    },
    "sidebar": {
        "grouping": {
            "label": "Navigation grouping",
            "operational": "Operational",
            "contractual": "Contractual"
        },
        "groups": {
            "platform": "Platform",
            "operations": "Operations",
            "services": "Services",
            "certification": "Certification",
            "administration": "Administration",
            "configuration": "Configuration",
            "contractedModules": "Contracted modules",
            "technicalMechanicalQuotation": "Technical-mechanical quotation",
            "schedule": "Schedule",
            "digitalCertificates": "Digital certificates",
            "customerService": "Customer service",
            "other": "Other"
        }
    },
    "iam": {
        "permissions": {
            "headTitle": "IAM · Permissions",
            "title": "Permission catalog",
            "description": "Read-only catalog managed by code and seeder.",
            "note": "Permissions are not created via the UI. Changes only in database\/seeders\/IamSeeder.php.",
            "actions": {
                "assignToProfiles": "Assign permissions in profiles"
            },
            "table": {
                "title": "Permissions",
                "name": "Name",
                "module": "Module",
                "action": "Action",
                "description": "Description"
            },
            "loading": "Loading...",
            "errors": {
                "load": "Unable to load permissions.",
                "connection": "Connection error while loading permissions."
            }
        },
        "skills": {
            "headTitle": "IAM · Skills",
            "title": "Skill catalog",
            "description": "Read-only technical catalog, versioned in code.",
            "note": "Skills are not created via the UI. Changes happen via seed\/migration per the operational catalog.",
            "actions": {
                "assignToProfiles": "Assign skills in profiles"
            },
            "table": {
                "title": "Skills",
                "name": "Name",
                "slug": "Slug",
                "description": "Description",
                "status": "Status"
            },
            "status": {
                "active": "Active",
                "inactive": "Inactive"
            },
            "loading": "Loading...",
            "errors": {
                "load": "Unable to load skills.",
                "connection": "Connection error while loading skills."
            }
        },
        "profiles": {
            "headTitle": "IAM · Profiles",
            "title": "Profiles and access matrix",
            "description": "A capability combines required and optional permissions. Required ones determine whether the capability is active.",
            "note": "Management is done by capabilities; there is no independent atomic list to edit.",
            "loading": "Loading...",
            "actions": {
                "goToUsers": "Go to users",
                "create": "Create",
                "createAndAssign": "Create and assign capabilities",
                "update": "Update",
                "edit": "Edit",
                "delete": "Delete",
                "assignCapabilities": "Assign capabilities",
                "cancel": "Cancel",
                "saving": "Saving..."
            },
            "form": {
                "createTitle": "Create profile",
                "editTitle": "Edit profile",
                "name": "Name",
                "slug": "Slug",
                "slugPlaceholder": "operator",
                "description": "Description",
                "active": "Active profile",
                "technicianProfile": "Technician profile (creates 1:1 link with technicians)"
            },
            "skills": {
                "title": "Base skills"
            },
            "table": {
                "title": "Existing profiles",
                "profile": "Profile",
                "users": "Users",
                "permissions": "Permissions",
                "skills": "Skills",
                "actions": "Actions"
            },
            "errors": {
                "load": "Unable to load profiles.",
                "save": "Unable to save the profile.",
                "skills": "Unable to update the profile skills.",
                "connection": "Connection error while saving the profile.",
                "delete": "Unable to delete the profile.",
                "deleteConnection": "Connection error while deleting the profile."
            },
            "toast": {
                "created": "Profile created.",
                "updated": "Profile updated.",
                "deleted": "Profile deleted."
            },
            "capabilities": {
                "headTitle": "IAM · Profile capabilities",
                "title": "Profile capabilities",
                "description": "Manage profile permissions by capability.",
                "actions": {
                    "back": "Back to profiles",
                    "save": "Save capabilities",
                    "saving": "Saving..."
                },
                "errors": {
                    "save": "Unable to save profile capabilities.",
                    "saveConnection": "Connection error while saving profile capabilities."
                },
                "toast": {
                    "saved": "Profile capabilities updated."
                }
            }
        },
        "capabilities": {
            "title": "Capabilities",
            "showPermissions": "Show permissions",
            "hidePermissions": "Hide permissions",
            "permissionsTitle": "Permissions",
            "actions": {
                "cancel": "Cancel"
            },
            "subject": {
                "user": "Configuring direct permissions for {name}.",
                "profile": "Configuring permissions for profile {name}."
            },
            "breakage": {
                "title": "Capability warning",
                "description": "This change will deactivate the following capabilities because required permissions would be missing:",
                "confirm": "Continue"
            },
            "warnings": {
                "title": "Capability catalog warnings",
                "description": "Some permissions defined in configuration are missing from the database."
            }
        },
        "users": {
            "headTitle": "IAM · Users",
            "title": "User management",
            "description": "Manage users, single profile, status, and direct skills.",
            "loading": "Loading...",
            "form": {
                "createTitle": "Create user",
                "editTitle": "Edit user",
                "description": "Complete access data, profile, and direct skills. When created, a temporary password will be sent to the registered email.",
                "fields": {
                    "username": "Username",
                    "fullName": "Name",
                    "email": "Email",
                    "profile": "Profile"
                },
                "active": "Active user",
                "directSkills": "Direct skills",
                "checkingAvailability": "Checking availability...",
                "checkingMatches": "Searching for matches...",
                "nameMatches": "Possible name matches",
                "emailMatches": "Possible email matches",
                "link": "Link",
                "unlink": "Unlink",
                "linkedTo": "Linked to:",
                "noEmail": "No email"
            },
            "profile": {
                "none": "No profile",
                "search": "Search profile",
                "empty": "No profiles available."
            },
            "candidate": {
                "and": "and"
            },
            "validation": {
                "usernameRequired": "Username is required.",
                "fullNameRequired": "Name is required.",
                "emailRequired": "Email is required.",
                "emailInvalid": "Enter a valid email.",
                "valueInUse": "This value is already in use."
            },
            "actions": {
                "create": "Create user",
                "createAndAssign": "Create and assign permissions",
                "saving": "Saving...",
                "cancel": "Cancel",
                "edit": "Edit",
                "saveChanges": "Save changes",
                "assignCapabilities": "Assign capabilities",
                "activate": "Activate",
                "deactivate": "Deactivate",
                "export": "Export XLS",
                "import": "Import XLS",
                "selectSaveAction": "Select save action"
            },
            "search": {
                "label": "Search users",
                "placeholder": "Username, name, or email",
                "submit": "Search",
                "clear": "Clear search"
            },
            "pagination": {
                "perPage": "Records",
                "total": "Total"
            },
            "table": {
                "username": "Username",
                "fullName": "Full name",
                "email": "Email",
                "profile": "Profile",
                "status": "Status",
                "actions": "Actions",
                "noFullName": "No full name"
            },
            "status": {
                "active": "Active",
                "inactive": "Inactive"
            },
            "empty": "No users for the selected filters.",
            "errors": {
                "load": "Unable to load users.",
                "connection": "Connection error while fetching users.",
                "save": "Unable to save user.",
                "saveConnection": "Connection error while saving user.",
                "validation": "Validation errors.",
                "sessionExpired": "Your session expired. Reload the page to continue.",
                "statusUpdate": "Unable to update the status.",
                "statusConnection": "Connection error while updating status."
            },
            "toast": {
                "created": "User created.",
                "updated": "User updated.",
                "statusUpdated": "User status updated."
            },
            "capabilities": {
                "headTitle": "IAM · User permissions",
                "title": "User direct permissions",
                "description": "Assign direct permissions by capability. This does not replace the user profile.",
                "actions": {
                    "back": "Back to users",
                    "save": "Save permissions",
                    "saving": "Saving..."
                },
                "errors": {
                    "save": "Unable to save user permissions.",
                    "saveConnection": "Connection error while saving user permissions."
                },
                "toast": {
                    "saved": "User permissions updated."
                }
            }
        }
    },
    "products": {
        "title": "Products",
        "subtitle": "Manage the products available for quotations.",
        "listTitle": "Product list",
        "searchLabel": "Search by name",
        "searchPlaceholder": "e.g. Technical inspection",
        "summary": "Showing {from}-{to} of {total}",
        "summaryEmpty": "No products registered",
        "pageSummary": "Page {current} of {last}",
        "table": {
            "name": "Product",
            "description": "Description",
            "price": "Price",
            "status": "Status",
            "actions": "Actions"
        },
        "form": {
            "open": "Create product",
            "title": "New product",
            "description": "Fill in the basic information to register the product.",
            "fields": {
                "name": "Name",
                "description": "Description",
                "observations": "Observations",
                "cost": "Cost",
                "price": "Price",
                "currency": "Currency",
                "active": "Active"
            },
            "placeholders": {
                "name": "e.g. Vehicle technical inspection",
                "description": "Short product details",
                "observations": "Internal product observations",
                "cost": "0.00",
                "price": "0.00",
                "currency": "COP"
            },
            "cancel": "Cancel",
            "save": "Save",
            "saving": "Saving...",
            "errors": {
                "title": "Unable to create product.",
                "generic": "There was an error creating the product.",
                "nameRequired": "Name is required.",
                "priceRequired": "Price is required."
            }
        },
        "edit": {
            "action": "Edit",
            "title": "Edit product",
            "description": "Update the selected product information.",
            "save": "Save changes",
            "saving": "Saving...",
            "errors": {
                "title": "Unable to update product.",
                "generic": "There was an error updating the product."
            }
        },
        "actions": {
            "enable": "Enable",
            "disable": "Disable",
            "more": "More actions",
            "moreSoon": "More actions coming soon",
            "export": "Export XLS",
            "exporting": "Exporting...",
            "errorTitle": "Unable to update status.",
            "error": "There was an error updating the status."
        },
        "status": {
            "active": "Active",
            "inactive": "Inactive",
            "ariaActive": "Active product",
            "ariaInactive": "Inactive product"
        },
        "errors": {
            "load": "There was an error loading products."
        },
        "export": {
            "errors": {
                "generic": "There was an error exporting products."
            }
        },
        "empty": {
            "noProducts": "No products registered yet.",
            "noMatch": "No products found for “{query}”."
        }
    },
    "services": {
        "title": "Services",
        "subtitle": "Manage the services available for quotations.",
        "listTitle": "Service list",
        "searchLabel": "Search by name",
        "searchPlaceholder": "e.g. Electrical diagnostics",
        "summary": "Showing {from}-{to} of {total}",
        "summaryEmpty": "No services registered",
        "pageSummary": "Page {current} of {last}",
        "table": {
            "name": "Service",
            "description": "Description",
            "price": "Price",
            "status": "Status",
            "actions": "Actions"
        },
        "form": {
            "open": "Create service",
            "title": "New service",
            "description": "Complete the basic information to register the service.",
            "fields": {
                "name": "Name",
                "description": "Description",
                "observations": "Observations",
                "price": "Price",
                "active": "Active"
            },
            "placeholders": {
                "name": "e.g. Electrical diagnostics",
                "description": "Brief service detail",
                "observations": "Internal service observations",
                "price": "0.00"
            },
            "cancel": "Cancel",
            "save": "Save",
            "saving": "Saving...",
            "errors": {
                "title": "Unable to create service.",
                "generic": "There was an error creating the service.",
                "nameRequired": "Name is required.",
                "priceRequired": "Price is required."
            }
        },
        "import": {
            "open": "Import XLS",
            "title": "Import services",
            "description": "Upload an XLS\/XLSX\/ODS file to create or update services in bulk.",
            "recommendation": "We recommend downloading the template first to ensure your data is in the correct format and avoid errors during import.",
            "downloadTemplate": "Download template",
            "fields": {
                "file": "XLS file"
            },
            "helper": "Allowed formats: .xls, .xlsx, .ods. Max 10 MB.",
            "cancel": "Cancel",
            "submit": "Import",
            "processing": "Processing...",
            "progressBatches": "Processing batch {processed} of {total}...",
            "timeout": "The import is taking too long. Try with a smaller file.",
            "summary": "Processed {total} rows: {created} created, {updated} updated, {skipped} duplicates skipped, {failed} failed.",
            "errors": {
                "title": "Unable to import the file.",
                "generic": "There was an error processing the import.",
                "fileRequired": "You must select a file.",
                "fileType": "The file must be .xls, .xlsx, or .ods.",
                "row": "Row",
                "field": "Field",
                "value": "Value",
                "message": "Message",
                "truncated": "Showing {shown} of {total} errors. Fix the first ones and re-import."
            }
        },
        "export": {
            "open": "Export XLS",
            "processing": "Exporting...",
            "progressBatches": "Exporting batch {processed} of {total}...",
            "timeout": "The export took too long. Please try again later.",
            "errors": {
                "title": "Unable to export services.",
                "generic": "There was an error processing the export."
            }
        },
        "edit": {
            "action": "Edit",
            "title": "Edit service",
            "description": "Update the selected service information.",
            "save": "Save changes",
            "saving": "Saving...",
            "errors": {
                "title": "Unable to update service.",
                "generic": "There was an error updating the service."
            }
        },
        "actions": {
            "enable": "Enable",
            "disable": "Disable",
            "more": "More actions",
            "moreSoon": "More actions coming soon",
            "errorTitle": "Unable to update status.",
            "error": "There was an error updating the status."
        },
        "status": {
            "active": "Active",
            "inactive": "Inactive",
            "ariaActive": "Active service",
            "ariaInactive": "Inactive service"
        },
        "errors": {
            "load": "There was an error loading services."
        },
        "empty": {
            "noServices": "No services registered yet.",
            "noMatch": "No services found for “{query}”."
        }
    },
    "taxes": {
        "title": "Taxes",
        "subtitle": "Manage the taxes available for catalogs.",
        "searchLabel": "Search by name or code",
        "searchPlaceholder": "e.g. VAT 19%",
        "table": {
            "name": "Tax",
            "code": "Code",
            "jurisdiction": "Jurisdiction",
            "rate": "Rate (%)",
            "actions": "Actions"
        },
        "actions": {
            "more": "More actions"
        },
        "form": {
            "open": "Create tax",
            "title": "New tax",
            "description": "Fill in the basic information to register the tax.",
            "fields": {
                "name": "Name",
                "code": "Code",
                "jurisdiction": "Jurisdiction",
                "rate": "Rate (%)"
            },
            "placeholders": {
                "name": "e.g. VAT",
                "code": "VAT-19",
                "jurisdiction": "Colombia",
                "rate": "e.g. 19"
            },
            "cancel": "Cancel",
            "save": "Save",
            "saving": "Saving...",
            "errors": {
                "title": "Unable to create tax.",
                "generic": "There was an error creating the tax.",
                "nameRequired": "Name is required.",
                "codeRequired": "Code is required.",
                "jurisdictionRequired": "Jurisdiction is required.",
                "rateRequired": "Rate is required.",
                "rateInvalid": "Rate must be numeric."
            },
            "help": {
                "codeDescription": "The code is used as the internal identifier for the tax.",
                "codeAuto": "Auto-generated code: {code}.",
                "codeAutoPlaceholder": "It will be generated on save.",
                "jurisdictionReadonly": "For now, the jurisdiction is fixed to Colombia."
            },
            "defaults": {
                "jurisdiction": "Colombia"
            }
        },
        "edit": {
            "action": "Edit",
            "title": "Edit tax",
            "description": "Update the selected tax information.",
            "save": "Save changes",
            "saving": "Saving...",
            "errors": {
                "title": "Unable to update tax.",
                "generic": "There was an error updating the tax."
            }
        },
        "delete": {
            "action": "Delete",
            "confirm": "Delete tax \"{name}\"?",
            "errors": {
                "generic": "Unable to delete the tax."
            }
        },
        "toast": {
            "created": "Tax created.",
            "updated": "Tax updated.",
            "deleted": "Tax deleted."
        },
        "errors": {
            "load": "There was an error loading taxes."
        },
        "empty": {
            "noTaxes": "No taxes registered yet.",
            "noMatch": "No taxes found for “{query}”."
        }
    },
    "catalogItemTaxes": {
        "title": "Taxes",
        "subtitle": "Associate taxes to the item and define validity.",
        "fields": {
            "tax": "Tax",
            "rate": "Rate (%)",
            "startAt": "Start",
            "endAt": "End"
        },
        "placeholders": {
            "tax": "Select tax",
            "rate": "e.g. 19"
        },
        "actions": {
            "addTax": "Add tax",
            "removeTax": "Remove tax",
            "setRange": "Set range",
            "clearEnd": "No end date",
            "addRange": "Add range",
            "saveRange": "Save range"
        },
        "range": {
            "todayOnwards": "From today onwards",
            "fromOnwards": "{date} onwards",
            "fromTo": "{start} to {end}"
        },
        "empty": "No taxes associated.",
        "emptyUnavailable": "No taxes available to associate."
    },
    "vehicles": {
        "title": "Vehicles",
        "subtitle": "Register and update key data for each vehicle.",
        "listTitle": "Vehicle list",
        "searchLabel": "Search by plate, make, or model",
        "searchPlaceholder": "e.g. ABC-123 or Mazda",
        "summary": "Showing {from}-{to} of {total}",
        "summaryEmpty": "No vehicles registered",
        "pageSummary": "Page {current} of {last}",
        "table": {
            "plate": "Plate",
            "vehicle": "Vehicle",
            "year": "Year",
            "type": "Type",
            "status": "Status",
            "actions": "Actions",
            "noVin": "No VIN registered",
            "noType": "No type"
        },
        "form": {
            "open": "Register vehicle",
            "title": "New vehicle",
            "description": "Complete the main details to identify the vehicle.",
            "sections": {
                "core": "Identification",
                "specs": "Specifications",
                "observations": "Observations",
                "status": "Status"
            },
            "fields": {
                "customer": "Customer",
                "plate": "Plate",
                "vin": "VIN",
                "make": "Make",
                "model": "Model",
                "year": "Year",
                "type": "Vehicle type",
                "color": "Color",
                "fuel": "Fuel",
                "transmission": "Transmission",
                "mileage": "Mileage",
                "observations": "Observations",
                "active": "Available"
            },
            "placeholders": {
                "customer": "Select a customer",
                "plate": "ABC-123",
                "vin": "Optional VIN",
                "make": "e.g. Mazda",
                "model": "e.g. CX-5",
                "year": "2023",
                "type": "Select a type",
                "color": "e.g. Gray",
                "fuel": "Select fuel",
                "transmission": "Select transmission",
                "mileage": "e.g. 45000",
                "observations": "Internal vehicle observations"
            },
            "help": {
                "vin": "Optional, but recommended for traceability.",
                "active": "Controls whether the vehicle is available."
            },
            "options": {
                "empty": "Not selected",
                "type": {
                    "sedan": "Sedan",
                    "suv": "SUV",
                    "pickup": "Pickup",
                    "van": "Van",
                    "motorcycle": "Motorcycle",
                    "truck": "Truck",
                    "other": "Other"
                },
                "fuel": {
                    "gasoline": "Gasoline",
                    "diesel": "Diesel",
                    "electric": "Electric",
                    "hybrid": "Hybrid",
                    "gas": "Gas",
                    "other": "Other"
                },
                "transmission": {
                    "manual": "Manual",
                    "automatic": "Automatic"
                }
            },
            "cancel": "Cancel",
            "save": "Save",
            "saving": "Saving...",
            "errors": {
                "title": "Unable to register the vehicle.",
                "generic": "There was an error registering the vehicle.",
                "customerRequired": "Customer is required.",
                "plateRequired": "Plate is required.",
                "makeRequired": "Make is required.",
                "modelRequired": "Model is required.",
                "yearRequired": "Year is required."
            }
        },
        "edit": {
            "action": "Edit",
            "title": "Edit vehicle",
            "description": "Update the selected vehicle details.",
            "save": "Save changes",
            "saving": "Saving...",
            "errors": {
                "title": "Unable to update the vehicle.",
                "generic": "There was an error updating the vehicle."
            }
        },
        "actions": {
            "enable": "Enable",
            "disable": "Disable",
            "more": "More actions",
            "moreSoon": "More actions coming soon",
            "errorTitle": "Unable to update status.",
            "error": "There was an error updating the status."
        },
        "status": {
            "active": "Active",
            "inactive": "Inactive",
            "ariaActive": "Active vehicle",
            "ariaInactive": "Inactive vehicle"
        },
        "errors": {
            "load": "There was an error loading vehicles."
        },
        "empty": {
            "noVehicles": "No vehicles registered yet.",
            "noMatch": "No vehicles found for “{query}”."
        }
    },
    "bundles": {
        "title": "Bundles",
        "subtitle": "Manage bundles composed of products and services.",
        "listTitle": "Bundle list",
        "actions": {
            "view": "Details",
            "more": "More actions"
        },
        "form": {
            "open": "Create bundle",
            "title": "New bundle",
            "description": "Complete the basic information to register the bundle.",
            "fields": {
                "name": "Name",
                "description": "Description",
                "observations": "Observations",
                "price": "Price",
                "active": "Active"
            },
            "placeholders": {
                "name": "e.g. Maintenance package",
                "description": "Brief bundle detail",
                "observations": "Internal bundle observations",
                "price": "0.00"
            },
            "cancel": "Cancel",
            "save": "Save",
            "saving": "Saving...",
            "errors": {
                "title": "Unable to create bundle.",
                "generic": "There was an error creating the bundle.",
                "catalogLoad": "Unable to load available items.",
                "nameRequired": "Name is required.",
                "priceRequired": "Price is required."
            },
            "itemTypes": {
                "product": "Product",
                "service": "Service",
                "bundle": "Bundle"
            },
            "items": {
                "title": "Bundle items",
                "search": "Search item",
                "searchPlaceholder": "Search by name or type",
                "item": "Item",
                "quantity": "Quantity",
                "placeholder": "Select an item",
                "add": "Add item",
                "remove": "Remove",
                "empty": "No items have been added to this bundle yet."
            }
        },
        "edit": {
            "action": "Edit",
            "title": "Edit bundle",
            "description": "Update the selected bundle information.",
            "save": "Save changes",
            "saving": "Saving...",
            "errors": {
                "title": "Unable to update bundle.",
                "generic": "There was an error updating the bundle.",
                "load": "Unable to load bundle details."
            }
        },
        "searchLabel": "Search by name",
        "searchPlaceholder": "e.g. Preventive maintenance",
        "summary": "Showing {from}-{to} of {total}",
        "summaryEmpty": "No bundles registered",
        "pageSummary": "Page {current} of {last}",
        "table": {
            "actions": "Details",
            "name": "Bundle",
            "description": "Description",
            "price": "Price",
            "items": "Items",
            "status": "Status"
        },
        "status": {
            "active": "Active",
            "inactive": "Inactive",
            "ariaActive": "Active bundle",
            "ariaInactive": "Inactive bundle"
        },
        "errors": {
            "load": "There was an error loading bundles."
        },
        "empty": {
            "noBundles": "No bundles registered yet.",
            "noMatch": "No bundles found for “{query}”."
        },
        "inline": {
            "expand": "View items",
            "collapse": "Hide items",
            "empty": "This bundle does not have items yet.",
            "errors": {
                "load": "There was an error loading bundle items."
            }
        },
        "detail": {
            "label": "Bundle details",
            "back": "Back to bundles",
            "noDescription": "No description available.",
            "itemsTitle": "Bundle items",
            "itemsSummary": "{total} items in this bundle",
            "empty": "This bundle does not have items yet.",
            "table": {
                "name": "Item",
                "type": "Type",
                "quantity": "Quantity",
                "price": "Price",
                "status": "Status"
            }
        }
    },
    "quotes": {
        "title": "Quotes",
        "subtitle": "Manage system quotes.",
        "listTitle": "Quote list",
        "searchLabel": "Search by ID, total, customer or plate",
        "searchPlaceholder": "e.g. 123 or 50000",
        "summary": "Showing {from}-{to} of {total}",
        "summaryEmpty": "No quotes registered",
        "pageSummary": "Page {current} of {last}",
        "table": {
            "id": "ID",
            "customer": "Customer",
            "vehicle": "Vehicle",
            "date": "Date",
            "status": "Status",
            "items": "Items",
            "subtotal": "Subtotal",
            "tax": "Tax",
            "total": "Total",
            "actions": "Actions"
        },
        "status": {
            "label": "Status",
            "values": {
                "draft": "Draft",
                "confirmed": "Confirmed",
                "cancelled": "Cancelled"
            }
        },
        "actions": {
            "view": "View",
            "edit": "Edit",
            "more": "More actions",
            "viewPdf": "View PDF",
            "downloadPdf": "Download PDF",
            "exportDetailedXls": "Export detailed XLS",
            "exportingDetailedXls": "Exporting detailed XLS...",
            "exportXls": "Export XLS",
            "exportingXls": "Exporting XLS...",
            "exportError": "Unable to export XLS file.",
            "confirm": "Confirm",
            "cancel": "Cancel",
            "errorTitle": "Unable to perform action.",
            "confirmSuccess": "Quote confirmed successfully.",
            "cancelSuccess": "Quote cancelled successfully."
        },
        "form": {
            "open": "New quote",
            "title": "Create quote",
            "description": "Add items to the quote.",
            "associationSearch": "Customer or vehicle",
            "associationPlaceholder": "Search by customer, document or plate...",
            "resultsVehicles": "Vehicles",
            "resultsCustomers": "Customers",
            "selected": "Associated",
            "vehicleForCustomer": "Customer vehicle",
            "vehicleForCustomerPlaceholder": "Select a vehicle",
            "selectItem": "Select item",
            "selectItemPlaceholder": "Search product, service or bundle...",
            "quantity": "Quantity",
            "addItem": "Add",
            "actions": {
                "removeItem": "Remove item"
            },
            "itemsTitle": "Added items",
            "noItems": "No items added.",
            "taxLabels": "Taxes: {labels}",
            "taxLabelsEmpty": "No taxes",
            "cancel": "Cancel",
            "save": "Create quote",
            "saving": "Creating...",
            "errors": {
                "title": "Unable to create quote.",
                "generic": "There was an error creating the quote.",
                "noVehicle": "You must select a vehicle (you can search by customer or plate).",
                "noItems": "You must add at least one item."
            }
        },
        "errors": {
            "load": "There was an error loading quotes."
        },
        "empty": {
            "noQuotes": "No quotes registered yet.",
            "noMatch": "No quotes found for \"{query}\"."
        },
        "itemTypes": {
            "product": "Product",
            "service": "Service",
            "bundle": "Bundle"
        },
        "detail": {
            "title": "Quote",
            "createdAt": "Created on",
            "customer": "Customer",
            "vehicle": "Vehicle",
            "subtotal": "Subtotal",
            "tax": "Taxes",
            "total": "Total",
            "itemsTitle": "Quote items",
            "itemsCount": "{count} items",
            "noItems": "This quote has no items.",
            "table": {
                "description": "Description",
                "type": "Type",
                "quantity": "Quantity",
                "unitPrice": "Unit Price",
                "taxRate": "Taxes",
                "subtotal": "Subtotal",
                "total": "Total"
            }
        },
        "edit": {
            "title": "Edit quote",
            "description": "Update the vehicle and items while the quote is in draft.",
            "save": "Save changes",
            "saving": "Saving...",
            "actions": {
                "removeItem": "Remove item"
            },
            "errors": {
                "title": "Unable to update quote.",
                "generic": "There was an error updating the quote."
            }
        }
    },
    "catalog": {
        "unit": {
            "label": "Unit",
            "placeholder": "Select unit",
            "values": {
                "unit": "Unit",
                "gram": "Gram",
                "kilogram": "Kilogram",
                "meter": "Meter",
                "centimeter": "Centimeter",
                "millimeter": "Millimeter",
                "liter": "Liter",
                "milliliter": "Milliliter"
            }
        }
    },
    "customers": {
        "picker": {
            "placeholder": "Select a customer",
            "searchPlaceholder": "Search customers...",
            "error": "Unable to load customers."
        },
        "index": {
            "headTitle": "Customers",
            "title": "Customers",
            "description": "Manage your customers and keep their information up to date.",
            "actions": {
                "export": "Export XLS",
                "import": "Import XLS",
                "create": "Create customer",
                "edit": "Edit",
                "delete": "Delete",
                "more": "More actions"
            },
            "perPage": "Per page",
            "searchLabel": "Search",
            "searchPlaceholder": "Search by name, email, or document...",
            "summary": "Showing {from} to {to} of {total} customers",
            "summaryEmpty": "No customers",
            "pageSummary": "Page {current} of {last}",
            "table": {
                "fullName": "Full name",
                "email": "Email",
                "document": "Document",
                "phone": "Phone",
                "actions": "Actions"
            },
            "empty": "No customers found.",
            "emptyValue": "—",
            "toast": {
                "deleteSuccess": "Customer deleted successfully",
                "deleteError": "Unable to delete customer"
            }
        },
        "import": {
            "title": "Import Customers from Excel",
            "description": "Drag an Excel file or select it from your device to import customers.",
            "fileLabel": "Excel file",
            "templateHelpText": "Don't have the file in the correct format?",
            "templateButtonText": "Download customer template",
            "note": "The file must contain the columns: full name, email, document type, document number, and phone.",
            "processingText": "Importing customers...",
            "resultTitleSuccess": "Completed",
            "resultTitleWithErrors": "Completed with errors",
            "toast": {
                "queued": "File received. The import will be processed in the background.",
                "failedAll": "No records could be imported. {failed} rows failed.",
                "completedWithErrors": "Import completed with warnings. {failed} rows failed.",
                "completed": "Completed. Close the window to refresh the list."
            },
            "attributes": {
                "fullName": "Full name",
                "email": "Email",
                "documentType": "Document type",
                "documentNumber": "Document number",
                "phoneNumber": "Phone",
                "street": "Street",
                "complement": "Complement",
                "neighborhood": "Neighborhood",
                "city": "City",
                "state": "State",
                "postalCode": "Postal code",
                "country": "Country",
                "reference": "Reference"
            }
        },
        "delete": {
            "title": "Delete customer?",
            "description": "This action cannot be undone. The customer will be permanently removed from the system.",
            "cancel": "Cancel",
            "confirm": "Delete",
            "deleting": "Deleting..."
        },
        "documentType": {
            "label": "Document type",
            "values": {
                "CC": "Citizenship ID",
                "CE": "Foreigner ID",
                "NIT": "NIT",
                "PP": "Passport",
                "TI": "Identity card"
            }
        },
        "addressType": {
            "label": "Address type",
            "values": {
                "primary": "Primary",
                "billing": "Billing",
                "shipping": "Shipping"
            }
        },
        "form": {
            "fields": {
                "fullName": "Full name",
                "email": "Email",
                "phoneNumber": "Phone (optional)",
                "documentType": "Document type",
                "documentNumber": "Document number",
                "observations": "Observations"
            },
            "placeholders": {
                "fullName": "e.g. Juan Perez Garcia",
                "email": "example{'@'}email.com",
                "phoneNumber": "e.g. 3001234567",
                "documentType": "Select type",
                "documentNumber": "e.g. 1234567890",
                "observations": "Internal customer observations"
            },
            "cancel": "Cancel",
            "saving": "Saving..."
        },
        "addresses": {
            "title": "Addresses",
            "subtitle": "Add the addresses associated with the profile.",
            "empty": "No addresses registered.",
            "actions": {
                "add": "Add address",
                "remove": "Remove address"
            },
            "fields": {
                "type": "Type",
                "isPrimary": "Primary address",
                "street": "Street",
                "complement": "Complement",
                "neighborhood": "Neighborhood",
                "city": "City",
                "state": "State",
                "postalCode": "Postal code",
                "country": "Country",
                "reference": "References"
            },
            "placeholders": {
                "type": "Select type",
                "street": "e.g. 12th Street # 34 - 56",
                "complement": "e.g. Apt 402",
                "neighborhood": "e.g. Laureles",
                "city": "e.g. Medellin",
                "state": "e.g. Antioquia",
                "postalCode": "e.g. 050021",
                "country": "e.g. Colombia",
                "reference": "e.g. In front of the main park"
            }
        },
        "create": {
            "headTitle": "Create Customer",
            "title": "Create customer",
            "description": "Complete the new customer information.",
            "actions": {
                "back": "← Back to customers",
                "submit": "Create customer"
            },
            "toast": {
                "success": "Customer created successfully",
                "error": "Unable to create customer"
            }
        },
        "edit": {
            "headTitle": "Edit Customer",
            "title": "Edit customer",
            "description": "Update customer information.",
            "actions": {
                "back": "← Back to customers",
                "submit": "Update customer"
            },
            "toast": {
                "success": "Customer updated successfully",
                "error": "Unable to update customer"
            }
        }
    },
    "dayOfWeek": {
        "label": "Day of week",
        "values": [
            "Monday",
            "Tuesday",
            "Wednesday",
            "Thursday",
            "Friday",
            "Saturday",
            "Sunday"
        ]
    },
    "technicians": {
        "index": {
            "headTitle": "Technicians",
            "title": "Technicians",
            "description": "Manage technicians and their availability.",
            "actions": {
                "create": "Create technician"
            }
        },
        "filters": {
            "searchLabel": "Search technician",
            "searchPlaceholder": "Name, email, or phone",
            "total": "Total"
        },
        "form": {
            "fields": {
                "name": "Name",
                "email": "Email",
                "phone": "Phone",
                "active": "Active"
            },
            "placeholders": {
                "name": "e.g. Jane Doe",
                "email": "email{'@'}example.com",
                "phone": "3001234567"
            },
            "actions": {
                "cancel": "Cancel",
                "save": "Save",
                "saving": "Saving..."
            },
            "errors": {
                "nameRequired": "Name is required."
            }
        },
        "create": {
            "title": "New technician",
            "description": "Complete the technician details."
        },
        "edit": {
            "title": "Edit technician",
            "description": "Update technician information."
        },
        "table": {
            "name": "Name",
            "email": "Email",
            "phone": "Phone",
            "status": "Status",
            "availability": "Availability",
            "actions": "Actions",
            "actionsEdit": "Edit",
            "actionsDisable": "Deactivate",
            "actionsEnable": "Activate",
            "actionsAvailability": "Availability"
        },
        "status": {
            "active": "Active",
            "inactive": "Inactive"
        },
        "availability": {
            "configured": "Configured",
            "unconfigured": "Not configured",
            "headTitle": "{name} — Availability",
            "nonWorkingDay": "The center does not operate on this day",
            "banner": {
                "noAvailability": "This technician has no configured availability and does not contribute appointment slots."
            },
            "section": {
                "title": "Weekly availability"
            },
            "table": {
                "day": "Day",
                "available": "Available",
                "startTime": "Start time",
                "endTime": "End time",
                "cdaSchedule": "Center schedule"
            },
            "cdaClosed": "The center does not operate on this day.",
            "cdaLabel": "Center:",
            "actions": {
                "save": "Save availability",
                "saving": "Saving..."
            },
            "errors": {
                "validation": "Review the fields and try again.",
                "save": "Unable to save availability.",
                "connection": "Connection error while saving availability."
            },
            "success": {
                "save": "Availability updated successfully."
            }
        },
        "blocks": {
            "section": {
                "title": "Exceptions \/ Blocks"
            },
            "actions": {
                "add": "Add block",
                "delete": "Delete",
                "cancel": "Cancel",
                "save": "Save",
                "saving": "Saving..."
            },
            "filters": {
                "future": "Upcoming",
                "past": "Past",
                "all": "All"
            },
            "loading": "Loading blocks...",
            "table": {
                "period": "Period",
                "schedule": "Schedule",
                "reason": "Reason",
                "actions": "Actions"
            },
            "empty": "There are no blocks registered for this technician.",
            "emptyReason": "—",
            "emptyReasonText": "No reason",
            "fullDay": "Full day",
            "dialogs": {
                "create": {
                    "title": "Add block",
                    "description": "Register a period when the technician will be unavailable."
                }
            },
            "conflicts": {
                "title": "The block cannot be created: there are {count} scheduled appointment(s).",
                "item": "{date} at {time}",
                "hint": "Cancel or reschedule the appointments before registering the block."
            },
            "overlap": {
                "title": "The block overlaps with an existing one."
            },
            "form": {
                "startDate": "Start date",
                "endDate": "End date",
                "fullDay": "Full day",
                "startTime": "Start time",
                "endTime": "End time",
                "reasonLabel": "Reason (optional)",
                "reasonPlaceholder": "e.g. Vacation, medical appointment..."
            },
            "errors": {
                "load": "Unable to load blocks.",
                "connection": "Connection error while loading blocks.",
                "validation": "Review the block fields.",
                "create": "Unable to create the block.",
                "createConnection": "Connection error while creating the block.",
                "delete": "Unable to delete the block.",
                "deleteConnection": "Connection error while deleting the block."
            },
            "success": {
                "create": "Block created successfully.",
                "delete": "Block deleted successfully."
            },
            "delete": {
                "title": "Delete block?",
                "description": "The block for {period} will be removed. This action cannot be undone.",
                "cancel": "Cancel",
                "confirm": "Delete",
                "deleting": "Deleting..."
            }
        },
        "errors": {
            "title": "Something went wrong.",
            "create": "Unable to create the technician. Please try again.",
            "update": "Unable to update the technician. Please try again.",
            "toggleStatus": "Unable to update the technician status."
        },
        "common": {
            "empty": "Not available",
            "noEmail": "No email",
            "noPhone": "No phone"
        },
        "appointment": {
            "status": {
                "label": "Status",
                "values": {
                    "pending": "Pending",
                    "confirmed": "Confirmed",
                    "cancelled": "Cancelled"
                }
            }
        }
    },
    "agenda": {
        "headTitle": "Agenda",
        "title": "Agenda",
        "description": "Manage the weekly appointment agenda.",
        "actions": {
            "previousWeek": "Previous week",
            "nextWeek": "Next week",
            "refresh": "Refresh",
            "new": "New appointment"
        },
        "filters": {
            "title": "Filters",
            "technicianLabel": "Technician",
            "technicianPlaceholder": "Select a technician",
            "technicianAll": "All technicians",
            "note": "Need to configure availability?",
            "noteLinkPrefix": "Visit ",
            "noteLinkLabel": "schedule settings",
            "noteLinkSuffix": " to adjust hours."
        },
        "status": {
            "available": "Available",
            "unavailable": "Unavailable"
        },
        "labels": {
            "appointment": "Appointment #{id}"
        },
        "empty": {
            "noSlots": "No slots available for this date."
        },
        "dialogs": {
            "create": {
                "title": "New appointment",
                "description": "Register an appointment for the selected slot.",
                "slotLabel": "Selected slot",
                "technicianLabel": "Technician",
                "technicianPlaceholder": "Select a technician",
                "technicianNone": "No technician assigned",
                "observationLabel": "Customer observations",
                "observationPlaceholder": "Add an observation if needed",
                "close": "Close",
                "submit": "Create appointment"
            },
            "details": {
                "title": "Appointment details",
                "description": "Manage the selected appointment.",
                "reassignLabel": "Reassign technician",
                "reassignPlaceholder": "Select a technician",
                "requestObservationLabel": "Customer observation",
                "technicianObservationLabel": "Technician observation",
                "adminObservationLabel": "Administrative observation",
                "adminObservationPlaceholder": "Add an observation to confirm the appointment",
                "shareCustomerObservationLabel": "Share customer observation",
                "shareCustomerObservationHelp": "Allow the technician to see the customer observation.",
                "close": "Close",
                "reschedule": "Reschedule",
                "confirm": "Confirm",
                "reassign": "Reassign",
                "cancel": "Cancel appointment"
            },
            "reschedule": {
                "title": "Reschedule appointment",
                "description": "Select a new available slot.",
                "slotLabel": "New slot",
                "slotPlaceholder": "Select a slot",
                "close": "Close",
                "save": "Save changes"
            }
        },
        "errors": {
            "loadAvailability": "Unable to load availability.",
            "loadAvailabilityConnection": "Connection error while loading availability.",
            "loadAppointments": "Unable to load appointments.",
            "loadAppointmentsConnection": "Connection error while loading appointments.",
            "validation": "Review the data and try again.",
            "create": "Unable to create the appointment.",
            "createConnection": "Connection error while creating the appointment.",
            "cancel": "Unable to cancel the appointment.",
            "cancelConnection": "Connection error while canceling the appointment.",
            "confirm": "Unable to confirm the appointment.",
            "confirmConnection": "Connection error while confirming the appointment.",
            "reassign": "Unable to reassign the appointment.",
            "reassignConnection": "Connection error while reassigning the appointment.",
            "loadRescheduleAvailability": "Unable to load availability to reschedule.",
            "reschedule": "Unable to reschedule the appointment.",
            "rescheduleConnection": "Connection error while rescheduling the appointment.",
            "selectTechnician": "Select a technician."
        },
        "success": {
            "create": "Appointment created successfully.",
            "cancel": "Appointment canceled successfully.",
            "confirm": "Appointment confirmed successfully.",
            "reassign": "Appointment reassigned successfully.",
            "reschedule": "Appointment rescheduled successfully."
        }
    },
    "schedule": {
        "actions": {
            "saveConfiguration": "Save configuration",
            "saving": "Saving..."
        },
        "overrides": {
            "actions": {
                "create": "Add holiday\/closure",
                "delete": "Delete",
                "more": "More actions"
            }
        }
    },
    "brand": {
        "logoAlt": "CDA San Jorge logo",
        "name": "CDA San Jorge",
        "legalName": "Del San Jorge S.A.S"
    },
    "auth": {
        "login": {
            "headTitle": "Sign in",
            "title": "Sign in to your account",
            "description": "Enter your email and password to continue.",
            "emailLabel": "Email address",
            "emailPlaceholder": "email{'@'}example.com",
            "passwordLabel": "Password",
            "passwordPlaceholder": "Password",
            "forgotPassword": "Forgot password?",
            "remember": "Remember me",
            "submit": "Sign in"
        }
    },
    "settings": {
        "appearance": {
            "breadcrumb": "Appearance",
            "title": "Appearance",
            "description": "Update your account appearance settings.",
            "currencyFormat": {
                "title": "Currency format",
                "description": "Define how monetary values are displayed.",
                "locale": "Regional format",
                "display": "Currency display",
                "decimals": "Decimals",
                "preview": "Preview",
                "locales": {
                    "esCO": "Colombia (es-CO)",
                    "enUS": "United States (en-US)",
                    "esES": "Spain (es-ES)"
                },
                "displayOptions": {
                    "symbol": "Symbol",
                    "code": "ISO code"
                },
                "decimalsOptions": {
                    "zero": "0 decimals",
                    "two": "2 decimals",
                    "four": "4 decimals"
                }
            },
            "options": {
                "light": "Light",
                "dark": "Dark",
                "system": "System"
            },
            "navigationGrouping": {
                "title": "Navigation grouping",
                "description": "Choose how the main navigation is organized.",
                "operational": "Operational",
                "contractual": "Contractual"
            }
        },
        "twoFactor": {
            "headTitle": "Two-Factor Authentication",
            "breadcrumbsTitle": "Two-Factor Authentication",
            "srTitle": "Two-Factor Authentication Settings",
            "headingTitle": "Two-Factor Authentication",
            "headingDescription": "Manage your two-factor authentication settings",
            "status": {
                "enabled": "Enabled",
                "disabled": "Disabled"
            },
            "description": {
                "disabled": "When you enable two-factor authentication, you will be prompted for a secure PIN during login. This PIN can be retrieved from a TOTP-supported application on your phone.",
                "enabled": "With two-factor authentication enabled, you will be prompted for a secure, random PIN during login, which you can retrieve from the TOTP-supported application on your phone."
            },
            "actions": {
                "continueSetup": "Continue setup",
                "enable": "Enable 2FA",
                "disable": "Disable 2FA"
            }
        }
    },
    "publicLanding": {
        "headTitle": "Home",
        "badge": "Landing in progress",
        "title": "Public appointment booking",
        "description": "Check dates and available time slots to book your appointment. This access is available to unauthenticated visitors.",
        "upcoming": "Coming soon: complete booking flow from this landing page.",
        "actions": {
            "dashboard": "Dashboard",
            "login": "Sign in",
            "register": "Register",
            "checkAvailability": "Check availability"
        },
        "i18n": {
            "showTranslations": "Show translations",
            "showKeys": "Show i18n keys"
        }
    },
    "publicAvailability": {
        "headTitle": "Public availability",
        "heading": {
            "title": "Availability check",
            "description": "Review available dates and slots to book your visit."
        },
        "i18n": {
            "showTranslations": "Show translations",
            "showKeys": "Show i18n keys"
        },
        "empty": {
            "notConfigured": {
                "title": "Calendar not configured",
                "description": "The inspection center calendar is not configured. Configure working days and hours before checking availability.",
                "cta": "Go to center schedule"
            },
            "noTechnicians": {
                "title": "No technicians available",
                "description": "There are no technicians with configured availability. Configure availability for at least one technician.",
                "cta": "Go to technicians"
            }
        },
        "controls": {
            "previousMonth": "Previous month",
            "nextMonth": "Next month"
        },
        "filters": {
            "technician": "Technician (optional)",
            "allTechnicians": "All technicians"
        },
        "weekdays": {
            "sun": "Sun",
            "mon": "Mon",
            "tue": "Tue",
            "wed": "Wed",
            "thu": "Thu",
            "fri": "Fri",
            "sat": "Sat"
        },
        "availabilityLabels": {
            "unavailable": "No slots",
            "low": "Few slots",
            "available": "Available"
        },
        "slots": {
            "summary": "{date} — {count} available slots",
            "selectDateHint": "Select a day to view available slots.",
            "noSlots": "No slots available for this date.",
            "selected": "Selected",
            "select": "Select",
            "unavailable": "Unavailable"
        },
        "messages": {
            "slotSelected": "Slot selected. We will continue with the booking form."
        },
        "errors": {
            "loadMonth": "Unable to load monthly availability.",
            "connection": "Connection error while loading availability."
        }
    }
} as const;
