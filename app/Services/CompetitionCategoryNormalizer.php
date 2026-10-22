<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\Config;

/**
 * 把賽事的報名資料（competition_applications）與賽事的組別／量級設定對齊。
 *
 * 背景：早期由 Excel 匯入的成績，報名資料的 category 會是「男子公開組」、
 * weight 會是「-60kg」，gender 是空的；而賽果頁是用賽事自己的
 * categories_weights（code：public / 60-）去篩選運動員，
 * 兩邊格式不一致就會出現「選了組別卻沒有運動員」的情況。
 *
 * 這個類別會：
 * 1. 從 category 文字解析性別（男子／女子／男女混合）並移除前綴
 * 2. 把文字對應到賽事既有的組別／量級 code（對不到就新增一筆）
 * 3. 寫回報名資料，確保賽果頁可以正常篩選
 */
class CompetitionCategoryNormalizer
{
    /**
     * 正規化一場賽事，回傳統計。
     *
     * @return array{categories:int, weights:int, applications:int}
     */
    public function normalize(Competition $competition, bool $save = true): array
    {
        $categories = $this->decode($competition->categories_weights) ?: [];
        $stats = ['categories' => 0, 'weights' => 0, 'applications' => 0, 'linked' => 0];

        foreach ($competition->applications()->get() as $application) {
            $rawCategory = trim((string) $application->category);
            $rawWeight = trim((string) $application->weight);

            if ($rawCategory === '' && $rawWeight === '') {
                continue;
            }

            $gender = $this->genderOf($rawCategory, $application->gender);
            $label = $this->stripGender($rawCategory);

            [$categoryCode, $created] = $this->ensureCategory($categories, $rawCategory, $label);
            $stats['categories'] += $created;

            // 組別文字沒有性別時，先看量級文字（MEN-60kg / WOMEN-57kg），再看設定
            if (! $gender && $rawWeight !== '') {
                $gender = $this->genderOfWeight($rawWeight);
            }

            if (! $gender && $rawWeight !== '') {
                $gender = $this->inferGenderFromWeight($categories, $categoryCode, $rawWeight);
            }

            $weightCode = null;
            if ($rawWeight !== '') {
                [$weightCode, $weightCreated, $linked] = $this->ensureWeight(
                    $categories,
                    $categoryCode,
                    $gender,
                    $rawWeight
                );
                $stats['weights'] += $weightCreated;
                $stats['linked'] += $linked;
            }

            $changed = false;

            if ($categoryCode && $application->category !== $categoryCode) {
                $application->category = $categoryCode;
                $changed = true;
            }

            if ($weightCode && $application->weight !== $weightCode) {
                $application->weight = $weightCode;
                $changed = true;
            }

            if ($gender && $application->gender !== $gender) {
                $application->gender = $gender;
                $changed = true;
            }

            if ($changed) {
                $application->save();
                $stats['applications']++;
            }
        }

        if ($save && ($stats['categories'] || $stats['weights'] || $stats['applications'] || $stats['linked'])) {
            // 與既有資料一致：存成 JSON 字串，讓 json cast 再編碼一次（雙重編碼）
            $competition->categories_weights = json_encode(array_values($categories));
            $competition->save();
        }

        return $stats;
    }

    /**
     * 找對應的組別；找不到就新增。
     *
     * @return array{0:string,1:int} [code, 新增筆數]
     */
    private function ensureCategory(array &$categories, string $rawCode, string $label): array
    {
        foreach ($categories as $category) {
            $category = (array) $category;

            if ($rawCode !== '' && (string) ($category['code'] ?? '') === $rawCode) {
                return [$rawCode, 0];
            }

            $name = trim((string) ($category['name'] ?? ''));

            if ($label !== '' && $name === $label) {
                return [(string) ($category['code'] ?? $label), 0];
            }

            if ($rawCode !== '' && $name === $rawCode) {
                return [(string) ($category['code'] ?? $rawCode), 0];
            }
        }

        $code = $label !== '' ? $label : $rawCode;

        $categories[] = [
            'code' => $code,
            'name' => $code,
            'description' => '',
            'male' => [],
            'female' => [],
            'mixed' => [],
        ];

        return [$code, 1];
    }

    /**
     * 找對應的量級；找不到就新增到該組別的性別清單。
     *
     * @return array{0:string|null,1:int,2:int} [code, 新增筆數, 從其他性別清單搬過來的筆數]
     */
    private function ensureWeight(array &$categories, string $categoryCode, ?string $gender, string $rawWeight): array
    {
        $listKey = $gender === 'F' ? 'female' : ($gender === 'X' ? 'mixed' : 'male');
        $needle = $this->normalizeText($rawWeight);

        foreach ($categories as $index => $category) {
            $category = (array) $category;

            if ((string) ($category['code'] ?? '') !== $categoryCode) {
                continue;
            }

            // 只比對「該性別」的清單
            foreach ((array) ($category[$listKey] ?? []) as $weight) {
                $weight = (array) $weight;

                if (
                    $this->normalizeText((string) ($weight['code'] ?? '')) === $needle ||
                    $this->normalizeText((string) ($weight['name'] ?? '')) === $needle
                ) {
                    return [(string) ($weight['code'] ?? $rawWeight), 0, 0];
                }
            }

            // 其他性別的清單有同一個量級：搬一份到這個性別，賽果頁才看得到
            foreach (['male', 'female', 'mixed'] as $key) {
                if ($key === $listKey) {
                    continue;
                }

                foreach ((array) ($category[$key] ?? []) as $weight) {
                    $weight = (array) $weight;

                    if (
                        $this->normalizeText((string) ($weight['code'] ?? '')) === $needle ||
                        $this->normalizeText((string) ($weight['name'] ?? '')) === $needle
                    ) {
                        $categories[$index][$listKey] = array_values((array) ($category[$listKey] ?? []));
                        $categories[$index][$listKey][] = [
                            'code' => $weight['code'] ?? $rawWeight,
                            'name' => $weight['name'] ?? $this->prettifyWeight($rawWeight),
                            'limit' => $weight['limit'] ?? null,
                        ];

                        return [(string) ($weight['code'] ?? $rawWeight), 0, 1];
                    }
                }
            }

            // 該組別還沒有這個量級，補在對應的性別清單
            $categories[$index][$listKey] = array_values((array) ($category[$listKey] ?? []));
            $categories[$index][$listKey][] = [
                'code' => $rawWeight,
                'name' => $this->prettifyWeight($rawWeight),
                'limit' => null,
            ];

            return [$rawWeight, 1, 0];
        }

        return [null, 0, 0];
    }

    /**
     * 從組別文字判斷性別：男子 / 女子 / 男女混合。
     */
    private function genderOf(string $category, ?string $current): ?string
    {
        if (str_contains($category, '混合')) {
            return 'X';
        }

        if (str_contains($category, '女子') || str_contains($category, '女')) {
            return 'F';
        }

        if (str_contains($category, '男子') || str_contains($category, '男')) {
            return 'M';
        }

        return $current ?: null;
    }

    /**
     * 量級文字自帶性別：MEN-60kg / WOMEN-57kg / BOYS / GIRLS。
     */
    private function genderOfWeight(string $weight): ?string
    {
        if (preg_match('/WOMEN|WOMAN|GIRL/i', $weight)) {
            return 'F';
        }

        if (preg_match('/^MEN|BOYS?\b/i', $weight)) {
            return 'M';
        }

        return null;
    }

    /**
     * 組別文字沒有性別時，用量級去男／女清單裡找。
     */
    private function inferGenderFromWeight(array $categories, string $categoryCode, string $rawWeight): ?string
    {
        $needle = $this->normalizeText($rawWeight);

        $search = function (array $category) use ($needle) {
            foreach (['male' => 'M', 'female' => 'F', 'mixed' => 'X'] as $key => $gender) {
                foreach ((array) ($category[$key] ?? []) as $weight) {
                    $weight = (array) $weight;

                    if (
                        $this->normalizeText((string) ($weight['code'] ?? '')) === $needle ||
                        $this->normalizeText((string) ($weight['name'] ?? '')) === $needle
                    ) {
                        return $gender;
                    }
                }
            }

            return null;
        };

        foreach ($categories as $category) {
            $category = (array) $category;
            if ((string) ($category['code'] ?? '') === $categoryCode) {
                $gender = $search($category);
                if ($gender) {
                    return $gender;
                }
            }
        }

        // 賽事自己沒有設定時，用系統標準的組別／量級設定推斷
        foreach ((array) (Config::items('categories_weights') ?: []) as $category) {
            $category = (array) $category;
            if (
                (string) ($category['code'] ?? '') === $categoryCode ||
                (string) ($category['name'] ?? '') === $categoryCode
            ) {
                $gender = $search($category);
                if ($gender) {
                    return $gender;
                }
            }
        }

        return null;
    }

    /**
     * 移除「男子／女子／男女混合」前綴，只留組別名稱。
     */
    private function stripGender(string $category): string
    {
        $label = preg_replace('/^(男女混合|男女子|男子|女子|男女)/u', '', $category);

        return trim((string) $label);
    }

    /**
     * 比對用的正規化：去空白、轉小寫、kg 統一。
     */
    private function normalizeText(string $value): string
    {
        $value = str_replace([' ', '　', 'ＫＧ', 'KG', 'Kg', 'kg'], ['', '', 'kg', 'kg', 'kg', 'kg'], $value);

        return mb_strtolower($value);
    }

    /**
     * 量級顯示名稱：-60kg → -60Kg、OPEN → 無限量級。
     */
    private function prettifyWeight(string $weight): string
    {
        if (in_array(mb_strtoupper($weight), ['OPEN', '無限量級', '無限'], true)) {
            return '無限量級';
        }

        // 去掉 MEN- / WOMEN- 之類的前綴再顯示
        $weight = preg_replace('/^(MEN|WOMEN|WOMAN|BOYS|GIRLS)[-\s]*/i', '', $weight);

        return preg_replace('/kg/i', 'Kg', (string) $weight);
    }

    private function decode($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_string($decoded) ? json_decode($decoded, true) : $decoded;
        }

        return $value;
    }
}
