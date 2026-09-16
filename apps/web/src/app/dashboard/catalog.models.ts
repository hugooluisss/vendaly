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
}
