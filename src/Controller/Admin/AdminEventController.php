<?php
namespace App\Controller\Admin;

use App\Entity\Event;
use App\Repository\EventRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminEventController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private EventRepository $eventRepo,
        private ReservationRepository $reservationRepo
    ) {}

    // ─── Dashboard ────────────────────────────────────────
    #[Route('/dashboard', name: 'admin_dashboard')]
    public function dashboard(): Response
    {
        $events = $this->eventRepo->findAll();
        $totalReservations = $this->reservationRepo->count([]);

        return $this->render('admin/dashboard.html.twig', [
            'events' => $events,
            'totalReservations' => $totalReservations,
        ]);
    }

    // ─── List Events ──────────────────────────────────────
    #[Route('/events', name: 'admin_events_index')]
    public function index(): Response
    {
        return $this->render('admin/events/index.html.twig', [
            'events' => $this->eventRepo->findAll(),
        ]);
    }

    // ─── Create Event ─────────────────────────────────────
    #[Route('/events/new', name: 'admin_events_new')]
    public function new(Request $request, SluggerInterface $slugger): Response
    {
        if ($request->isMethod('POST')) {
            $event = new Event();
            $event->setTitle($request->request->get('title'));
            $event->setDescription($request->request->get('description'));
            $event->setDate(new \DateTime($request->request->get('date')));
            $event->setLocation($request->request->get('location'));
            $event->setSeats((int) $request->request->get('seats'));

            // Handle image upload
            $imageFile = $request->files->get('image');
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/events',
                        $newFilename
                    );
                    $event->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Image upload failed.');
                }
            }

            $this->em->persist($event);
            $this->em->flush();

            $this->addFlash('success', 'Event created successfully!');
            return $this->redirectToRoute('admin_events_index');
        }

        return $this->render('admin/events/new.html.twig');
    }

    // ─── Edit Event ───────────────────────────────────────
    #[Route('/events/{id}/edit', name: 'admin_events_edit')]
    public function edit(int $id, Request $request, SluggerInterface $slugger): Response
    {
        $event = $this->eventRepo->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }

        if ($request->isMethod('POST')) {
            $event->setTitle($request->request->get('title'));
            $event->setDescription($request->request->get('description'));
            $event->setDate(new \DateTime($request->request->get('date')));
            $event->setLocation($request->request->get('location'));
            $event->setSeats((int) $request->request->get('seats'));

            $imageFile = $request->files->get('image');
            if ($imageFile) {
                // Delete old image
                if ($event->getImage()) {
                    $oldPath = $this->getParameter('kernel.project_dir')
                        . '/public/uploads/events/' . $event->getImage();
                    if (file_exists($oldPath)) unlink($oldPath);
                }

                $safeFilename = $slugger->slug(
                    pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME)
                );
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/events',
                        $newFilename
                    );
                    $event->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Image upload failed.');
                }
            }

            $this->em->flush();
            $this->addFlash('success', 'Event updated successfully!');
            return $this->redirectToRoute('admin_events_index');
        }

        return $this->render('admin/events/edit.html.twig', [
            'event' => $event,
        ]);
    }

    // ─── Delete Event ─────────────────────────────────────
    #[Route('/events/{id}/delete', name: 'admin_events_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $event = $this->eventRepo->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }

        // Delete image file
        if ($event->getImage()) {
            $path = $this->getParameter('kernel.project_dir')
                . '/public/uploads/events/' . $event->getImage();
            if (file_exists($path)) unlink($path);
        }

        $this->em->remove($event);
        $this->em->flush();

        $this->addFlash('success', 'Event deleted.');
        return $this->redirectToRoute('admin_events_index');
    }

    // ─── View Reservations for an Event ───────────────────
    #[Route('/events/{id}/reservations', name: 'admin_events_reservations')]
    public function reservations(int $id): Response
    {
        $event = $this->eventRepo->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }

        return $this->render('admin/events/reservations.html.twig', [
            'event' => $event,
            'reservations' => $event->getReservations(),
        ]);
    }
}
