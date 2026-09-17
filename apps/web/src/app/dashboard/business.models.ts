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
}

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
