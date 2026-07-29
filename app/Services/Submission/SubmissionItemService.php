<?php

namespace App\Services\Submission;

use App\Models\FinancialSubmission;
use App\Models\SubmissionCategory;

class SubmissionItemService
{
    public function __construct(private readonly SubmissionCalculator $calculator) {}

    public function replaceItems(FinancialSubmission $submission, array $items): string
    {
        $submission->items()->delete();

        $rows = [];
        foreach (array_values($items) as $index => $item) {
            $category = SubmissionCategory::findOrFail($item['category_id']);
            $subtotal = $this->calculator->itemSubtotal($item['quantity'], $item['unit_price']);
            $rows[] = [
                'financial_submission_id' => $submission->id,
                'category_id' => $category->id,
                'category_name' => $category->name,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit' => $item['unit'] ?? null,
                'unit_price' => $item['unit_price'],
                'subtotal' => $subtotal,
                'notes' => $item['notes'] ?? null,
                'sort_order' => $index,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $submission->items()->createMany($rows);

        return $this->calculator->total($rows);
    }
}
