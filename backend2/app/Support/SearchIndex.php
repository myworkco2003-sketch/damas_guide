<?php

namespace App\Support;

class SearchIndex
{
    /** علامات التشكيل والتطويل */
    private const DIACRITICS = '/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u';

    /** البوادئ المكانية والإدارية الشائعة */
    private const SPATIAL_PREFIXES = '/^(?:شارع|طريق|جاده|جادة|حي|بلديه|بلدية|دائره خدمات|دائرة خدمات|دائره|دائرة|منطقه|منطقة|سرفيس|سيرفيس|خط|حاره|حارة|سوق|مستشفي|مستشفى|مشفي|مشفى|جامع|مسجد|حديقه|حديقة|مركز)\s+/iu';

    /** أسماء مركبة شهيرة */
    private const COMPOUND_PREFIXES = ['ابو', 'كفر', 'باب', 'دير', 'تل', 'عين', 'نهر', 'خان'];

    /** يطابق [ء-ي] في Python */
    private const AR = '\x{0621}-\x{064A}';

    public static function normalize(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        if (class_exists(\Normalizer::class)) {
            $text = \Normalizer::normalize($text, \Normalizer::FORM_KC) ?: $text;
        }

        $text = preg_replace(self::DIACRITICS, '', $text);
        $text = preg_replace('/[أإآٱ]/u', 'ا', $text);
        $text = str_replace('ة', 'ه', $text);
        $text = str_replace('ى', 'ي', $text);
        $text = preg_replace('/\s+/u', ' ', $text);
        $text = preg_replace('/^\s+|\s+$/u', '', $text);

        return mb_strtolower($text);
    }

    /**
     * يبني نص الفهرسة: SearchIndex::build($nameAr, $nameEn, $parentName, $code)
     */
    public static function build(mixed ...$parts): string
    {
        $parts = array_filter($parts, fn ($p) => $p !== null && $p !== '' && $p !== false && $p !== 0);
        $norm = self::normalize(implode(' ', array_map('strval', $parts)));

        if ($norm === '') {
            return '';
        }

        $tokens = self::tokens($norm);
        $extras = [];

        // 1. توسيع الكلمات المركبة
        foreach (self::COMPOUND_PREFIXES as $cp) {
            if (str_contains($norm, $cp.' ')) {
                $extras[] = str_replace($cp.' ', $cp, $norm);
            }
            $joined = '/\b'.$cp.'(?=['.self::AR.'])/u';
            if (preg_match($joined, $norm)) {
                $extras[] = preg_replace($joined, $cp.' ', $norm);
            }
        }

        // 2. إضافة الكلمات بدون "ال"
        $noAl = [];
        foreach ($tokens as $t) {
            if (str_starts_with($t, 'ال') && mb_strlen($t) > 3) {
                $noAl[] = mb_substr($t, 2);
                $extras[] = mb_substr($t, 2);
            } else {
                $noAl[] = $t;
            }
        }
        if ($noAl !== $tokens) {
            $extras[] = implode(' ', $noAl);
        }

        $all = array_values(array_unique(array_merge([$norm], $extras)));

        return mb_substr(implode(' ', $all), 0, 500);
    }

    /**
     * مشتقات البحث (generate_search_variants). مرتبة الأطول أولاً.
     *
     * @return list<string>
     */
    public static function variants(?string $query): array
    {
        if ($query === null || $query === '') {
            return [];
        }

        $rawNorm = self::normalize($query);
        if ($rawNorm === '' || mb_strlen($rawNorm) < 2) {
            return $rawNorm !== '' ? [$rawNorm] : [];
        }

        $variants = [$rawNorm => true];

        // 1. إزالة البوادئ الإدارية
        $noPrefix = preg_replace('/^\s+|\s+$/u', '', preg_replace(self::SPATIAL_PREFIXES, '', $rawNorm, 1));
        if ($noPrefix !== '' && mb_strlen($noPrefix) >= 2) {
            $variants[$noPrefix] = true;
        }

        // 2. مشفى / مستشفى
        foreach (array_keys($variants) as $v) {
            if (str_contains($v, 'مستشفي')) {
                $variants[str_replace('مستشفي', 'مشفي', $v)] = true;
            } elseif (str_contains($v, 'مشفي')) {
                $variants[str_replace('مشفي', 'مستشفي', $v)] = true;
            }
        }

        // 3. ساروجة
        foreach (array_keys($variants) as $v) {
            foreach (['ساروجه', 'ساروجا', 'ساروحه'] as $w) {
                if (str_contains($v, $w)) {
                    $variants['ساروجه'] = true;
                    $variants['ساروجا'] = true;
                    $variants['ساروج'] = true;
                    break;
                }
            }
        }

        // 4. الكلمات المركبة
        foreach (array_keys($variants) as $v) {
            $v = (string) $v;
            foreach (self::COMPOUND_PREFIXES as $cp) {
                if (str_contains($v, $cp.' ')) {
                    $variants[str_replace($cp.' ', $cp, $v)] = true;
                }
                $joined = '/\b'.$cp.'(?=['.self::AR.'])/u';
                if (preg_match($joined, $v)) {
                    $variants[preg_replace($joined, $cp.' ', $v)] = true;
                }

                $pattern = '/\b'.$cp.'\s+(?:ال)?(['.self::AR.']+)/u';
                if (preg_match($pattern, $v, $m)) {
                    $base = $m[1];
                    $variants[preg_replace($pattern, $cp.' '.$base, $v)] = true;
                    $variants[preg_replace($pattern, $cp.' ال'.$base, $v)] = true;
                    $variants[preg_replace($pattern, $cp.$base, $v)] = true;
                    $variants[preg_replace($pattern, $cp.'ال'.$base, $v)] = true;
                }
            }
        }

        // 5. أداة التعريف
        foreach (array_keys($variants) as $v) {
            $v = (string) $v;
            $tokens = self::tokens($v);

            $stripped = array_map(
                fn ($t) => str_starts_with($t, 'ال') && mb_strlen($t) > 3 ? mb_substr($t, 2) : $t,
                $tokens
            );
            $noAlPhrase = implode(' ', $stripped);
            if (mb_strlen($noAlPhrase) >= 2) {
                $variants[$noAlPhrase] = true;
            }

            if (
                $tokens !== []
                && ! str_starts_with($tokens[0], 'ال')
                && mb_strlen($tokens[0]) >= 3
                && ! in_array($tokens[0], self::COMPOUND_PREFIXES, true)
            ) {
                $withAl = $tokens;
                $withAl[0] = 'ال'.$tokens[0];
                $variants[implode(' ', $withAl)] = true;
            }
        }

        $list = [];
        foreach (array_keys($variants) as $x) {
            $x = preg_replace('/^\s+|\s+$/u', '', (string) $x);
            if (mb_strlen($x) >= 2) {
                $list[] = $x;
            }
        }
        usort($list, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        return $list;
    }

    /** @return list<string> */
    private static function tokens(string $text): array
    {
        return preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }
}
