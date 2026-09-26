export interface Business {
  id: number;
  name: string;
  slug?: string;
  logo_url?: string | null;
  cover_image_url?: string | null;
  latitude?: number | null;
  longitude?: number | null;
  whatsapp_number?: string | null;
  facebook_url?: string | null;
  instagram_url?: string | null;
  website_url?: string | null;
  description?: string | null;
  category?: string | null;
  location?: string | null;
  is_published?: boolean;
  wallet_enabled?: boolean;
  fulfillment_methods?: FulfillmentMethod[];
  payment_methods?: PaymentMethod[];
  order_statuses?: OrderStatus[];
}

export interface PaymentMethod { id: number; name: string; position: number; }
export interface FulfillmentMethod { id: number; name: string; position: number; fee?: number | string | null; requires_address: boolean; }
export interface OrderStatus { id: number; name: string; color: string; is_terminal: boolean; is_default: boolean; reverses_wallet?: boolean; position: number; }

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
