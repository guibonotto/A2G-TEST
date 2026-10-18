<?php

namespace Database\Factories;

use App\Models\Evidence;
use App\Models\Execution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Evidence>
 */
class EvidenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fileName = fake()->unique()->lexify('evidence-?????').'.png';

        return [
            'execution_id' => Execution::factory(),
            'file_name' => $fileName,
            'file_path' => 'evidences/'.fake()->numberBetween(1, 1000).'/'.$fileName,
            'mime_type' => 'image/png',
            'size' => fake()->numberBetween(1024, 1024 * 512),
            'uploaded_at' => now(),
        ];
    }
}
