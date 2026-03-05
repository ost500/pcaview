<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 리워드 상품 모델
 *
 * @property int $id
 * @property string $name 상품명
 * @property string|null $description 상품 설명
 * @property string|null $image_url 상품 이미지
 * @property float $price 가격 (포인트)
 * @property float|null $gold_grams 금 그램 환산
 * @property int $stock 재고 수량
 * @property bool $is_active 활성화 여부
 * @property int $priority 정렬 순서 (높을수록 상단)
 * @property string|null $category 카테고리
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class RewardProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'image_url',
        'price',
        'gold_grams',
        'stock',
        'is_active',
        'priority',
        'category',
    ];

    protected $casts = [
        'price' => 'decimal:9',
        'gold_grams' => 'decimal:9',
        'stock' => 'integer',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    protected $attributes = [
        'stock' => 0,
        'is_active' => true,
        'priority' => 0,
    ];

    /**
     * 활성화된 상품만 조회
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 재고가 있는 상품만 조회
     */
    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    /**
     * 카테고리별 조회
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
