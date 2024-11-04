<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition()
    {
        $copiesCount = $this->faker->numberBetween(1, 5);
        return [
            'title' => $this->faker->sentence(3),
            'authors' => [$this->faker->name(), $this->faker->name()],
            'dewey_category' => $this->faker->numberBetween(100, 900),
            'dewey_subcategory' => $this->faker->numberBetween(10, 99),
            'copies_count' => $copiesCount,
            'available_copies' => $this->faker->numberBetween(0, $copiesCount),
            'parts_count' => $this->faker->numberBetween(1, 3),
            'publisher' => $this->faker->company(),
            'edition_number' => $this->faker->numberBetween(1, 5),
            'price' => $this->faker->randomFloat(2, 10, 100),
            'comments' => $this->faker->optional()->sentence(),
            'central_number' => $this->faker->unique()->numerify('CN###'),
            'local_number' => $this->faker->unique()->numerify('LN###'),
            'publication_date' => $this->faker->date(),
            'acquisition_date' => $this->faker->date(),
            'isbn' => $this->faker->unique()->isbn13,
            'publication_year' => $this->faker->year
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