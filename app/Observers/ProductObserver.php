<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Product;

class ProductObserver
{
    private function log(string $action, Product $product, array $changes = []): void
    {
        AuditLog::create([
            'user'      => request()->bearerToken() ?? 'system',
            'action'    => $action,
            'entity'    => 'Product',
            'entity_id' => $product->id,
            'changes'   => $changes,
        ]);
    }

    public function created(Product $product): void
    {
        $this->log('created', $product, $product->getAttributes());
    }

    public function updated(Product $product): void
    {
        $this->log('updated', $product, $product->getChanges());
    }

    public function deleted(Product $product): void
    {
        $this->log('deleted', $product);
    }
}