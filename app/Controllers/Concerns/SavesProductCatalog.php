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
        $cashback   = $this->request->getPost('cashback_percent');

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
            'cashback_percent' => max(0.0, min(100.0, (float) ($cashback === '' || $cashback === null ? 0 : $cashback))),
        ];
    }

    protected function imageHasSort(): bool
    {
        try {
            return \Config\Database::connect()->fieldExists('sort_order', 'product_images');
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function imageRow(int $productId, string $path, int $primary, int $sort): array
    {
        $row = [
            'product_id' => $productId,
            'image_path' => $path,
            'is_primary' => $primary,
        ];
        if ($this->imageHasSort()) {
            $row['sort_order'] = $sort;
        }

        return $row;
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
        $newName = $file->getRandomName();
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

        $imageModel->insert($this->imageRow($productId, $imagePath, 1, 0));
    }

    protected function replacePrimaryImage(int $productId): void
    {
        $imageModel = $this->productImageModel ?? new ProductImageModel();
        $path = $this->storeUploadedFile($this->request->getFile('image_file'));
        if ($path) {
            $imageModel->where('product_id', $productId)->set(['is_primary' => 0])->update();
            $imageModel->insert($this->imageRow($productId, $path, 1, 0));
        }
    }

    protected function saveGalleryImages(int $productId, bool $keepPrimary): void
    {
        $imageModel = $this->productImageModel ?? new ProductImageModel();
        $order = array_values(array_filter((array) $this->request->getPost('gallery_order'), static fn ($v) => $v !== '' && $v !== null));
        $files = $this->request->getFileMultiple('gallery_files') ?? [];

        if ($order === []) {
            $sort = 1;
            $hasPrimary = $keepPrimary;
            foreach ($files as $file) {
                $path = $this->storeUploadedFile($file);
                if (! $path) {
                    continue;
                }
                $imageModel->insert($this->imageRow($productId, $path, $hasPrimary ? 0 : 1, $sort++));
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
                $imageModel->update($id, $update);
                $sort++;
            } elseif (str_starts_with($token, 'new:')) {
                $idx = (int) substr($token, 4);
                $path = $this->storeUploadedFile($files[$idx] ?? null);
                if (! $path) {
                    continue;
                }
                $imageModel->insert($this->imageRow($productId, $path, 0, $sort++));
            }
        }
    }

    protected function saveVariants(int $productId): void
    {
        $colors = (array) $this->request->getPost('variant_color');
        $sizes  = (array) $this->request->getPost('variant_size');
        $stocks = (array) $this->request->getPost('variant_stock');
        $prices = (array) $this->request->getPost('variant_price');
        $variantModel = new ProductVariantModel();

        $count = max(count($colors), count($sizes), count($stocks), count($prices));
        for ($i = 0; $i < $count; $i++) {
            $color = trim((string) ($colors[$i] ?? ''));
            $size  = trim((string) ($sizes[$i] ?? ''));
            $stock = (int) ($stocks[$i] ?? 0);
            $price = ($prices[$i] ?? '') !== '' ? (float) $prices[$i] : null;
            if ($color === '' && $size === '') {
                continue;
            }
            $variantModel->insert([
                'product_id' => $productId,
                'color'      => $color !== '' ? $color : null,
                'size'       => $size !== '' ? $size : null,
                'stock'      => $stock,
                'price'      => $price,
                'sku'        => 'VAR-' . strtoupper(substr(md5($productId . $color . $size . $i), 0, 6)),
            ]);
        }
    }

    protected function replaceVariants(int $productId): void
    {
        (new ProductVariantModel())->where('product_id', $productId)->delete();
        $this->saveVariants($productId);
    }
}
