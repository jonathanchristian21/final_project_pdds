<?php
 
namespace App\Console\Commands;
 
use Illuminate\Console\Command;
use App\Models\DateDimension;
use App\Models\Product;
use App\Models\Location;
use App\Models\SalesFact;
use Carbon\Carbon;
 
class ImportSuperstore extends Command
{
    protected $signature = 'import:superstore {file? : Path to CSV file}';
    protected $description = 'Import Superstore cleaned CSV into MySQL star schema';
 
    // Cache dimensi supaya tidak query DB setiap baris
    private array $dateCache     = [];
    private array $productCache  = [];
    private array $locationCache = [];
 
    public function handle(): void
    {
        $file = $this->argument('file') ?? storage_path('app/private/data/superstore_cleaned.csv');
 
        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            $this->info("Letakkan file di: storage/app/private/data/superstore_cleaned.csv");
            return;
        }
 
        $this->info('Starting import...');
        $this->info('Clearing existing data...');
        $this->truncateTables();
 
        $handle  = fopen($file, 'r');
        $header  = fgetcsv($handle);                          // Baca header row
        $colMap  = array_flip(array_map('trim', $header));    // Map nama kolom ke index
 
        $rows      = [];
        $rowCount  = 0;
        $skipped   = 0;
        $batchSize = 500;
 
        $bar = $this->output->createProgressBar($this->countLines($file));
        $bar->start();
 
        while (($row = fgetcsv($handle)) !== false) {
            // Ambil nilai berdasarkan nama kolom
            // Nama kolom sudah lowercase+underscore hasil cleaning Python
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
 
            // Parse tanggal format YYYY-MM-DD (output dari Python cleaning)
            $orderCarbon = $this->parseDate($orderDate);
            $shipCarbon  = $this->parseDate($shipDate);
 
            if (!$orderCarbon || !$shipCarbon) {
                $this->newLine();
                $this->warn("Skipping row (order_id: {$orderId}): invalid date '{$orderDate}' / '{$shipDate}'");
                $skipped++;
                continue;
            }
 
            // Upsert semua tabel dimensi terlebih dahulu
            $orderDateId = $this->upsertDate($orderCarbon);
            $shipDateId  = $this->upsertDate($shipCarbon);
            $this->upsertProduct($productId, $productName, $category, $subCategory);
            $locationId  = $this->upsertLocation($country, $region, $state, $city);
 
            // Kumpulkan baris fact untuk batch insert
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
 
            // Insert per batch supaya tidak habis memori
            if (count($rows) >= $batchSize) {
                SalesFact::insert($rows);
                $rows = [];
            }
 
            $rowCount++;
            $bar->advance();
        }
 
        // Insert sisa baris yang belum masuk batch terakhir
        if (!empty($rows)) {
            SalesFact::insert($rows);
        }
 
        fclose($handle);
        $bar->finish();
        $this->newLine();
 
        $this->info("Import selesai!");
        $this->table(
            ['Keterangan', 'Jumlah'],
            [
                ['Baris berhasil diimport', $rowCount],
                ['Baris dilewati (error)',  $skipped],
                ['Date dimensions',         count($this->dateCache)],
                ['Products',                count($this->productCache)],
                ['Locations',               count($this->locationCache)],
            ]
        );
    }
 
    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------
 
    private function truncateTables(): void
    {
        // Fact table dulu sebelum dimensi karena ada FK constraint
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');
        SalesFact::truncate();
        DateDimension::truncate();
        Product::truncate();
        Location::truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
 
    /**
     * Parse tanggal format YYYY-MM-DD (output dari Python cleaning).
     * Return null jika tidak valid.
     */
    private function parseDate(string $raw): ?Carbon
    {
        try {
            return Carbon::createFromFormat('Y-m-d', $raw);
        } catch (\Exception $e) {
            return null;
        }
    }
 
    /**
     * Buat date_id integer berformat YYYYMMDD dari Carbon.
     */
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
                    'month_name' => $date->format('F'),  // e.g. "November"
                    'quarter'    => $date->quarter,
                    'year'       => $date->year,
                ]
            );
            $this->dateCache[$dateId] = true;
        }
 
        return $dateId;
    }
 
    private function upsertProduct(
        string $productId,
        string $productName,
        string $category,
        string $subCategory
    ): void {
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
 
    private function upsertLocation(
        string $country,
        string $region,
        string $state,
        string $city
    ): int {
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
 
    /**
     * Hitung jumlah baris data (minus header) untuk progress bar.
     */
    private function countLines(string $file): int
    {
        $count  = 0;
        $handle = fopen($file, 'r');
        fgetcsv($handle); // Skip header
        while (fgetcsv($handle) !== false) {
            $count++;
        }
        fclose($handle);
        return $count;
    }
}
