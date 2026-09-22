export interface Category {
  id: number;
  name: string;
  position: number;
}

export interface Product {
  id: number;
  category_id: number;
  name: string;
  description?: string | null;
  price?: number | string | null;
  is_active: boolean;
  position: number;
  image_url?: string | null;
  ingredients: string[];
  options: ProductOption[];
}

export interface ProductOptionValue { id?: number; name: string; price_delta: number | string; position?: number; }
export interface ProductOption { id?: number; name: string; selection_type: 'single' | 'multiple'; required: boolean; position?: number; values: ProductOptionValue[]; }
