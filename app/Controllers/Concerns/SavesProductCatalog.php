<?php

namespace App\Controllers\Concerns;

use App\Models\ProductImageModel;
use App\Models\ProductVariantModel;

trait SavesProductCatalog
{
    protected function catalogProductPayload(): array
    {
        $brand     = trim((string) $this->request->getPost('brand'));
        $warranty  = trim((string) $this->request->getPost('warranty_info'));
        $highlights = trim((string) $this->request->getPost('highlights'));
        $specs      = trim((string) $this->request->getPost('specifications'));
        $sizeGuide  = trim((string) $this->request->getPost('size_guide'));
        $compare    = $this->request->getPost('compare_at_price');
        $returnDays = $this->request->getPost('return_days');
        $cashback   = $this->request->getPost('cashback_percent');

        return [
            'category_id'      => (int) $this->request->getPost('category_id'),
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

    protected function savePrimaryImage(int $productId): void
    {
        $imageModel = $this->productImageModel ?? new ProductImageModel();
        $img = $this->request->getFile('image_file');
        $imagePath = null;

        if ($img && $img->isValid() && !$img->hasMoved()) {
            $newName = $img->getRandomName();
            $uploadDir = FCPATH . 'uploads/products';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $img->move($uploadDir, $newName);
            $imagePath = base_url('uploads/products/' . $newName);
        } elseif ($this->request->getPost('image_url')) {
            $imagePath = $this->request->getPost('image_url');
        } else {
            $imagePath = base_url('assets/images/product-placeholder.svg');
        }

        if ($imagePath) {
            $imageModel->insert([
                'product_id' => $productId,
                'image_path' => $imagePath,
                'is_primary' => 1,
            ]);
        }
    }

    protected function replacePrimaryImage(int $productId): void
    {
        $imageModel = $this->productImageModel ?? new ProductImageModel();
        $img = $this->request->getFile('image_file');
        if (!$img || !$img->isValid() || $img->hasMoved()) {
            return;
        }
        $newName = $img->getRandomName();
        $uploadDir = FCPATH . 'uploads/products';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $img->move($uploadDir, $newName);
        $imagePath = base_url('uploads/products/' . $newName);
        $imageModel->where('product_id', $productId)->set(['is_primary' => 0])->update();
        $imageModel->insert([
            'product_id' => $productId,
            'image_path' => $imagePath,
            'is_primary' => 1,
        ]);
    }

    protected function saveGalleryImages(int $productId, bool $keepPrimary): void
    {
        $imageModel = $this->productImageModel ?? new ProductImageModel();
        $files = $this->request->getFileMultiple('gallery_files') ?? [];
        $uploadDir = FCPATH . 'uploads/products';
        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $hasPrimary = $keepPrimary;
        foreach ($files as $file) {
            if (! $file || ! $file->isValid() || $file->hasMoved()) {
                continue;
            }
            $newName = $file->getRandomName();
            $file->move($uploadDir, $newName);
            $imageModel->insert([
                'product_id' => $productId,
                'image_path' => base_url('uploads/products/' . $newName),
                'is_primary' => $hasPrimary ? 0 : 1,
            ]);
            $hasPrimary = true;
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
