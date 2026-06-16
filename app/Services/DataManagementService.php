<?php

namespace App\Services;

use App\Models\DateDimension;
use App\Models\Product;
use App\Models\Location;
use App\Models\SalesFact;
use App\Models\Insight;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class DataManagementService
{
    private array $dateCache     = [];
    private array $productCache  = [];
    private array $locationCache = [];

    public function uploadData(string $filePath): void
    {
        set_time_limit(0); // Allow long execution

        $this->clearData();

        $handle  = fopen($filePath, 'r');
        $header  = fgetcsv($handle);
        $colMap  = array_flip(array_map('trim', $header));

        $rows      = [];
        $batchSize = 500;

        while (($row = fgetcsv($handle)) !== false) {
            $orderId     = trim($row[$colMap['order_id']]);
            $orderDate   = trim($row[$colMap['order_date']]);
            $shipDate    = trim($row[$colMap['ship_date']]);
            $productId   = trim($row[$colMap['product_id']]);
            $productName = trim($row[$colMap['product_name']]);
            $category    = trim($row[$colMap['category']]);
            $subCategory = trim($row[$colMap['sub_category']]);
            $country     = trim($row[$colMap['country']]);
            $city        = trim($row[$colMap['city']]);
            $state       = trim($row[$colMap['state']]);
            $region      = trim($row[$colMap['region']]);
            $sales       = (float) $row[$colMap['sales']];
            $quantity    = (int)   $row[$colMap['quantity']];
            $discount    = (float) $row[$colMap['discount']];
            $profit      = (float) $row[$colMap['profit']];

            $orderCarbon = $this->parseDate($orderDate);
            $shipCarbon  = $this->parseDate($shipDate);

            if (!$orderCarbon || !$shipCarbon) {
                continue;
            }

            $orderDateId = $this->upsertDate($orderCarbon);
            $shipDateId  = $this->upsertDate($shipCarbon);
            $this->upsertProduct($productId, $productName, $category, $subCategory);
            $locationId  = $this->upsertLocation($country, $region, $state, $city);

            $rows[] = [
                'order_id'      => $orderId,
                'product_id'    => $productId,
                'location_id'   => $locationId,
                'order_date_id' => $orderDateId,
                'ship_date_id'  => $shipDateId,
                'sales'         => $sales,
                'quantity'      => $quantity,
                'discount'      => $discount,
                'profit'        => $profit,
            ];

            if (count($rows) >= $batchSize) {
                SalesFact::insert($rows);
                $rows = [];
            }
        }

        if (!empty($rows)) {
            SalesFact::insert($rows);
        }

        fclose($handle);

        // Regenerate insights to MongoDB automatically
        Artisan::call('generate:insights');
    }

    public function clearData(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        SalesFact::truncate();
        DateDimension::truncate();
        Product::truncate();
        Location::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        Insight::truncate();
    }

    private function parseDate(string $raw): ?Carbon
    {
        try {
            return Carbon::createFromFormat('Y-m-d', $raw);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function makeDateId(Carbon $date): int
    {
        return (int) $date->format('Ymd');
    }

    private function upsertDate(Carbon $date): int
    {
        $dateId = $this->makeDateId($date);
        if (!isset($this->dateCache[$dateId])) {
            DateDimension::firstOrCreate(
                ['date_id' => $dateId],
                [
                    'full_date'  => $date->toDateString(),
                    'day'        => $date->day,
                    'month'      => $date->month,
                    'month_name' => $date->format('F'),
                    'quarter'    => $date->quarter,
                    'year'       => $date->year,
                ]
            );
            $this->dateCache[$dateId] = true;
        }
        return $dateId;
    }

    private function upsertProduct(string $productId, string $productName, string $category, string $subCategory): void
    {
        if (!isset($this->productCache[$productId])) {
            Product::firstOrCreate(
                ['product_id' => $productId],
                [
                    'product_name' => $productName,
                    'category'     => $category,
                    'sub_category' => $subCategory,
                ]
            );
            $this->productCache[$productId] = true;
        }
    }

    private function upsertLocation(string $country, string $region, string $state, string $city): int
    {
        $key = "{$city}|{$state}|{$region}";
        if (!isset($this->locationCache[$key])) {
            $location = Location::firstOrCreate(
                ['city' => $city, 'state' => $state, 'region' => $region],
                ['country' => $country]
            );
            $this->locationCache[$key] = $location->location_id;
        }
        return $this->locationCache[$key];
    }
}
