<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\User;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition()
    {
        $status = $this->faker->randomElement(['pending', 'ready', 'completed', 'cancelled']);
        
        return [
            'user_id' => User::factory(),
            'book_id' => Book::factory(),
            'status' => $status,
            'queue_position' => $status === 'pending' ? $this->faker->numberBetween(1, 5) : 1,
            'expires_at' => $status === 'ready' ? now()->addDays(2) : null,
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'updated_at' => now()
        ];
    }

    public function pending()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
                'queue_position' => $this->faker->numberBetween(1, 5),
                'expires_at' => null
            ];
        });
    }

    public function ready()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'ready',
                'queue_position' => 1,
                'expires_at' => now()->addDays(2)
            ];
        });
    }
} 