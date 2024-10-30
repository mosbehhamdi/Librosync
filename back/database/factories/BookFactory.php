<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BookFactory extends Factory
{
    public function definition(): array
    {
        $deweyCategories = ['000', '100', '200', '300', '400', '500', '600', '700', '800', '900'];
        $copies = $this->faker->numberBetween(1, 10);
        
        return [
            'title' => $this->faker->sentence(4),
            'authors' => [
                $this->faker->name(),
                $this->faker->optional(0.3)->name(),
            ],
            'copies_count' => $copies,
            'available_copies' => $this->faker->numberBetween(0, $copies),
            'parts_count' => $this->faker->numberBetween(1, 5),
            'publisher' => $this->faker->company(),
            'edition_number' => $this->faker->numberBetween(1, 5),
            'dewey_category' => $this->faker->randomElement($deweyCategories),
            'dewey_subcategory' => $this->faker->numberBetween(10, 99),
            'price' => $this->faker->randomFloat(2, 10, 200),
            'comments' => $this->faker->optional(0.7)->paragraph(),
            'central_number' => $this->faker->unique()->numerify('CN-#####'),
            'local_number' => $this->faker->unique()->numerify('LN-#####'),
            'publication_date' => $this->faker->dateTimeBetween('-30 years', 'now'),
            'acquisition_date' => $this->faker->dateTimeBetween('-5 years', 'now'),
        ];
    }

    /**
     * Indicate that the book is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'available_copies' => 0,
        ]);
    }

    /**
     * Indicate that the book is a single copy.
     */
    public function singleCopy(): static
    {
        return $this->state(fn (array $attributes) => [
            'copies_count' => 1,
            'available_copies' => 1,
        ]);
    }

    /**
     * Indicate that the book is a recent publication.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'publication_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'acquisition_date' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ]);
    }
} 