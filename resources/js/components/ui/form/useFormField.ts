import { useFormErrors } from "vee-validate"
import { computed, inject } from "vue"
import { FORM_FIELD_INJECTION_KEY, FORM_ITEM_INJECTION_KEY } from "./constants"

export function useFormField() {
  const fieldName = inject(FORM_FIELD_INJECTION_KEY)
  const formItemContext = inject(FORM_ITEM_INJECTION_KEY)
  const formErrors = useFormErrors()

  if (!fieldName) {
    throw new Error("useFormField should be used within <FormField>")
  }

  if (!formItemContext) {
    throw new Error("useFormField should be used within <FormItem>")
  }

  const error = computed(() => formErrors.value[fieldName.value])
  const formItemId = computed(() => `${formItemContext.id}-form-item`)
  const formDescriptionId = computed(() => `${formItemContext.id}-form-item-description`)
  const formMessageId = computed(() => `${formItemContext.id}-form-item-message`)

  return {
    id: computed(() => formItemContext.id),
    name: fieldName,
    error,
    formItemId,
    formDescriptionId,
    formMessageId,
  }
}
