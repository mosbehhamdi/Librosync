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
        $status = $this->faker->randomElement(['pending', 'ready', 'accepted', 'delivered', 'cancelled']);
        $delivered_at = null;
        $due_date = null;
        $expires_at = null;
        $queue_position = null;

        switch ($status) {
            case 'pending':
                $queue_position = $this->faker->numberBetween(1, 5);
                break;
            case 'ready':
                $expires_at = now()->addDays(2);
                break;
            case 'delivered':
                $delivered_at = now()->subDays($this->faker->numberBetween(15, 30));
                $due_date = (clone $delivered_at)->addDays(14);
                break;
        }

        return [
            'user_id' => User::factory(),
            'book_id' => Book::factory(),
            'status' => $status,
            'queue_position' => $queue_position,
            'expires_at' => $expires_at,
            'delivered_at' => $delivered_at,
            'due_date' => $due_date,
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'updated_at' => now()
        ];
    }
}
