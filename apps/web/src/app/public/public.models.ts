export interface PublicProduct {
  id: number;
  name: string;
  description?: string | null;
  price?: number | string | null;
  imageUrl?: string | null;
  ingredients: string[];
  options?: PublicProductOption[];
}

export interface PublicProductOptionValue { id: number; name: string; price_delta: number | string; }
export interface PublicProductOption { id: number; name: string; selection_type: 'single' | 'multiple'; required: boolean; values: PublicProductOptionValue[]; }

export interface PublicCategory { id: number; name: string; products: PublicProduct[]; }
export interface PublicFulfillmentMethod { type: 'pickup' | 'delivery' | 'dine_in'; fee?: number | string | null; }
export interface PublicPaymentMethod { id: number; name: string; position?: number; }
export interface BusinessHours { dayOfWeek: number; opensAt?: string | null; closesAt?: string | null; isClosed: boolean; }
export interface PublicCatalog {
  business: { name: string; description?: string | null; logoUrl?: string | null; cover_image_url?: string | null };
  hours: BusinessHours[];
  fulfillment_methods: PublicFulfillmentMethod[];
  payment_methods: PublicPaymentMethod[];
  categories: PublicCategory[];
}
export interface DirectoryBusiness { name: string; slug: string; logo_url?: string | null; category?: string | null; location?: string | null; latitude?: number | null; longitude?: number | null; }
export interface BusinessDirectoryResponse { businesses: DirectoryBusiness[]; }
export interface CartItem { key?: string; product: PublicProduct; quantity: number; note: string; selectedOptionValueIds?: number[]; }
