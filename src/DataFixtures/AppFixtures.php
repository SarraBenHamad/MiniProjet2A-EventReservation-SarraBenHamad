<?php
namespace App\DataFixtures;

use App\Entity\Admin;
use App\Entity\Event;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $hasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // Create admin
        $admin = new Admin();
        $admin->setUsername('admin');
        $admin->setPassword(
            $this->hasher->hashPassword($admin, 'admin123')
        );
        $manager->persist($admin);

        // Updated test events
        $events = [
            ['Cyber Security Summit', 'Deep dive into network security and ethical hacking.', '+5 days', 'Tunis, Tunisia', 150],
            ['Flutter & Dart Workshop', 'Build high-performance mobile apps for Android and iOS.', '+12 days', 'Sousse, Tunisia', 80],
            ['AI & Robotics Expo', 'Exploring the future of automation and machine learning.', '+20 days', 'Sfax, Tunisia', 300],
            ['UI/UX Design Masterclass', 'Focusing on gamification and user-centered design.', '+8 days', 'Monastir, Tunisia', 40],
            ['Networking Night', 'Connect with developers and tech industry leaders.', '+15 days', 'Hammamet, Tunisia', 120],
        ];

        foreach ($events as [$title, $desc, $offset, $location, $seats]) {
            $event = new Event();
            $event->setTitle($title);
            $event->setDescription($desc);
            $event->setDate(new \DateTime($offset));
            $event->setLocation($location);
            $event->setSeats($seats);
            $manager->persist($event);
        }

        $manager->flush();
    }
}