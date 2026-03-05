<?php

namespace Database\Seeders;

use App\Models\DomesticMetalPrice;
use App\Models\RewardProduct;
use Illuminate\Database\Seeder;

class RewardProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 현재 금 시세 조회
        $goldPrice = DomesticMetalPrice::getLatest();
        $goldPricePerGram = $goldPrice ? $goldPrice->s_pure / 3.75 : 22666.67;

        $this->command->info("Current gold price per gram: {$goldPricePerGram} KRW");

        $products = [
            // 기프티콘 카테고리
            [
                'name' => '스타벅스 아메리카노 Tall',
                'description' => '스타벅스 아메리카노 Tall 사이즈 기프티콘',
                'image_url' => 'https://image.istarbucks.co.kr/upload/store/skuimg/2021/04/[9200000002487]_20210415133440467.jpg',
                'price' => 4500,
                'gold_grams' => 4500 / $goldPricePerGram,
                'stock' => 100,
                'is_active' => false,
                'priority' => 100,
                'category' => '기프티콘',
            ],
            [
                'name' => '컴포즈커피 아메리카노',
                'description' => '컴포즈커피 아메리카노 (ICE/HOT)',
                'image_url' => 'https://composecoffee.com/wp-content/uploads/2021/03/menu_01.png',
                'price' => 1500,
                'gold_grams' => 1500 / $goldPricePerGram,
                'stock' => 200,
                'is_active' => false,
                'priority' => 90,
                'category' => '기프티콘',
            ],
            [
                'name' => 'GS25 모바일 상품권 5천원',
                'description' => 'GS25 편의점에서 사용 가능한 5천원 상품권',
                'image_url' => 'https://via.placeholder.com/300x300?text=GS25',
                'price' => 5000,
                'gold_grams' => 5000 / $goldPricePerGram,
                'stock' => 150,
                'is_active' => false,
                'priority' => 80,
                'category' => '기프티콘',
            ],
            [
                'name' => 'CU 모바일 상품권 5천원',
                'description' => 'CU 편의점에서 사용 가능한 5천원 상품권',
                'image_url' => 'https://via.placeholder.com/300x300?text=CU',
                'price' => 5000,
                'gold_grams' => 5000 / $goldPricePerGram,
                'stock' => 150,
                'is_active' => false,
                'priority' => 80,
                'category' => '기프티콘',
            ],

            // 상품권 카테고리
            [
                'name' => '해피머니 상품권 1만원',
                'description' => '전국 가맹점에서 사용 가능한 해피머니 상품권',
                'image_url' => 'https://via.placeholder.com/300x300?text=HappyMoney',
                'price' => 10000,
                'gold_grams' => 10000 / $goldPricePerGram,
                'stock' => 50,
                'is_active' => false,
                'priority' => 70,
                'category' => '상품권',
            ],
            [
                'name' => '컬쳐랜드 상품권 1만원',
                'description' => '도서, 공연, 영화 등에 사용 가능한 컬쳐랜드 상품권',
                'image_url' => 'https://via.placeholder.com/300x300?text=Cultureland',
                'price' => 10000,
                'gold_grams' => 10000 / $goldPricePerGram,
                'stock' => 50,
                'is_active' => false,
                'priority' => 70,
                'category' => '상품권',
            ],

            // 치킨 카테고리
            [
                'name' => 'BBQ 황금올리브 치킨',
                'description' => 'BBQ 황금올리브 순살치킨 기프티콘',
                'image_url' => 'https://via.placeholder.com/300x300?text=BBQ',
                'price' => 18000,
                'gold_grams' => 18000 / $goldPricePerGram,
                'stock' => 30,
                'is_active' => false,
                'priority' => 60,
                'category' => '치킨',
            ],
            [
                'name' => 'BHC 뿌링클 치킨',
                'description' => 'BHC 뿌링클 치킨 기프티콘',
                'image_url' => 'https://via.placeholder.com/300x300?text=BHC',
                'price' => 18000,
                'gold_grams' => 18000 / $goldPricePerGram,
                'stock' => 30,
                'is_active' => false,
                'priority' => 60,
                'category' => '치킨',
            ],

            // 실물 금 카테고리
            [
                'name' => '순금 1돈 (3.75g)',
                'description' => '한국금거래소 공식 인증 순금 1돈',
                'image_url' => 'https://www.koreagoldx.co.kr/data/product/99999-%EB%8C%80%ED%91%9C%EC%9D%B4%EB%AF%B8%EC%A7%80.jpg',
                'price' => $goldPricePerGram * 3.75,
                'gold_grams' => 3.75,
                'stock' => 10,
                'is_active' => true,
                'priority' => 50,
                'category' => '실물금',
            ],
            [
                'name' => '순금 5돈 (18.75g)',
                'description' => '한국금거래소 공식 인증 순금 5돈',
                'image_url' => 'https://via.placeholder.com/300x300?text=Gold+5+Don',
                'price' => $goldPricePerGram * 18.75,
                'gold_grams' => 18.75,
                'stock' => 5,
                'is_active' => false,
                'priority' => 40,
                'category' => '실물금',
            ],
        ];

        foreach ($products as $product) {
            RewardProduct::create($product);
            $this->command->info("✓ Created: {$product['name']} ({$product['price']} points)");
        }

        $this->command->newLine();
        $this->command->info('✓ Created '.count($products).' reward products');
    }
}
