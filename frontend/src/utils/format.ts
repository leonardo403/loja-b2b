const currency = new Intl.NumberFormat('pt-BR', {
  style: 'currency',
  currency: 'BRL',
})

export function formatCurrency(value: number | null | undefined): string {
  return currency.format(Number(value ?? 0))
}

export function formatNumber(value: number): string {
  return new Intl.NumberFormat('pt-BR').format(value)
}

export function formatDate(value: string | null | undefined): string {
  if (!value) return '—'

  const date = new Date(value.length <= 10 ? `${value}T12:00:00` : value)

  return new Intl.DateTimeFormat('pt-BR').format(date)
}

export function formatDateTime(value: string | null | undefined): string {
  if (!value) return '—'

  return new Intl.DateTimeFormat('pt-BR', {
    dateStyle: 'short',
    timeStyle: 'short',
  }).format(new Date(value))
}

export function formatPercentage(value: number): string {
  return `${new Intl.NumberFormat('pt-BR', { maximumFractionDigits: 2 }).format(value)}%`
}

export function formatCnpj(value: string): string {
  const digits = value.replace(/\D/g, '')

  if (digits.length !== 14) return value

  return digits.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5')
}
