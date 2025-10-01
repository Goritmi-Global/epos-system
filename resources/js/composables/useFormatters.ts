// resources/js/composables/useFormatters.ts
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";

/** ========= helpers: DB-driven, no guessing beyond what your strings say ========= **/

type GroupingStyle = "western" | "indian" | "none";
type DigitStyle = "latin" | "arabic" | "fullwidth";

const NBSP = "\u00A0";
const NNBSP = "\u202F";

// Detect separators, digit style and grouping style from a sample pattern string
function detectNumberPatternMeta(pattern: string) {
    const s = String(pattern || "").trim();

    // digit style
    let digitStyle: DigitStyle = "latin";
    if (/[٠-٩]/.test(s)) digitStyle = "arabic"; // Arabic-Indic
    if (/[０-９]/.test(s)) digitStyle = "fullwidth"; // Full-width

    // normalize whitespace (treat nbsp/narrow-nbsp as space)
    const sn = s.replace(new RegExp(`[${NBSP}${NNBSP}]`, "g"), " ");

    // decimal separator
    let decSep = ".";
    const lastComma = sn.lastIndexOf(",");
    const lastDot = sn.lastIndexOf(".");
    if (lastComma !== -1 || lastDot !== -1) {
        decSep = lastComma > lastDot ? "," : ".";
    }

    // grouping separator (space/apostrophe/comma/dot) — choose the "other" symbol than decimal if present
    let groupSep = "";
    if (sn.includes("'")) groupSep = "'";
    else if (/\s/.test(sn)) groupSep = " ";
    else {
        const other = decSep === "," ? "." : ",";
        groupSep = sn.includes(other) ? other : "";
    }

    // detect grouping style
    // Strip decimals and digits, look at groups from the RIGHT
    const intPart = sn.split(decSep)[0];
    const cleanDigits = intPart.replace(/[^\d٠-٩０-９]/g, ""); // keep any digit set
    const groups = [];
    let count = 0;
    for (let i = cleanDigits.length - 1; i >= 0; i--) {
        count++;
        // a separator in the sample helps, but we also infer by common patterns
        if (
            groupSep &&
            intPart[intPart.length - (cleanDigits.length - i) - 1] === groupSep
        ) {
            groups.push(count - 1);
            count = 1;
        }
    }
    if (count) groups.push(count);
    // groups[] now holds sizes from rightmost going left

    let grouping: GroupingStyle = "none";
    if (groupSep) {
        // Indian: last 3 then 2s (… 2,2,2,3)
        if (
            groups.length >= 2 &&
            groups[0] === 3 &&
            groups.slice(1).every((g) => g === 2)
        ) {
            grouping = "indian";
        } else if (groups[0] === 3) {
            grouping = "western"; // 3,3,3…
        } else {
            // fall back to western if sample is ambiguous but has a group separator
            grouping = "western";
        }
    }

    return { groupSep, decSep, grouping, digitStyle };
}

function parseCurrencyPosition(sampleOrWord: string | undefined) {
    const v = String(sampleOrWord || "")
        .toLowerCase()
        .trim();
    // strict values
    if (["before", "after", "iso-before", "iso-after", "name"].includes(v))
        return v;
    if (v.includes("iso") && v.includes("before")) return "iso-before";
    if (v.includes("iso") && v.includes("after")) return "iso-after";
    if (v.includes("name")) return "name";
    if (v.includes("before")) return "before";
    if (v.includes("after")) return "after";

    // samples like "USD 123" / "123 USD" / "$123" / "123$"
    if (/\d/.test(v)) {
        const idx = v.indexOf("123");
        if (idx > -1) {
            const leading = v.slice(0, idx).trim();
            const trailing = v.slice(idx + 3).trim();
            const hasLettersLead = /[a-z]/i.test(leading);
            const hasLettersTrail = /[a-z]/i.test(trailing);
            if (
                hasLettersLead &&
                !leading.includes("$") &&
                !leading.includes("€") &&
                !leading.includes("£")
            )
                return "iso-before";
            if (
                hasLettersTrail &&
                !trailing.includes("$") &&
                !trailing.includes("€") &&
                !trailing.includes("£")
            )
                return "iso-after";
            if (leading.length) return "before";
            if (trailing.length) return "after";
        }
    }
    return "before";
}

// Coerce labels like "GMT (UTC+01:00)" / "UTC+01:00" / "GMT Standard Time" → IANA
function coerceToIANA(tz?: string): string {
    const v = String(tz || "").trim();
    if (!v) return "UTC";
    if (/^[A-Za-z]+\/[A-Za-z0-9_\-+]+$/.test(v)) return v;
    if (/^gmt standard time$/i.test(v)) return "Europe/London";

    const m = v.match(/UTC\s*([+-])\s*(\d{1,2})(?::?(\d{2}))?/i);
    if (m) {
        const sign = m[1] === "+" ? "-" : "+";
        const hh = String(parseInt(m[2], 10));
        return `Etc/GMT${sign}${hh}`;
    }
    const m2 = v.match(/GMT\s*([+-])\s*(\d{1,2})(?::?(\d{2}))?/i);
    if (m2) {
        const sign = m2[1] === "+" ? "-" : "+";
        const hh = String(parseInt(m2[2], 10));
        return `Etc/GMT${sign}${hh}`;
    }
    return "UTC";
}

// Parse ISO string | Date | unix seconds | unix ms → Date | null
function parseToDate(input: unknown): Date | null {
    if (input == null) return null;
    if (input instanceof Date) return isNaN(input.getTime()) ? null : input;
    if (typeof input === "number") {
        const ms = input < 1e12 ? input * 1000 : input;
        const d = new Date(ms);
        return isNaN(d.getTime()) ? null : d;
    }
    const s = String(input).trim();
    if (!s) return null;
    if (/^\d+$/.test(s)) {
        const num = Number(s);
        const ms = num < 1e12 ? num * 1000 : num;
        const d = new Date(ms);
        return isNaN(d.getTime()) ? null : d;
    }
    const d = new Date(s);
    return isNaN(d.getTime()) ? null : d;
}

// split number preserving decimals (handles scientific notation)
function splitNumberPreserve(n: number) {
    let s = String(n);
    if (/[eE]/.test(s)) {
        s = n.toFixed(20).replace(/(?:\.0+|(\.\d+?)0+)$/, "$1");
    }
    if (!s.includes(".")) return { int: s, frac: "" };
    const [int, frac] = s.split(".");
    return { int, frac };
}

function groupIntegerWestern(intRaw: string, sep: string) {
    const clean = intRaw.replace(/[,\.\s'${NBSP}${NNBSP}]/g, "");
    return clean.replace(/\B(?=(\d{3})+(?!\d))/g, sep);
}

function groupIntegerIndian(intRaw: string, sep: string) {
    const clean = intRaw.replace(/[,\.\s'${NBSP}${NNBSP}]/g, "");
    if (clean.length <= 3) return clean;
    const last3 = clean.slice(-3);
    const rest = clean.slice(0, -3);
    const pairs = rest.replace(/\B(?=(\d{2})+(?!\d))/g, sep);
    // ensure first pair does not start with a separator
    const out = pairs + sep + last3;
    return out.replace(new RegExp(`^${sep}`), "");
}

function applyDigits(str: string, digitStyle: DigitStyle) {
    if (digitStyle === "latin") return str;
    const maps: Record<DigitStyle, string[]> = {
        latin: "0123456789".split(""),
        arabic: "٠١٢٣٤٥٦٧٨٩".split(""),
        fullwidth: "０１２３４５６７８９".split(""),
    };
    const to = maps[digitStyle];
    return str.replace(/\d/g, (d) => to[Number(d)]);
}

// Format number using pattern metadata, preserving or fixing decimals
function formatWithPattern(
    n: number,
    pattern: string,
    opts?: { preserveDecimals?: boolean; fixedDigits?: number }
) {
    const meta = detectNumberPatternMeta(pattern);
    const { groupSep, decSep, grouping, digitStyle } = meta;

    const neg = n < 0;
    const abs = Math.abs(n);

    let intPart = "";
    let fracPart = "";

    if (opts?.preserveDecimals) {
        const { int, frac } = splitNumberPreserve(abs);
        intPart =
            grouping === "indian"
                ? groupIntegerIndian(int, groupSep)
                : grouping === "western"
                ? groupIntegerWestern(int, groupSep)
                : int;
        fracPart = frac; // unchanged, we only swap the decimal symbol later
    } else {
        const digits = Math.max(0, opts?.fixedDigits ?? 2);
        const fixed = abs.toFixed(digits); // e.g., "1234.50"
        const [int, frac] = fixed.split(".");
        intPart =
            grouping === "indian"
                ? groupIntegerIndian(int, groupSep)
                : grouping === "western"
                ? groupIntegerWestern(int, groupSep)
                : int;
        fracPart = digits > 0 ? frac : "";
    }

    let out = fracPart ? `${intPart}${decSep}${fracPart}` : intPart;
    out = applyDigits(out, digitStyle);
    return neg ? `-${out}` : out;
}

function formatDateParts(d: Date, tz: string, twelveHour: boolean) {
    const safeTZ = coerceToIANA(tz);

    let partsDate: Intl.DateTimeFormatPart[];
    let partsTime: Intl.DateTimeFormatPart[];
    let partsMonthText: Intl.DateTimeFormatPart[];

    try {
        partsDate = new Intl.DateTimeFormat("en-GB", {
            timeZone: safeTZ,
            year: "numeric",
            month: "2-digit",
            day: "2-digit",
        }).formatToParts(d);

        partsTime = new Intl.DateTimeFormat("en-GB", {
            timeZone: safeTZ,
            hour: "2-digit",
            minute: "2-digit",
            hour12: twelveHour,
        }).formatToParts(d);

        partsMonthText = new Intl.DateTimeFormat("en-GB", {
            timeZone: safeTZ,
            year: "numeric",
            month: "short",
            day: "2-digit",
        }).formatToParts(d);
    } catch {
        partsDate = new Intl.DateTimeFormat("en-GB", {
            timeZone: "UTC",
            year: "numeric",
            month: "2-digit",
            day: "2-digit",
        }).formatToParts(d);

        partsTime = new Intl.DateTimeFormat("en-GB", {
            timeZone: "UTC",
            hour: "2-digit",
            minute: "2-digit",
            hour12: twelveHour,
        }).formatToParts(d);

        partsMonthText = new Intl.DateTimeFormat("en-GB", {
            timeZone: "UTC",
            year: "numeric",
            month: "short",
            day: "2-digit",
        }).formatToParts(d);
    }

    const grab = (arr: Intl.DateTimeFormatPart[], type: string) =>
        arr.find((p) => p.type === type)?.value || "";

    const yyyy = grab(partsDate, "year");
    const MM = grab(partsDate, "month");
    const dd = grab(partsDate, "day");
    const monShort = grab(partsMonthText, "month");
    const hh = grab(partsTime, "hour");
    const mm = grab(partsTime, "minute");
    const dayPeriod = grab(partsTime, "dayPeriod");

    return { yyyy, MM, dd, monShort, hh, mm, dayPeriod };
}

function buildDateString(
    df: string,
    yyyy: string,
    MM: string,
    dd: string,
    monShort: string
) {
    const pattern = (df || "yyyy-MM-dd").trim();

    // tokened patterns (support: dd/MM/yyyy, MM/dd/yyyy, yyyy-MM-dd, dd-MM-yyyy, yyyy/MM/dd, dd MMM yyyy, MMM dd, yyyy)
    switch (pattern) {
        case "dd/MM/yyyy":
            return `${dd}/${MM}/${yyyy}`;
        case "MM/dd/yyyy":
            return `${MM}/${dd}/${yyyy}`;
        case "yyyy-MM-dd":
            return `${yyyy}-${MM}-${dd}`;
        case "dd-MM-yyyy":
            return `${dd}-${MM}-${yyyy}`;
        case "yyyy/MM/dd":
            return `${yyyy}/${MM}/${dd}`;
        case "dd MMM yyyy":
            return `${dd} ${monShort} ${yyyy}`;
        case "MMM dd, yyyy":
            return `${monShort} ${dd}, ${yyyy}`;
        default: {
            // fallback: detect delimiter and order by first token
            const sep = pattern.includes("/")
                ? "/"
                : pattern.includes("-")
                ? "-"
                : "/";
            if (pattern.startsWith("dd"))
                return `${dd}${sep}${MM}${sep}${yyyy}`;
            if (pattern.startsWith("MM"))
                return `${MM}${sep}${dd}${sep}${yyyy}`;
            if (pattern.startsWith("yyyy"))
                return `${yyyy}${sep}${MM}${sep}${dd}`;
            return `${yyyy}-${MM}-${dd}`;
        }
    }
}

/** ======================= main composable ======================= **/

export function useFormatters() {
    const page = usePage();

    const formatting = computed(
        () =>
            page.props.formatting ?? {
                // fallbacks only if server didn’t send them
                locale: "en-US",
                dateFormat: "yyyy-MM-dd",
                timeFormat: "24-hour",
                currency: "PKR",
                currencyPosition: "before", // 'before' | 'after' | 'iso-before' | 'iso-after' | 'name'
                timezone: "UTC",
                numberPattern: "1,234.56", // controls grouping + decimal + digits + indian/western
                currencyName: "Rupees", // optional (used when position === 'name')
                currencyISO: "PKR", // optional (used for iso-* positions; defaults to currency)
            }
    );

    const numberPattern = computed(() =>
        String(formatting.value.numberPattern || "1,234.56")
    );
    const currencyStr = computed(() => String(formatting.value.currency ?? ""));
    const currencyName = computed(() =>
        String(formatting.value.currencyName ?? currencyStr.value)
    );
    const currencyISO = computed(() =>
        String(formatting.value.currencyISO ?? currencyStr.value)
    );
    const currencyPos = computed(() =>
        parseCurrencyPosition(formatting.value.currencyPosition)
    );
    const tz = computed(() => String(formatting.value.timezone || "UTC"));
    const dateFormat = computed(() =>
        String(formatting.value.dateFormat || "yyyy-MM-dd")
    );
    const is12h = computed(() =>
        String(formatting.value.timeFormat || "")
            .toLowerCase()
            .includes("12")
    );

    // MONEY: value is in minor units → 2 decimals, grouping from numberPattern, position from DB
    const formatMoney = (minor: number) => {
        const val = (minor ?? 0) / 100;
        const num = formatWithPattern(val, numberPattern.value, {
            preserveDecimals: false,
            fixedDigits: 2,
        });

        switch (currencyPos.value) {
            case "after":
                return `${num} ${currencyStr.value}`.trim();
            case "iso-before":
                return `${currencyISO.value} ${num}`.trim();
            case "iso-after":
                return `${num} ${currencyISO.value}`.trim();
            case "name":
                return `${num} ${currencyName.value}`.trim();
            case "before":
            default:
                return `${currencyStr.value} ${num}`.trim();
        }
    };

    // NUMBER: preserve original decimals; decimal symbol/grouping based on numberPattern
    const formatNumber = (n: number) =>
        formatWithPattern(n ?? 0, numberPattern.value, {
            preserveDecimals: true,
        });

    // DATE/TIME with timezone + chosen patterns
    const dateFmt = (input: string | number | Date) => {
        const d = parseToDate(input);
        if (!d) return "";
        const { yyyy, MM, dd, monShort, hh, mm, dayPeriod } = formatDateParts(
            d,
            tz.value,
            is12h.value
        );
        const dateStr = buildDateString(
            dateFormat.value,
            yyyy,
            MM,
            dd,
            monShort
        );
        const timeStr = is12h.value
            ? `${hh}:${mm}${dayPeriod ? " " + dayPeriod : ""}`
            : `${hh}:${mm}`;
        return `${dateStr} ${timeStr}`;
    };

    // expose as-is for convenience
    const locale = computed(() => String(formatting.value.locale || ""));
    const currency = computed(() => currencyStr.value);
    const timezone = computed(() => tz.value);

    return { formatMoney, formatNumber, dateFmt, locale, currency, timezone };
}
