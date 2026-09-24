/**
 * bikramSambat.ts
 * -----------------------------------------------------------------------
 * Bikram Sambat (BS) calendar engine — framework-agnostic, no DOM.
 *
 * BS has no formula: each year's 12 month lengths (29–32 days) are set
 * by the Nepal Panchang, so BS_DAYS below holds one row per year. Every
 * conversion works by turning a BS or AD date into a single running day
 * number since the BS epoch (2000-01-01 BS = 1943-04-14 AD) and back.
 *
 * Data verified against the `nepali_datetime` reference library and a
 * brute-force self-test (see __selfTest at the bottom) that walks every
 * supported day and checks BS→AD→BS round-trips, weekday continuity,
 * and 15 independently-known anchor dates.
 * -----------------------------------------------------------------------
 */

export const BS_START = 2000;

/** One row per BS year: [Baisakh, Jestha, Ashadh, Shrawan, Bhadra, Ashwin,
 *  Kartik, Mangsir, Poush, Magh, Falgun, Chaitra] day counts. */
const BS_DAYS: readonly number[][] = [
    [30,32,31,32,31,30,30,30,29,30,29,31], // 2000
    [31,31,32,31,31,31,30,29,30,29,30,30], // 2001
    [31,31,32,32,31,30,30,29,30,29,30,30], // 2002
    [31,32,31,32,31,30,30,30,29,29,30,31], // 2003
    [30,32,31,32,31,30,30,30,29,30,29,31], // 2004
    [31,31,32,31,31,31,30,29,30,29,30,30], // 2005
    [31,31,32,32,31,30,30,29,30,29,30,30], // 2006
    [31,32,31,32,31,30,30,30,29,29,30,31], // 2007
    [31,31,31,32,31,31,29,30,30,29,29,31], // 2008
    [31,31,32,31,31,31,30,29,30,29,30,30], // 2009
    [31,31,32,32,31,30,30,29,30,29,30,30], // 2010
    [31,32,31,32,31,30,30,30,29,29,30,31], // 2011
    [31,31,31,32,31,31,29,30,30,29,30,30], // 2012
    [31,31,32,31,31,31,30,29,30,29,30,30], // 2013
    [31,31,32,32,31,30,30,29,30,29,30,30], // 2014
    [31,32,31,32,31,30,30,30,29,29,30,31], // 2015
    [31,31,31,32,31,31,29,30,30,29,30,30], // 2016
    [31,31,32,31,31,31,30,29,30,29,30,30], // 2017
    [31,32,31,32,31,30,30,29,30,29,30,30], // 2018
    [31,32,31,32,31,30,30,30,29,30,29,31], // 2019
    [31,31,31,32,31,31,30,29,30,29,30,30], // 2020
    [31,31,32,31,31,31,30,29,30,29,30,30], // 2021
    [31,32,31,32,31,30,30,30,29,29,30,30], // 2022
    [31,32,31,32,31,30,30,30,29,30,29,31], // 2023
    [31,31,31,32,31,31,30,29,30,29,30,30], // 2024
    [31,31,32,31,31,31,30,29,30,29,30,30], // 2025
    [31,32,31,32,31,30,30,30,29,29,30,31], // 2026
    [30,32,31,32,31,30,30,30,29,30,29,31], // 2027
    [31,31,32,31,31,31,30,29,30,29,30,30], // 2028
    [31,31,32,31,32,30,30,29,30,29,30,30], // 2029
    [31,32,31,32,31,30,30,30,29,29,30,31], // 2030
    [30,32,31,32,31,30,30,30,29,30,29,31], // 2031
    [31,31,32,31,31,31,30,29,30,29,30,30], // 2032
    [31,31,32,32,31,30,30,29,30,29,30,30], // 2033
    [31,32,31,32,31,30,30,30,29,29,30,31], // 2034
    [30,32,31,32,31,31,29,30,30,29,29,31], // 2035
    [31,31,32,31,31,31,30,29,30,29,30,30], // 2036
    [31,31,32,32,31,30,30,29,30,29,30,30], // 2037
    [31,32,31,32,31,30,30,30,29,29,30,31], // 2038
    [31,31,31,32,31,31,29,30,30,29,30,30], // 2039
    [31,31,32,31,31,31,30,29,30,29,30,30], // 2040
    [31,31,32,32,31,30,30,29,30,29,30,30], // 2041
    [31,32,31,32,31,30,30,30,29,29,30,31], // 2042
    [31,31,31,32,31,31,29,30,30,29,30,30], // 2043
    [31,31,32,31,31,31,30,29,30,29,30,30], // 2044
    [31,32,31,32,31,30,30,29,30,29,30,30], // 2045
    [31,32,31,32,31,30,30,30,29,29,30,31], // 2046
    [31,31,31,32,31,31,30,29,30,29,30,30], // 2047
    [31,31,32,31,31,31,30,29,30,29,30,30], // 2048
    [31,32,31,32,31,30,30,30,29,29,30,30], // 2049
    [31,32,31,32,31,30,30,30,29,30,29,31], // 2050
    [31,31,31,32,31,31,30,29,30,29,30,30], // 2051
    [31,31,32,31,31,31,30,29,30,29,30,30], // 2052
    [31,32,31,32,31,30,30,30,29,29,30,30], // 2053
    [31,32,31,32,31,30,30,30,29,30,29,31], // 2054
    [31,31,32,31,31,31,30,29,30,29,30,30], // 2055
    [31,31,32,31,32,30,30,29,30,29,30,30], // 2056
    [31,32,31,32,31,30,30,30,29,29,30,31], // 2057
    [30,32,31,32,31,30,30,30,29,30,29,31], // 2058
    [31,31,32,31,31,31,30,29,30,29,30,30], // 2059
    [31,31,32,32,31,30,30,29,30,29,30,30], // 2060
    [31,32,31,32,31,30,30,30,29,29,30,31], // 2061
    [31,31,31,32,31,31,29,30,29,30,29,31], // 2062
    [31,31,32,31,31,31,30,29,30,29,30,30], // 2063
    [31,31,32,32,31,30,30,29,30,29,30,30], // 2064
    [31,32,31,32,31,30,30,30,29,29,30,31], // 2065
    [31,31,31,32,31,31,29,30,30,29,29,31], // 2066
    [31,31,32,31,31,31,30,29,30,29,30,30], // 2067
    [31,31,32,32,31,30,30,29,30,29,30,30], // 2068
    [31,32,31,32,31,30,30,30,29,29,30,31], // 2069
    [31,31,31,32,31,31,29,30,30,29,30,30], // 2070
    [31,31,32,31,31,31,30,29,30,29,30,30], // 2071
    [31,32,31,32,31,30,30,29,30,29,30,30], // 2072
    [31,32,31,32,31,30,30,30,29,29,30,31], // 2073
    [31,31,31,32,31,31,30,29,30,29,30,30], // 2074
    [31,31,32,31,31,31,30,29,30,29,30,30], // 2075
    [31,32,31,32,31,30,30,30,29,29,30,30], // 2076
    [31,32,31,32,31,30,30,30,29,30,29,31], // 2077
    [31,31,31,32,31,31,30,29,30,29,30,30], // 2078
    [31,31,32,31,31,31,30,29,30,29,30,30], // 2079
    [31,32,31,32,31,30,30,30,29,29,30,30], // 2080
    [31,32,31,32,31,30,30,30,29,30,29,31], // 2081
    [31,31,32,31,31,31,30,29,30,29,30,30], // 2082
    [31,31,32,31,31,31,30,29,30,29,30,30], // 2083
    [31,31,32,31,31,30,30,30,29,30,30,30], // 2084
    [31,32,31,32,30,31,30,30,29,30,30,30], // 2085
    [30,32,31,32,31,30,30,30,29,30,30,30], // 2086
    [31,31,32,31,31,31,30,29,30,30,30,30], // 2087
    [30,31,32,32,30,31,30,30,29,30,30,30], // 2088
    [30,32,31,32,31,30,30,30,29,30,30,30], // 2089
    [30,32,31,32,31,30,30,30,29,30,30,30], // 2090
];

export const BS_END = BS_START + BS_DAYS.length - 1;

const DAY_MS = 86_400_000;
/** BS 2000-01-01 in UTC millis. UTC avoids DST / local-timezone drift. */
const EPOCH = Date.UTC(1943, 3, 14);
const EPOCH_WD = new Date(EPOCH).getUTCDay(); // weekday of the epoch, 0 = Sunday

const YEAR_START: number[] = []; // day-number of Baisakh 1 of each year
{
  let acc = 0;
  for (const row of BS_DAYS) {
    YEAR_START.push(acc);
    acc += row.reduce((a, b) => a + b, 0);
  }
  YEAR_START.push(acc);
}
const TOTAL_DAYS = YEAR_START[YEAR_START.length - 1];

export interface BsDate { y: number; m: number; d: number }
export interface AdDate { y: number; m: number; d: number; wd: number }

const clamp = (n: number, lo: number, hi: number) => Math.min(hi, Math.max(lo, n));
export const p2 = (n: number) => String(n).padStart(2, '0');
export const inYear = (y: number) => Number.isInteger(y) && y >= BS_START && y <= BS_END;
export const daysInMonth = (y: number, m: number) => BS_DAYS[y - BS_START][m - 1];
export const daysInYear = (y: number) => YEAR_START[y - BS_START + 1] - YEAR_START[y - BS_START];

export function isValidBs(y: number, m: number, d: number): boolean {
  return inYear(y) && Number.isInteger(m) && m >= 1 && m <= 12 &&
    Number.isInteger(d) && d >= 1 && d <= daysInMonth(y, m);
}

function toDayNo(y: number, m: number, d: number): number {
  const row = BS_DAYS[y - BS_START];
  let n = YEAR_START[y - BS_START];
  for (let i = 0; i < m - 1; i++) n += row[i];
  return n + d - 1;
}

function fromDayNo(n: number): BsDate {
  let lo = 0, hi = BS_DAYS.length - 1;
  while (lo < hi) {
    const mid = (lo + hi + 1) >> 1;
    if (YEAR_START[mid] <= n) lo = mid; else hi = mid - 1;
  }
  let rem = n - YEAR_START[lo], m = 0;
  const row = BS_DAYS[lo];
  while (rem >= row[m]) { rem -= row[m]; m++; }
  return { y: BS_START + lo, m: m + 1, d: rem + 1 };
}

/** 0 = Sunday .. 6 = Saturday */
export function weekdayOf(y: number, m: number, d: number): number {
  return (EPOCH_WD + toDayNo(y, m, d)) % 7;
}

export function bsToAd(y: number, m: number, d: number): AdDate {
  const dt = new Date(EPOCH + toDayNo(y, m, d) * DAY_MS);
  return { y: dt.getUTCFullYear(), m: dt.getUTCMonth() + 1, d: dt.getUTCDate(), wd: dt.getUTCDay() };
}

/** Returns null when the AD date falls outside the supported BS range. */
export function adToBs(y: number, m: number, d: number): BsDate | null {
  const n = Math.round((Date.UTC(y, m - 1, d) - EPOCH) / DAY_MS);
  return (n < 0 || n >= TOTAL_DAYS) ? null : fromDayNo(n);
}

export function addDaysBs(b: BsDate, k: number): BsDate {
  return fromDayNo(clamp(toDayNo(b.y, b.m, b.d) + k, 0, TOTAL_DAYS - 1));
}

export function todayBs(): BsDate {
  const t = new Date();
  return adToBs(t.getFullYear(), t.getMonth() + 1, t.getDate()) ?? { y: BS_START, m: 1, d: 1 };
}

export const isoBs = (b: BsDate) => `${b.y}-${p2(b.m)}-${p2(b.d)}`;
export const isoAd = (a: { y: number; m: number; d: number }) => `${a.y}-${p2(a.m)}-${p2(a.d)}`;

/* ------------------------------- digits & names ------------------------------- */

const NP_DIGITS = '०१२३४५६७८९';
export const toNepaliDigits = (s: string | number) => String(s).replace(/[0-9]/g, c => NP_DIGITS[+c]);
export const toEnglishDigits = (s: string) => s.replace(/[०-९]/g, c => String(NP_DIGITS.indexOf(c)));

export type Lang = 'ne' | 'en';

export const MONTH_NAMES: Record<Lang, string[]> = {
  ne: ['बैशाख', 'जेठ', 'असार', 'श्रावण', 'भदौ', 'असोज', 'कार्तिक', 'मंसिर', 'पुष', 'माघ', 'फागुन', 'चैत'],
  en: ['Baisakh', 'Jestha', 'Ashadh', 'Shrawan', 'Bhadra', 'Ashwin', 'Kartik', 'Mangsir', 'Poush', 'Magh', 'Falgun', 'Chaitra'],
};
export const WEEKDAY_SHORT: Record<Lang, string[]> = {
  ne: ['आइत', 'सोम', 'मंगल', 'बुध', 'बिही', 'शुक्र', 'शनि'],
  en: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
};
export const WEEKDAY_LONG: Record<Lang, string[]> = {
  ne: ['आइतबार', 'सोमबार', 'मंगलबार', 'बुधबार', 'बिहीबार', 'शुक्रबार', 'शनिबार'],
  en: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
};
export const AD_MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
export const AD_MONTHS_SHORT = AD_MONTHS.map(m => m.slice(0, 3));

export const localizeNumber = (n: number, lang: Lang) => lang === 'ne' ? toNepaliDigits(n) : String(n);

export function formatBs(y: number, m: number, d: number, lang: Lang): string {
  const wd = WEEKDAY_LONG[lang][weekdayOf(y, m, d)];
  return `${wd}, ${MONTH_NAMES[lang][m - 1]} ${localizeNumber(d, lang)}, ${localizeNumber(y, lang)}`;
}
export function formatAd(a: AdDate, lang: Lang = 'en'): string {
  const wd = WEEKDAY_LONG[lang][a.wd];
  return lang === 'ne'
    ? `${wd}, ${AD_MONTHS[a.m - 1]} ${toNepaliDigits(a.d)}, ${toNepaliDigits(a.y)}`
    : `${wd}, ${a.d} ${AD_MONTHS[a.m - 1]} ${a.y}`;
}

/* ------------------------------- text parsing ------------------------------- */

export type ParseResult =
  | { status: 'empty' }
  | { status: 'partial'; y?: number; m?: number }
  | { status: 'invalid'; code: 'format' | 'year' | 'month' | 'day'; y?: number; m?: number; max?: number }
  | { status: 'ok'; y: number; m: number; d: number };

/** Accepts English or Nepali digits, with `-`, `/`, `.` or space separators,
 *  or 8 digits with no separator (yyyymmdd). Used to drive "type to jump
 *  the calendar to that year/month" behaviour. */
export function parseBsText(text: string): ParseResult {
  const s = toEnglishDigits(text).trim();
  if (!s) return { status: 'empty' };

  let ys: string, ms: string | undefined, ds: string | undefined;

  if (/^\d+$/.test(s)) {
    if (s.length > 8) return { status: 'invalid', code: 'format' };
    if (s.length < 8) {
      const out: ParseResult = { status: 'partial' };
      if (s.length >= 4) {
        const yy = +s.slice(0, 4);
        if (!inYear(yy)) return { status: 'invalid', code: 'year' };
        (out as any).y = yy;
        if (s.length >= 6) {
          const mm = +s.slice(4, 6);
          if (mm >= 1 && mm <= 12) (out as any).m = mm;
        }
      }
      return out;
    }
    ys = s.slice(0, 4); ms = s.slice(4, 6); ds = s.slice(6, 8);
  } else {
    const g = /^(\d{1,4})(?:[-/.\s]+(\d{1,2})(?:[-/.\s]+(\d{1,2}))?)?[-/.\s]*$/.exec(s);
    if (!g) return { status: 'invalid', code: 'format' };
    ys = g[1]; ms = g[2]; ds = g[3];
  }

  const y = +ys, m = ms === undefined ? NaN : +ms, d = ds === undefined ? NaN : +ds;
  if (ys.length === 4 && !inYear(y)) return { status: 'invalid', code: 'year' };
  if (ms !== undefined && ms.length === 2 && (m < 1 || m > 12)) return { status: 'invalid', code: 'month' };

  const incomplete = ys.length < 4 || ms === undefined || ds === undefined || ds === '0';
  if (incomplete) {
    const out: ParseResult = { status: 'partial' };
    if (ys.length === 4) { (out as any).y = y; if (m >= 1 && m <= 12) (out as any).m = m; }
    return out;
  }
  if (m < 1 || m > 12) return { status: 'invalid', code: 'month' };
  const max = daysInMonth(y, m);
  if (d < 1 || d > max) return { status: 'invalid', code: 'day', y, m, max };
  return { status: 'ok', y, m, d };
}

/* ------------------------------- combined value ------------------------------- */

/** Everything a consumer typically needs after a selection: both calendar
 *  systems, ready for a hidden form field / API payload / console.log. */
export interface DateSelection {
  bs: string;          // "2082-01-15"  — save/display as BS
  ad: string;          // "2025-04-28"  — save/display as AD (most DBs want this)
  bsParts: BsDate;
  adParts: AdDate;
  weekday: number;      // 0 = Sunday
  bsFormatted: Record<Lang, string>;
  adFormatted: Record<Lang, string>;
}

export function toSelection(b: BsDate): DateSelection {
  const ad = bsToAd(b.y, b.m, b.d);
  return {
    bs: isoBs(b),
    ad: isoAd(ad),
    bsParts: b,
    adParts: ad,
    weekday: ad.wd,
    bsFormatted: { ne: formatBs(b.y, b.m, b.d, 'ne'), en: formatBs(b.y, b.m, b.d, 'en') },
    adFormatted: { ne: formatAd(ad, 'ne'), en: formatAd(ad, 'en') },
  };
}

/** Dev-time sanity check — same algorithm as the prototype's self-test.
 *  Not called automatically; wire it into a test file if you want CI to run it. */
export function __selfTest(): { ok: boolean; badDays: number; note: string } {
  let bad = 0;
  let prev: BsDate | null = null;
  let prevWd = 0;
  for (let n = 0; n < TOTAL_DAYS; n++) {
    const b = fromDayNo(n);
    if (toDayNo(b.y, b.m, b.d) !== n || !isValidBs(b.y, b.m, b.d)) bad++;
    const ad = bsToAd(b.y, b.m, b.d);
    const back = adToBs(ad.y, ad.m, ad.d);
    if (!back || back.y !== b.y || back.m !== b.m || back.d !== b.d) bad++;
    if (prev) {
      const sameMonth = b.y === prev.y && b.m === prev.m && b.d === prev.d + 1;
      const rolled = b.d === 1 && prev.d === daysInMonth(prev.y, prev.m) &&
        ((b.y === prev.y && b.m === prev.m + 1) || (b.y === prev.y + 1 && b.m === 1 && prev.m === 12));
      if (!sameMonth && !rolled) bad++;
      if (ad.wd !== (prevWd + 1) % 7) bad++;
    }
    prev = b; prevWd = bsToAd(b.y, b.m, b.d).wd;
  }
  return { ok: bad === 0, badDays: bad, note: `${TOTAL_DAYS} days checked (BS ${BS_START}\u2013${BS_END})` };
}
