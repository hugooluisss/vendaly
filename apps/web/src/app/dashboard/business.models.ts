export interface Business {
  id: number;
  name: string;
  slug?: string;
  logo_url?: string | null;
  whatsapp_number?: string | null;
  description?: string | null;
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
