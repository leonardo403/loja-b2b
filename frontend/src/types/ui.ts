export interface CategoryTheme {
  gradient: string
  icon: string
  accent: string
  badge: string
}

export interface ToastMessage {
  id: number
  type: 'success' | 'error' | 'info'
  message: string
}
