export interface OrderItem {
  name: string;
  quantity: number;
  note?: string | null;
}

export interface Order {
  id: number;
  created_at: string;
  customer_note?: string | null;
  total?: number | string | null;
  items: OrderItem[];
}

export interface OrderList {
  orders: Order[];
  count: number;
}
