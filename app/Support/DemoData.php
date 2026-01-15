<?php

namespace App\Support;

use Symfony\Component\Yaml\Yaml;

class DemoData
{
    /**
     * Demo data cache
     */
    private static ?array $data = null;

    /**
     * YAML dosyasının tam yolu
     */
    private static function getFilePath(): string
    {
        return database_path('demo/demo_data.yaml');
    }

    /**
     * Demo verileri yükle (cache'li)
     */
    public static function load(): array
    {
        if (self::$data === null) {
            $filePath = self::getFilePath();
            
            if (!file_exists($filePath)) {
                throw new \RuntimeException("Demo data dosyası bulunamadı: {$filePath}");
            }
            
            self::$data = Yaml::parseFile($filePath);
        }
        
        return self::$data;
    }

    /**
     * Belirli bir bölümü getir
     */
    public static function get(string $key): mixed
    {
        $data = self::load();
        
        if (!isset($data[$key])) {
            throw new \RuntimeException("Demo data içinde '{$key}' anahtarı bulunamadı.");
        }
        
        return $data[$key];
    }

    /**
     * Müşteri listesi
     */
    public static function customers(): array
    {
        return self::get('customers');
    }

    /**
     * Tekne listesi
     */
    public static function vessels(): array
    {
        return self::get('vessels');
    }

    /**
     * Tekne kontak default'ları
     */
    public static function vesselContactsDefaults(): array
    {
        return self::get('vessel_contacts_defaults');
    }

    /**
     * Quote items kataloğu
     */
    public static function quoteItemsCatalog(): array
    {
        return self::get('quote_items_catalog');
    }

    /**
     * Döküman planı (kaç tane quote/order/contract oluşturulacak)
     */
    public static function documentsPlan(): array
    {
        return self::get('documents_plan');
    }

    /**
     * Para birimi
     */
    public static function currency(): string
    {
        return self::get('currency');
    }

    /**
     * Müşteriyi key'e göre bul
     */
    public static function findCustomer(string $key): ?array
    {
        foreach (self::customers() as $customer) {
            if ($customer['key'] === $key) {
                return $customer;
            }
        }
        
        return null;
    }

    /**
     * Tekneyi key'e göre bul
     */
    public static function findVessel(string $key): ?array
    {
        foreach (self::vessels() as $vessel) {
            if ($vessel['key'] === $key) {
                return $vessel;
            }
        }
        
        return null;
    }

    /**
     * Belirli bir müşteriye ait tekneleri getir
     */
    public static function vesselsForCustomer(string $customerKey): array
    {
        return array_filter(
            self::vessels(),
            fn($vessel) => $vessel['owner'] === $customerKey
        );
    }

    /**
     * Katalog item'ını code'a göre bul
     */
    public static function findCatalogItem(string $code): ?array
    {
        foreach (self::quoteItemsCatalog() as $item) {
            if ($item['code'] === $code) {
                return $item;
            }
        }
        
        return null;
    }

    /**
     * Cache'i temizle (test için kullanışlı)
     */
    public static function clearCache(): void
    {
        self::$data = null;
    }
}
