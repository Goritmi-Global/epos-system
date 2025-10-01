// resources/js/composables/useFormatters.ts
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

/** ========= helpers: DB-driven, no guessing beyond what your strings say ========= **/

function detectSeparators(pattern: string) {
  // Supports: '1000', '1,000', '1.000', '1 000', "1'000", '1,234.56', '1.234,56', '1 234,56'
  const s = String(pattern || '').trim()
  let groupSep = ''
  let decSep = '.'

  const lastComma = s.lastIndexOf(',')
  const lastDot = s.lastIndexOf('.')

  if (lastComma !== -1 || lastDot !== -1) {
    // whichever appears LAST is treated as decimal separator
    if (lastComma > lastDot) decSep = ','
    else decSep = '.'

    // prefer apostrophe or space if present, else the "other" symbol
    if (s.includes("'")) groupSep = "'"
    else if (/\s/.test(s)) groupSep = ' '
    else {
      const other = decSep === ',' ? '.' : ','
      groupSep = s.includes(other) ? other : ''
    }
  } else {
    // no dot/comma → maybe space/apostrophe grouping or no grouping
    if (s.includes("'")) groupSep = "'"
    else if (/\s/.test(s)) groupSep = ' '
    else groupSep = ''
    decSep = '.' // default decimal sep when not provided
  }

  return { groupSep, decSep }
}

function parseCurrencyPosition(sampleOrWord: string | undefined) {
  const v = String(sampleOrWord || '').toLowerCase().trim()
  if (v.includes('before')) return 'before'
  if (v.includes('after')) return 'after'
  // allow samples like "$123" or "123$"
  if (/\d/.test(v)) {
    const idx123 = v.indexOf('123')
    if (idx123 > -1) {
      const leading = v.slice(0, idx123).trim()
      const trailing = v.slice(idx123 + 3).trim()
      if (leading.length) return 'before'
      if (trailing.length) return 'after'
    }
  }
  return 'before'
}

// Coerce labels like "GMT (UTC+01:00)" / "UTC+01:00" / "GMT Standard Time" → IANA
function coerceToIANA(tz?: string): string {
  const v = String(tz || '').trim()
  if (!v) return 'UTC'

  // already IANA-like
  if (/^[A-Za-z]+\/[A-Za-z0-9_\-+]+$/.test(v)) return v

  // Windows UK name (very common)
  if (/^gmt standard time$/i.test(v)) return 'Europe/London'

  // Extract "UTC±HH(:MM)?" even if wrapped like "GMT (UTC+01:00)"
  const m = v.match(/UTC\s*([+-])\s*(\d{1,2})(?::?(\d{2}))?/i)
  if (m) {
    // Etc/GMT signage is reversed vs UTC
    const sign = m[1] === '+' ? '-' : '+'
    const hh = String(parseInt(m[2], 10))
    return `Etc/GMT${sign}${hh}` // minutes ignored by Etc/GMT
  }

  // Also allow "GMT±HH(:MM)?"
  const m2 = v.match(/GMT\s*([+-])\s*(\d{1,2})(?::?(\d{2}))?/i)
  if (m2) {
    const sign = m2[1] === '+' ? '-' : '+'
    const hh = String(parseInt(m2[2], 10))
    return `Etc/GMT${sign}${hh}`
  }

  return 'UTC'
}

// Parse ISO string | Date | unix seconds | unix ms → Date | null
function parseToDate(input: unknown): Date | null {
  if (input == null) return null
  if (input instanceof Date) return isNaN(input.getTime()) ? null : input

  // numeric?
  if (typeof input === 'number') {
    // treat < 10^12 as seconds (e.g., 1696137600), otherwise ms
    const ms = input < 1e12 ? input * 1000 : input
    const d = new Date(ms)
    return isNaN(d.getTime()) ? null : d
  }

  // string?
  const s = String(input).trim()
  if (!s) return null
  if (/^\d+$/.test(s)) {
    const num = Number(s)
    const ms = num < 1e12 ? num * 1000 : num
    const d = new Date(ms)
    return isNaN(d.getTime()) ? null : d
  }

  const d = new Date(s)
  return isNaN(d.getTime()) ? null : d
}

// Split number preserving decimals (no rounding). Handles scientific notation.
function splitNumberPreserve(n: number) {
  let s = String(n)
  if (/[eE]/.test(s)) {
    // expand to 20 decimals, then trim trailing zeros
    s = n.toFixed(20).replace(/(?:\.0+|(\.\d+?)0+)$/, '$1')
  }
  if (!s.includes('.')) return { int: s, frac: '' }
  const [int, frac] = s.split('.')
  return { int, frac }
}

// group integer part with chosen separator (3-digit grouping by default)
function groupInteger(intRaw: string, groupSep: string) {
  // remove any existing separators just in case
  const clean = intRaw.replace(/[,\.\s']/g, '')
  return clean.replace(/\B(?=(\d{3})+(?!\d))/g, groupSep)
}

// Format number using DB pattern, preserving original decimals if requested
function formatWithPattern(n: number, pattern: string, opts?: { preserveDecimals?: boolean; fixedDigits?: number }) {
  const { groupSep, decSep } = detectSeparators(pattern)
  const neg = n < 0
  const abs = Math.abs(n)

  let intPart = ''
  let fracPart = ''

  if (opts?.preserveDecimals) {
    const { int, frac } = splitNumberPreserve(abs)
    intPart = groupInteger(int, groupSep)
    fracPart = frac // untouched
  } else {
    const digits = Math.max(0, opts?.fixedDigits ?? 2)
    const fixed = abs.toFixed(digits) // e.g., "1234.50"
    const [int, frac] = fixed.split('.')
    intPart = groupInteger(int, groupSep)
    fracPart = digits > 0 ? frac : ''
  }

  const out = fracPart ? `${intPart}${decSep}${fracPart}` : intPart
  return neg ? `-${out}` : out
}

function formatDateParts(d: Date, tz: string, twelveHour: boolean) {
  const safeTZ = coerceToIANA(tz)

  let partsDate: Intl.DateTimeFormatPart[]
  let partsTime: Intl.DateTimeFormatPart[]

  try {
    partsDate = new Intl.DateTimeFormat('en-GB', {
      timeZone: safeTZ,
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
    }).formatToParts(d)

    partsTime = new Intl.DateTimeFormat('en-GB', {
      timeZone: safeTZ,
      hour: '2-digit',
      minute: '2-digit',
      hour12: twelveHour,
    }).formatToParts(d)
  } catch {
    // absolute safety net
    partsDate = new Intl.DateTimeFormat('en-GB', {
      timeZone: 'UTC',
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
    }).formatToParts(d)

    partsTime = new Intl.DateTimeFormat('en-GB', {
      timeZone: 'UTC',
      hour: '2-digit',
      minute: '2-digit',
      hour12: twelveHour,
    }).formatToParts(d)
  }

  const grab = (arr: Intl.DateTimeFormatPart[], type: string) =>
    arr.find(p => p.type === type)?.value || ''

  const yyyy = grab(partsDate, 'year')
  const MM = grab(partsDate, 'month')
  const dd = grab(partsDate, 'day')
  const hh = grab(partsTime, 'hour')
  const mm = grab(partsTime, 'minute')
  const dayPeriod = grab(partsTime, 'dayPeriod')

  return { yyyy, MM, dd, hh, mm, dayPeriod }
}

function buildDateString(df: string, yyyy: string, MM: string, dd: string) {
  const pattern = (df || 'yyyy-MM-dd').trim()
  const sep = pattern.includes('/') ? '/' : (pattern.includes('-') ? '-' : '/')
  if (pattern.startsWith('dd')) return `${dd}${sep}${MM}${sep}${yyyy}`
  if (pattern.startsWith('MM')) return `${MM}${sep}${dd}${sep}${yyyy}`
  return `${yyyy}${sep}${MM}${sep}${dd}` // yyyy-first
}

/** ======================= main composable ======================= **/

export function useFormatters() {
  const page = usePage()

  const formatting = computed(() => page.props.formatting ?? {
    // fallbacks only if server didn’t send them
    locale: 'en-US',
    dateFormat: 'yyyy-MM-dd',
    timeFormat: '24hour',
    currency: 'PKR',
    currencyPosition: 'before',
    timezone: 'UTC',
    numberPattern: '1,234.56',
  })

  const numberPattern = computed(() => String(formatting.value.numberPattern || '1,234.56'))
  const currencyStr   = computed(() => String(formatting.value.currency ?? ''))
  const currencyPos   = computed(() => parseCurrencyPosition(formatting.value.currencyPosition))
  const tz            = computed(() => String(formatting.value.timezone || 'UTC'))
  const dateFormat    = computed(() => String(formatting.value.dateFormat || 'yyyy-MM-dd'))
  const is12h         = computed(() => String(formatting.value.timeFormat || '').toLowerCase().includes('12'))

  // MONEY: minor units → 2 decimals, grouping from your number_pattern, symbol position from DB
  const formatMoney = (minor: number) => {
    const val = (minor ?? 0) / 100
    const num = formatWithPattern(val, numberPattern.value, { preserveDecimals: false, fixedDigits: 2 })
    return currencyPos.value === 'after'
      ? `${num} ${currencyStr.value}`.trim()
      : `${currencyStr.value} ${num}`.trim()
  }

  // NUMBER: preserve original decimals; do NOT touch the fraction part
  // Example: 12345.678 -> with pattern '1 000' becomes "12 345.678" (decimal unchanged),
  // with pattern '1.000' becomes "12.345,678" (decimal symbol swapped to DB-style).
  const formatNumber = (n: number) =>
    formatWithPattern(n ?? 0, numberPattern.value, { preserveDecimals: true })

  // DATE/TIME: honor date_format + time_format + timezone (with coercion)
  const dateFmt = (input: string | number | Date) => {
    const d = parseToDate(input)
    if (!d) return '' // avoids "31/12/1969 23:59" on bad inputs

    const { yyyy, MM, dd, hh, mm, dayPeriod } = formatDateParts(d, tz.value, is12h.value)
    const dateStr = buildDateString(dateFormat.value, yyyy, MM, dd)
    const timeStr = is12h.value ? `${hh}:${mm}${dayPeriod ? ' ' + dayPeriod : ''}` : `${hh}:${mm}`
    return `${dateStr} ${timeStr}`
  }

  // expose as-is for convenience
  const locale   = computed(() => String(formatting.value.locale || ''))
  const currency = computed(() => currencyStr.value)
  const timezone = computed(() => tz.value)

  return { formatMoney, formatNumber, dateFmt, locale, currency, timezone }
}
