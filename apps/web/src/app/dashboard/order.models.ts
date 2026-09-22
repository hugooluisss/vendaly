export interface OrderItem {
  name: string;
  quantity: number;
  note?: string | null;
}

export interface OrderStatus {
  id: number;
  name: string;
  color: string;
}

export interface Order {
  id: number;
  order_number: number;
  created_at: string;
  status: OrderStatus;
  customer_phone?: string | null;
  customer_note?: string | null;
  total?: number | string | null;
  items: OrderItem[];
}

export interface OrderList {
  orders: Order[];
  count: number;
}
