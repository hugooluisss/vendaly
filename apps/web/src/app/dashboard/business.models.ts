export interface Business {
  id: number;
  name: string;
  slug?: string;
  logo_url?: string | null;
  cover_image_url?: string | null;
  latitude?: number | null;
  longitude?: number | null;
  whatsapp_number?: string | null;
  description?: string | null;
  category?: string | null;
  location?: string | null;
  is_published?: boolean;
  pickup_enabled?: boolean;
  delivery_enabled?: boolean;
  dine_in_enabled?: boolean;
  pickup_fee?: number | string | null;
  delivery_fee?: number | string | null;
  dine_in_fee?: number | string | null;
  payment_methods?: PaymentMethod[];
  order_statuses?: OrderStatus[];
}

export interface PaymentMethod { id: number; name: string; position: number; }
export interface OrderStatus { id: number; name: string; color: string; is_terminal: boolean; is_default: boolean; position: number; }

export interface BusinessHour {
  day_of_week: number;
  opens_at?: string | null;
  closes_at?: string | null;
  is_closed: boolean;
}

export interface BusinessSettings {
  business: Business;
  hours: BusinessHour[];
}
