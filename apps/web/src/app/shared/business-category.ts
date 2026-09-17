export const BUSINESS_CATEGORIES = [
  { value: 'restaurant', label: 'Restaurante' },
  { value: 'cafe', label: 'Café' },
  { value: 'beauty_salon', label: 'Salón de belleza' },
  { value: 'professional_services', label: 'Servicios profesionales' },
  { value: 'store', label: 'Tienda' },
  { value: 'repair_services', label: 'Reparaciones' },
  { value: 'other', label: 'Otro' },
] as const;

export function businessCategoryLabel(value?: string | null): string {
  return BUSINESS_CATEGORIES.find(category => category.value === value)?.label ?? 'Sin categoría';
}
