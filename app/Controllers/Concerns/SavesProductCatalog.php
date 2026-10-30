<?php

namespace App\Controllers\Concerns;

use App\Models\ProductImageModel;
use App\Models\ProductVariantModel;

trait SavesProductCatalog
{
    protected function catalogProductPayload(): array
    {
        $brand      = trim((string) $this->request->getPost('brand'));
        $warranty   = trim((string) $this->request->getPost('warranty_info'));
        $highlights = trim((string) $this->request->getPost('highlights'));
        $specs      = trim((string) $this->request->getPost('specifications'));
        $sizeGuide  = trim((string) $this->request->getPost('size_guide'));
        $compare    = $this->request->getPost('compare_at_price');
        $returnDays = $this->request->getPost('return_days');

        $categoryId = (int) $this->request->getPost('category_id');
        if ($categoryId <= 0 || ! (new \App\Models\CategoryModel())->exists($categoryId)) {
            $categoryId = 0;
        }

        return [
            'category_id'      => $categoryId,
            'description'      => $this->request->getPost('description'),
            'highlights'       => $highlights !== '' ? $highlights : null,
            'specifications'   => $specs !== '' ? $specs : null,
            'size_guide'       => $sizeGuide !== '' ? $sizeGuide : null,
            'warranty_info'    => $warranty !== '' ? $warranty : null,
            'return_days'      => ($returnDays === '' || $returnDays === null) ? 7 : max(0, (int) $returnDays),
            'price'            => (float) $this->request->getPost('price'),
            'compare_at_price' => ($compare !== '' && $compare !== null) ? (float) $compare : null,
            'stock'            => (int) $this->request->getPost('stock'),
            'brand'            => $brand !== '' ? $brand : null,
            'cashback_percent' => $this->postedCashbackPercent(),
        ];
    }

    protected function postedCashbackPercent(): float
    {
        $cashback = $this->request->getPost('cashback_percent');
        if ($cashback === '' || $cashback === null) {
            return 0.0;
        }

        return max(0.0, min(100.0, (float) $cashback));
    }

    protected function persistPostedCashback(int $productId): void
    {
        if ($productId <= 0) {
            return;
        }
        $model = $this->productModel ?? new \App\Models\ProductModel();
        $model->persistCashback($productId, $this->postedCashbackPercent());
    }

    protected function imageHasSort(): bool
    {
        try {
            return \Config\Database::connect()->fieldExists('sort_order', 'product_images');
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function imageHasMediaType(): bool
    {
        try {
            return \Config\Database::connect()->fieldExists('media_type', 'product_images');
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function clientMediaType($file): string
    {
        if (! $file) {
            return 'image';
        }
        $mime = '';
        $ext  = '';
        try {
            $mime = strtolower((string) $file->getClientMimeType());
        } catch (\Throwable $e) {
            $mime = '';
        }
        try {
            $ext = strtolower((string) $file->getClientExtension());
        } catch (\Throwable $e) {
            $ext = '';
        }
        if (str_starts_with($mime, 'video/') || (bool) preg_match('/^(mp4|webm|ogg|ogv|mov|m4v)$/', $ext)) {
            return 'video';
        }

        return 'image';
    }

    protected function mediaTypeForPath(string $path, $file = null): string
    {
        if ($this->clientMediaType($file) === 'video') {
            return 'video';
        }
        if (function_exists('is_product_video') && is_product_video($path, '')) {
            return 'video';
        }

        return 'image';
    }

    protected function imageRow(int $productId, string $path, int $primary, int $sort, string $mediaType = 'image'): array
    {
        $row = [
            'product_id' => $productId,
            'image_path' => $path,
            'is_primary' => $primary,
        ];
        if ($this->imageHasSort()) {
            $row['sort_order'] = $sort;
        }
        if ($this->imageHasMediaType()) {
            $row['media_type'] = $mediaType === 'video' ? 'video' : 'image';
        }
        try {
            if (\Config\Database::connect()->fieldExists('created_at', 'product_images')) {
                $row['created_at'] = date('Y-m-d H:i:s');
            }
        } catch (\Throwable $e) {
            // skip
        }

        return $row;
    }

    protected function galleryUploadFiles(): array
    {
        $files = $this->request->getFileMultiple('gallery_files');
        if (is_array($files)) {
            return array_values($files);
        }
        $one = $this->request->getFile('gallery_files');
        if ($one) {
            return [$one];
        }

        return [];
    }

    protected function postedEmptyForm(): bool
    {
        return $this->request->getPost('name') === null && $this->request->getPost('price') === null;
    }

    protected function storeUploadedFile($file): ?string
    {
        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return null;
        }
        $uploadDir = FCPATH . 'uploads/products';
        if (! is_dir($uploadDir) && ! @mkdir($uploadDir, 0755, true) && ! is_dir($uploadDir)) {
            log_message('error', 'Cannot create upload dir: ' . $uploadDir);

            return null;
        }
        $ext = '';
        try {
            $ext = strtolower((string) preg_replace('/[^a-z0-9]/', '', (string) $file->getClientExtension()));
        } catch (\Throwable $e) {
            $ext = '';
        }
        if ($ext === '') {
            $ext = 'bin';
        }
        $newName = date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
        $file->move($uploadDir, $newName);

        return base_url('uploads/products/' . $newName);
    }

    protected function savePrimaryImage(int $productId): void
    {
        $imageModel = $this->productImageModel ?? new ProductImageModel();
        $img = $this->request->getFile('image_file');
        $imagePath = $this->storeUploadedFile($img);

        if (! $imagePath && $this->request->getPost('image_url')) {
            $imagePath = (string) $this->request->getPost('image_url');
        } elseif (! $imagePath) {
            $imagePath = base_url('assets/images/product-placeholder.svg');
        }

        $imageModel->insert($this->imageRow($productId, $imagePath, 1, 0, $this->mediaTypeForPath($imagePath, $img)));
    }

    protected function replacePrimaryImage(int $productId): void
    {
        $imageModel = $this->productImageModel ?? new ProductImageModel();
        $file = $this->request->getFile('image_file');
        $path = $this->storeUploadedFile($file);
        if ($path) {
            \Config\Database::connect()->table('product_images')
                ->where('product_id', $productId)
                ->set(['is_primary' => 0])
                ->update();
            $imageModel->insert($this->imageRow($productId, $path, 1, 0, $this->mediaTypeForPath($path, $file)));
        }
    }

    protected function saveGalleryImages(int $productId, bool $keepPrimary): void
    {
        $imageModel = $this->productImageModel ?? new ProductImageModel();
        $order = array_values(array_filter((array) $this->request->getPost('gallery_order'), static fn ($v) => $v !== '' && $v !== null));
        $files = $this->galleryUploadFiles();

        if ($order === []) {
            $sort = 1;
            $hasPrimary = $keepPrimary;
            foreach ($files as $file) {
                $path = $this->storeUploadedFile($file);
                if (! $path) {
                    continue;
                }
                $imageModel->insert($this->imageRow($productId, $path, $hasPrimary ? 0 : 1, $sort++, $this->mediaTypeForPath($path, $file)));
                $hasPrimary = true;
            }

            return;
        }

        $listedExisting = [];
        foreach ($order as $token) {
            if (str_starts_with((string) $token, 'id:')) {
                $listedExisting[] = (int) substr((string) $token, 3);
            }
        }

        $oldGallery = $imageModel->where('product_id', $productId)->where('is_primary', 0)->findAll();
        foreach ($oldGallery as $row) {
            $id = (int) $row['id'];
            if (! in_array($id, $listedExisting, true)) {
                $imageModel->delete($id);
            }
        }

        $sort = 1;
        foreach ($order as $token) {
            $token = (string) $token;
            if (str_starts_with($token, 'id:')) {
                $id = (int) substr($token, 3);
                if ($id <= 0) {
                    continue;
                }
                $row = $imageModel->where('id', $id)->where('product_id', $productId)->first();
                if (! $row) {
                    continue;
                }
                $update = ['is_primary' => 0];
                if ($this->imageHasSort()) {
                    $update['sort_order'] = $sort;
                }
                \Config\Database::connect()->table('product_images')->where('id', $id)->update($update);
                $sort++;
            } elseif (str_starts_with($token, 'new:')) {
                $idx = (int) substr($token, 4);
                $path = $this->storeUploadedFile($files[$idx] ?? null);
                if (! $path) {
                    continue;
                }
                $imageModel->insert($this->imageRow($productId, $path, 0, $sort++, $this->mediaTypeForPath($path, $files[$idx] ?? null)));
            }
        }
    }

    /**
     * SKU rows copy the previous list price in the form. If the seller only changes
     * the main Price field, those SKUs must follow the new list price on the storefront.
     */
    protected function persistCatalogUpdate(int $id, array $existing, array $payload): void
    {
        $oldPrice = (float) ($existing['price'] ?? 0);
        $newPrice = (float) ($payload['price'] ?? 0);
        $this->productModel->updateCatalog($id, $payload);
        $this->persistPostedCashback($id);
        $this->replacePrimaryImage($id);
        $this->saveGalleryImages($id, true);
        $this->replaceVariants($id, $newPrice, $oldPrice);
        $this->applyListPriceToVariants($id, $newPrice, $oldPrice);
        $this->syncDealPrices($id, $oldPrice, $newPrice);
    }

    protected function saveVariants(int $productId, ?float $listPrice = null, ?float $previousListPrice = null): void
    {
        $colors = (array) $this->request->getPost('variant_color');
        $sizes  = (array) $this->request->getPost('variant_size');
        $stocks = (array) $this->request->getPost('variant_stock');
        $prices = (array) $this->request->getPost('variant_price');
        $keeps  = (array) $this->request->getPost('variant_image_keep');
        $files  = $this->request->getFileMultiple('variant_image');
        $files  = is_array($files) ? $files : [];
        $variantModel = new ProductVariantModel();
        $hasImageCol  = false;
        try {
            $hasImageCol = $variantModel->db->fieldExists('image', 'product_variants');
        } catch (\Throwable $e) {
            $hasImageCol = false;
        }

        $count = max(count($colors), count($sizes), count($stocks), count($prices), count($keeps), count($files));
        $colorImage = [];
        for ($i = 0; $i < $count; $i++) {
            $color = trim((string) ($colors[$i] ?? ''));
            $size  = trim((string) ($sizes[$i] ?? ''));
            $stock = (int) ($stocks[$i] ?? 0);
            $price = $this->inheritVariantPrice($prices[$i] ?? '', $listPrice, $previousListPrice);
            if ($color === '' && $size === '') {
                continue;
            }
            $uploaded = $this->storeUploadedFile($files[$i] ?? null);
            $keep     = trim((string) ($keeps[$i] ?? ''));
            $image    = $uploaded ?: $keep;
            if ($color !== '' && $image !== '') {
                $colorImage[$color] = $image;
            } elseif ($color !== '' && $image === '' && isset($colorImage[$color])) {
                $image = $colorImage[$color];
            }
            $row = [
                'product_id' => $productId,
                'color'      => $color !== '' ? $color : null,
                'size'       => $size !== '' ? $size : null,
                'stock'      => $stock,
                'price'      => $price,
                'sku'        => 'VAR-' . strtoupper(substr(md5($productId . $color . $size . $i), 0, 6)),
            ];
            if ($hasImageCol && $image !== '') {
                $row['image'] = $image;
            }
            $variantModel->skipValidation(true)->insert($row);
        }
    }

    protected function replaceVariants(int $productId, ?float $listPrice = null, ?float $previousListPrice = null): void
    {
        (new ProductVariantModel())->where('product_id', $productId)->delete();
        $this->saveVariants($productId, $listPrice, $previousListPrice);
    }

    protected function inheritVariantPrice($posted, ?float $listPrice, ?float $previousListPrice): ?float
    {
        if ($posted === '' || $posted === null) {
            return $listPrice !== null && $listPrice > 0 ? $listPrice : null;
        }
        $price = (float) $posted;
        if ($price <= 0) {
            return $listPrice !== null && $listPrice > 0 ? $listPrice : null;
        }
        if ($previousListPrice !== null && abs($price - $previousListPrice) < 0.009) {
            return $listPrice !== null && $listPrice > 0 ? $listPrice : $price;
        }
        if ($listPrice !== null && abs($price - $listPrice) < 0.009) {
            return $listPrice;
        }

        return $price;
    }

    protected function applyListPriceToVariants(int $productId, float $newPrice, float $oldPrice): void
    {
        if ($productId <= 0 || $newPrice <= 0) {
            return;
        }
        try {
            $db = \Config\Database::connect();
            if (! $db->tableExists('product_variants')) {
                return;
            }
            $db->table('product_variants')
                ->where('product_id', $productId)
                ->groupStart()
                    ->where('price', null)
                    ->orWhere('price', 0)
                    ->orWhere('price', $oldPrice)
                ->groupEnd()
                ->update(['price' => $newPrice]);
        } catch (\Throwable $e) {
            log_message('error', 'Variant list price sync: ' . $e->getMessage());
        }
    }

    protected function syncDealPrices(int $productId, float $oldPrice, float $newPrice): void
    {
        if ($productId <= 0 || $newPrice <= 0) {
            return;
        }
        try {
            $db = \Config\Database::connect();
            if (! $db->tableExists('flash_sale_items')) {
                return;
            }
            $rows = $db->table('flash_sale_items')->where('product_id', $productId)->get()->getResultArray();
            foreach ($rows as $row) {
                $sale = (float) ($row['sale_price'] ?? 0);
                $next = $sale;
                if ($oldPrice > 0 && abs($sale - $oldPrice) < 0.009) {
                    $next = $newPrice;
                } elseif ($sale > $newPrice) {
                    $next = $newPrice;
                }
                if (abs($next - $sale) > 0.009) {
                    $db->table('flash_sale_items')->where('id', (int) $row['id'])->update(['sale_price' => $next]);
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'Deal price sync: ' . $e->getMessage());
        }
    }
}
