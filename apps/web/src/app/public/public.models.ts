export interface PublicProduct {
  id: number;
  name: string;
  description?: string | null;
  price?: number | string | null;
  imageUrl?: string | null;
  ingredients: string[];
}

export interface PublicCategory { id: number; name: string; products: PublicProduct[]; }
export interface BusinessHours { dayOfWeek: number; opensAt?: string | null; closesAt?: string | null; isClosed: boolean; }
export interface PublicCatalog {
  business: { name: string; description?: string | null; logoUrl?: string | null; cover_image_url?: string | null };
  hours: BusinessHours[];
  categories: PublicCategory[];
}
export interface DirectoryBusiness { name: string; slug: string; logo_url?: string | null; category?: string | null; location?: string | null; latitude?: number | null; longitude?: number | null; }
export interface BusinessDirectoryResponse { businesses: DirectoryBusiness[]; }
export interface CartItem { product: PublicProduct; quantity: number; note: string; }
