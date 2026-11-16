import type { CategoryTheme } from '@/types/ui'

const themes: Record<string, CategoryTheme> = {
  Bebidas: {
    gradient: 'from-sky-100 to-cyan-50',
    icon: 'CupSoda',
    accent: 'text-sky-600',
    badge: 'bg-sky-100 text-sky-700',
  },
  'Frios e Laticínios': {
    gradient: 'from-amber-100 to-yellow-50',
    icon: 'Milk',
    accent: 'text-amber-600',
    badge: 'bg-amber-100 text-amber-700',
  },
  Higiene: {
    gradient: 'from-violet-100 to-purple-50',
    icon: 'Sparkles',
    accent: 'text-violet-600',
    badge: 'bg-violet-100 text-violet-700',
  },
  Limpeza: {
    gradient: 'from-emerald-100 to-teal-50',
    icon: 'SprayCan',
    accent: 'text-emerald-600',
    badge: 'bg-emerald-100 text-emerald-700',
  },
  Mercearia: {
    gradient: 'from-orange-100 to-rose-50',
    icon: 'ShoppingBasket',
    accent: 'text-orange-600',
    badge: 'bg-orange-100 text-orange-700',
  },
}

const fallback: CategoryTheme = {
  gradient: 'from-slate-100 to-slate-50',
  icon: 'Package',
  accent: 'text-slate-500',
  badge: 'bg-slate-100 text-slate-700',
}

export function categoryTheme(category: string | null | undefined): CategoryTheme {
  if (!category) return fallback

  return themes[category] ?? fallback
}
