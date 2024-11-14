<?php

return [
    'auth' => [
        'failed' => 'Ces identifiants ne correspondent pas à nos enregistrements.',
        'password' => 'Le mot de passe fourni est incorrect.',
        'throttle' => 'Trop de tentatives de connexion. Veuillez réessayer dans :seconds secondes.',
    ],
    'validation' => [
        'required' => 'Le champ :attribute est obligatoire.',
        'email' => 'Le champ :attribute doit être une adresse email valide.',
        'min' => [
            'string' => 'Le champ :attribute doit contenir au moins :min caractères.',
        ],
        'confirmed' => 'La confirmation de :attribute ne correspond pas.',
    ],
    'books' => [
        'created' => 'Livre créé avec succès.',
        'updated' => 'Livre mis à jour avec succès.',
        'deleted' => 'Livre supprimé avec succès.',
        'not_found' => 'Livre introuvable.',
    ],
    'reservations' => [
        'created' => 'Réservation créée avec succès.',
        'cancelled' => 'Réservation annulée avec succès.',
        'accepted' => 'Réservation acceptée avec succès.',
        'delivered' => 'Livre livré avec succès.',
        'returned' => 'Livre retourné avec succès.',
        'not_found' => 'Réservation introuvable.',
    ]
];
