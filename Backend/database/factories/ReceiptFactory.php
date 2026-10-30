<?php

namespace Database\Factories;

use App\Models\Receipt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Receipt>
 */
class ReceiptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = ['RECEIVED', 'PROCESSING', 'PROCESSED', 'CONFIRMED', 'REQUIRES_REVIEW', 'ERROR'];
        $fileTypes = ['jpg', 'jpeg', 'png', 'pdf'];
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'pdf' => 'application/pdf',
        ];

        $fileType = $this->faker->randomElement($fileTypes);
        $fileName = $this->faker->word . '.' . $fileType;

        return [
            'user_id' => \App\Models\User::factory(),
            'file_path' => 'receipts/' . $this->faker->uuid . '.' . $fileType,
            'file_name' => $fileName,
            'mime_type' => $mimeTypes[$fileType],
            'status' => $this->faker->randomElement($statuses),
            'extracted_data' => $this->faker->optional(0.8, function () {
                return [
                    'amount' => $this->faker->randomFloat(2, 0.01, 1000),
                    'date' => $this->faker->date,
                    'merchant' => $this->faker->company,
                    'operation_type' => $this->faker->randomElement(['EXPENSE', 'INCOME']),
                    'currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP', 'JPY', 'CAD']),
                    'source_id' => \App\Models\Source::factory(),
                    'category_id' => \App\Models\Category::factory(),
                    'description' => $this->faker->sentence,
                ];
            }) ?: null,
        ];
    }
}