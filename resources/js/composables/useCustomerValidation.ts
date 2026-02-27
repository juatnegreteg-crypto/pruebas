import { computed, ref, type Ref } from 'vue';

export interface CustomerFormData {
    fullName: string;
    email: string;
    documentType: string;
    documentNumber: string;
    phoneNumber: string;
    addresses: PartyAddressForm[];
}

export interface PartyAddressForm {
    id?: number | null;
    type: string;
    isPrimary: boolean;
    street: string;
    complement: string;
    neighborhood: string;
    city: string;
    state: string;
    postalCode: string;
    country: string;
    reference: string;
}

export type ValidationErrors = Record<string, string | undefined>;

export function useCustomerValidation(formData: Ref<CustomerFormData>) {
    const errors = ref<ValidationErrors>({});

    const validateFullName = (): boolean => {
        const value = formData.value.fullName.trim();

        if (!value) {
            errors.value.fullName = 'El nombre completo es obligatorio.';
            return false;
        }

        if (value.length > 255) {
            errors.value.fullName =
                'El nombre completo no puede exceder 255 caracteres.';
            return false;
        }

        // Validar que no contenga números, punto y coma, comillas, guiones
        const regex = /^[^\d;'"\\-]*$/;
        if (!regex.test(value)) {
            errors.value.fullName =
                'El nombre no debe contener números ni caracteres especiales (;, \', ", \\, -).';
            return false;
        }

        delete errors.value.fullName;
        return true;
    };

    const validateEmail = (): boolean => {
        const value = formData.value.email.trim().toLowerCase();

        if (!value) {
            errors.value.email = 'El correo electrónico es obligatorio.';
            return false;
        }

        if (value.length > 255) {
            errors.value.email =
                'El correo electrónico no puede exceder 255 caracteres.';
            return false;
        }

        // Validación básica de email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            errors.value.email =
                'El correo electrónico debe ser una dirección válida.';
            return false;
        }

        delete errors.value.email;
        return true;
    };

    const validateDocumentType = (): boolean => {
        const value = formData.value.documentType;

        if (!value) {
            errors.value.documentType = 'El tipo de documento es obligatorio.';
            return false;
        }

        delete errors.value.documentType;
        return true;
    };

    const validateDocumentNumber = (): boolean => {
        const value = formData.value.documentNumber.trim();

        if (!value) {
            errors.value.documentNumber =
                'El número de documento es obligatorio.';
            return false;
        }

        if (value.length > 50) {
            errors.value.documentNumber =
                'El número de documento no puede exceder 50 caracteres.';
            return false;
        }

        // Validar que solo contenga dígitos
        if (!/^\d+$/.test(value)) {
            errors.value.documentNumber =
                'El número de documento solo debe contener dígitos.';
            return false;
        }

        // Validar que tenga entre 8 y 10 dígitos
        if (value.length < 8 || value.length > 10) {
            errors.value.documentNumber =
                'El número de documento debe tener entre 8 y 10 dígitos.';
            return false;
        }

        delete errors.value.documentNumber;
        return true;
    };

    const validatePhoneNumber = (): boolean => {
        const value = formData.value.phoneNumber?.trim();

        // El teléfono es opcional
        if (!value) {
            delete errors.value.phoneNumber;
            return true;
        }

        // Remover espacios y guiones para validar solo dígitos
        const cleanValue = value.replace(/[\s-]/g, '');

        // Validar que solo contenga dígitos
        if (!/^\d+$/.test(cleanValue)) {
            errors.value.phoneNumber =
                'El teléfono solo debe contener dígitos.';
            return false;
        }

        // Validar que tenga exactamente 10 dígitos
        if (cleanValue.length !== 10) {
            errors.value.phoneNumber = 'El teléfono debe tener 10 dígitos.';
            return false;
        }

        delete errors.value.phoneNumber;
        return true;
    };

    const validateAddresses = (): boolean => {
        const addressErrors: ValidationErrors = {};

        formData.value.addresses.forEach((address, index) => {
            const prefix = `addresses.${index}.`;

            if (!address.type) {
                addressErrors[`${prefix}type`] =
                    'El tipo de dirección es obligatorio.';
            }

            if (!address.street.trim()) {
                addressErrors[`${prefix}street`] =
                    'La dirección es obligatoria.';
            }

            if (!address.city.trim()) {
                addressErrors[`${prefix}city`] = 'La ciudad es obligatoria.';
            }

            if (!address.state.trim()) {
                addressErrors[`${prefix}state`] =
                    'El departamento es obligatorio.';
            }

            if (!address.country.trim()) {
                addressErrors[`${prefix}country`] = 'El país es obligatorio.';
            }
        });

        Object.keys(errors.value)
            .filter((key) => key.startsWith('addresses.'))
            .forEach((key) => {
                delete errors.value[key];
            });

        Object.assign(errors.value, addressErrors);

        return Object.keys(addressErrors).length === 0;
    };

    const validateAll = (): boolean => {
        const validations = [
            validateFullName(),
            validateEmail(),
            validateDocumentType(),
            validateDocumentNumber(),
            validatePhoneNumber(),
            validateAddresses(),
        ];

        return validations.every((isValid) => isValid);
    };

    const isValid = computed(() => {
        return Object.keys(errors.value).length === 0;
    });

    return {
        errors,
        validateFullName,
        validateEmail,
        validateDocumentType,
        validateDocumentNumber,
        validatePhoneNumber,
        validateAll,
        isValid,
    };
}
