<?php

namespace Database\Seeders;

use App\Models\Book;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    public function run(): void
    {
        // Créer 100 livres standards
        Book::factory(100)->create();

        // Créer 20 livres récents
        Book::factory(20)
            ->recent()
            ->create();

        // Créer 10 livres en rupture de stock
        Book::factory(10)
            ->outOfStock()
            ->create();

        // Créer 30 livres avec une seule copie
        Book::factory(30)
            ->singleCopy()
            ->create();

        // Créer quelques livres spécifiques
        $this->createSpecificBooks();
    }

    private function createSpecificBooks(): void
    {
        $specificBooks = [
            [
                'title' => 'Introduction à la Programmation',
                'authors' => ['John Doe', 'Jane Smith'],
                'copies_count' => 5,
                'available_copies' => 3,
                'parts_count' => 1,
                'publisher' => 'TechBooks Edition',
                'edition_number' => 2,
                'dewey_category' => '000',
                'dewey_subcategory' => '04',
                'price' => 49.99,
                'comments' => 'Excellent livre pour débutants',
                'central_number' => 'CN-PROG1',
                'local_number' => 'LN-PROG1',
                'publication_date' => '2023-01-15',
                'acquisition_date' => '2023-02-01',
            ],
            [
                'title' => 'Histoire de l\'Art Moderne',
                'authors' => ['Marie Dubois'],
                'copies_count' => 3,
                'available_copies' => 2,
                'parts_count' => 2,
                'publisher' => 'Arts & Culture',
                'edition_number' => 1,
                'dewey_category' => '700',
                'dewey_subcategory' => '09',
                'price' => 75.00,
                'comments' => 'Édition limitée avec illustrations couleur',
                'central_number' => 'CN-ART01',
                'local_number' => 'LN-ART01',
                'publication_date' => '2022-06-20',
                'acquisition_date' => '2022-07-15',
            ],
        ];

        foreach ($specificBooks as $book) {
            Book::create($book);
        }
    }
} 