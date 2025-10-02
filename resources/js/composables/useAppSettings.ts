import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function useAppSettings() {
  const page = usePage()

  const user = computed(() => page.props.current_user ?? null)
  const onboarding = computed(() => page.props.onboarding ?? {})
  const formatting = computed(() => page.props.formatting ?? {
    locale: 'en-US', dateFormat: 'yyyy-MM-dd', timeFormat: 'HH:mm',
    currency: 'PKR', currencyPosition: 'before', timezone: 'UTC',
  })

  return { user, onboarding, formatting }
}