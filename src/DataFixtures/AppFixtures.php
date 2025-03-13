<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Editor;
use App\Entity\Users;
use App\Entity\VideoGame;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Création des utilisateurs
        $admin = new Users();
        $admin->setUserName('admin');
        $admin->setEmail('admin@example.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'admin123');
        $admin->setPassword($hashedPassword);
        $manager->persist($admin);

        $user = new Users();
        $user->setUserName('user');
        $user->setEmail('user@example.com');
        $user->setRoles(['ROLE_USER']);
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'user123');
        $user->setPassword($hashedPassword);
        $manager->persist($user);

        // Création des catégories
        $categories = [];
        $categoryNames = ['Action', 'Aventure', 'RPG', 'Stratégie', 'FPS', 'Sport', 'Simulation', 'Puzzle'];
        
        foreach ($categoryNames as $name) {
            $category = new Category();
            $category->setName($name);
            $manager->persist($category);
            $categories[] = $category;
        }

        // Création des éditeurs
        $editors = [];
        $editorData = [
            ['Ubisoft', 'France'],
            ['Electronic Arts', 'USA'],
            ['Nintendo', 'Japon'],
            ['Square Enix', 'Japon'],
            ['Bethesda', 'USA'],
            ['CD Projekt', 'Pologne'],
            ['Rockstar Games', 'USA'],
            ['Valve', 'USA'],
            ['Bandai Namco', 'Japon'],
            ['Activision Blizzard', 'USA']
        ];
        
        foreach ($editorData as [$name, $country]) {
            $editor = new Editor();
            $editor->setName($name);
            $editor->setCountry($country);
            $manager->persist($editor);
            $editors[] = $editor;
        }

        // Création des jeux vidéo
        $gameData = [
            ['Assassin\'s Creed Valhalla', '2020-11-10', 'Un jeu d\'action-aventure situé dans l\'ère viking', 0, 0],
            ['FIFA 23', '2022-09-30', 'Jeu de simulation de football', 1, 6],
            ['The Legend of Zelda: Breath of the Wild', '2017-03-03', 'Jeu d\'aventure dans un monde ouvert', 2, 1],
            ['Final Fantasy XVI', '2023-06-22', 'Jeu de rôle dans un univers fantastique', 3, 2],
            ['Fallout 4', '2015-11-10', 'Jeu de rôle post-apocalyptique', 4, 2],
            ['The Witcher 3: Wild Hunt', '2015-05-19', 'Jeu de rôle dans un monde de fantasy', 5, 2],
            ['Red Dead Redemption 2', '2018-10-26', 'Jeu d\'action-aventure dans l\'Ouest américain', 6, 1],
            ['Counter-Strike 2', '2023-09-27', 'Jeu de tir à la première personne', 7, 4],
            ['Dark Souls III', '2016-03-24', 'Jeu d\'action-RPG difficile', 8, 2],
            ['Call of Duty: Modern Warfare III', '2023-11-10', 'Jeu de tir à la première personne', 9, 4],
            ['The Elder Scrolls V: Skyrim', '2011-11-11', 'Jeu de rôle dans un univers fantastique', 4, 2],
            ['Grand Theft Auto V', '2013-09-17', 'Jeu d\'action-aventure dans un monde ouvert', 6, 0],
            ['Super Mario Odyssey', '2017-10-27', 'Jeu de plateforme 3D', 2, 1],
            ['Overwatch 2', '2022-10-04', 'Jeu de tir à la première personne compétitif', 9, 4],
            ['Minecraft', '2011-11-18', 'Jeu de construction sandbox', 1, 6]
        ];
        
        foreach ($gameData as [$title, $releaseDate, $description, $editorIndex, $categoryIndex]) {
            $game = new VideoGame();
            $game->setTitle($title);
            $game->setReleaseDate(new \DateTime($releaseDate));
            $game->setDescription($description);
            $game->setEditor($editors[$editorIndex]);
            $game->setCategory($categories[$categoryIndex]);
            $game->setUsers($admin); // Attribuer tous les jeux à l'admin pour l'exemple
            $manager->persist($game);
        }

        $manager->flush();
    }
}