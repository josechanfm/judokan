<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 每月運動員訓練記錄。
 *
 * 四項次數：中心/集訓、得勝、集訓（節數）、比賽日數；
 * 積分 = 每項次數 × 該項設定的分數（屬會可在頁面上設定）。
 */
class TrainingRecord extends Model
{
    use HasFactory;

    public const POINT_FIELDS = [
        'centre_count',
        'win_count',
        'session_count',
        'competition_day_count',
    ];

    protected $fillable = [
        'organization_id',
        'member_id',
        'month',
        'centre_count',
        'win_count',
        'session_count',
        'competition_day_count',
    ];

    protected $casts = ['month' => 'date'];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * 未設定時的預設分數（每項 1 分）。
     */
    public static function defaultRates(): array
    {
        return array_fill_keys(self::POINT_FIELDS, 1);
    }

    /**
     * 讀取屬會的計分設定（未設定時用預設值）。
     */
    public static function ratesFor(int $organizationId): array
    {
        $config = Config::where('organization_id', $organizationId)
            ->where('key', 'training_point_rates')
            ->first();

        $stored = $config ? json_decode($config->value, true) : [];

        return self::normalizeRates(is_array($stored) ? $stored : []);
    }

    /**
     * 儲存屬會的計分設定。
     */
    public static function storeRates(int $organizationId, array $rates): void
    {
        Config::updateOrCreate(
            ['organization_id' => $organizationId, 'key' => 'training_point_rates'],
            ['value' => json_encode(self::normalizeRates($rates))]
        );
    }

    /**
     * 只保留四項分數，缺漏或無效值時用預設值（1 分）。
     */
    public static function normalizeRates(array $rates): array
    {
        $defaults = self::defaultRates();
        $normalized = [];

        foreach (self::POINT_FIELDS as $field) {
            $value = $rates[$field] ?? null;
            $normalized[$field] = is_numeric($value) && (float) $value >= 0
                ? (int) round((float) $value)
                : $defaults[$field];
        }

        return $normalized;
    }

    /**
     * 這一筆記錄可得的積分。
     */
    public function pointsFor(array $rates): int
    {
        $total = 0;

        foreach (self::POINT_FIELDS as $field) {
            $total += (int) $this->{$field} * (int) ($rates[$field] ?? 0);
        }

        return $total;
    }

    /**
     * 四項次數是否全為 0。
     */
    public function isEmpty(): bool
    {
        foreach (self::POINT_FIELDS as $field) {
            if ((int) $this->{$field} !== 0) {
                return false;
            }
        }

        return true;
    }
}
