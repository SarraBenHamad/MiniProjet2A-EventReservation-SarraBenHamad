<?php
namespace App\Controller\User;

use App\Entity\Reservation;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EventController extends AbstractController
{
    public function __construct(
        private EventRepository $eventRepo,
        private EntityManagerInterface $em
    ) {}

    // ─── Homepage — Event Listing ──────────────────────────
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return $this->render('user/index.html.twig', [
            'events' => $this->eventRepo->findUpcoming(),
        ]);
    }

    // ─── Event Detail ─────────────────────────────────────
    #[Route('/event/{id}', name: 'event_show')]
    public function show(int $id): Response
    {
        $event = $this->eventRepo->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }

        return $this->render('user/event_show.html.twig', [
            'event' => $event,
        ]);
    }

    // ─── Reservation Form + Save ──────────────────────────
    #[Route('/event/{id}/reserve', name: 'event_reserve', methods: ['POST'])]
    public function reserve(int $id, Request $request): Response
    {
        $event = $this->eventRepo->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }

        // Check seats available
        if ($event->getAvailableSeats() <= 0) {
            $this->addFlash('error', 'Sorry, this event is fully booked.');
            return $this->redirectToRoute('event_show', ['id' => $id]);
        }

        $reservation = new Reservation();
        $reservation->setEvent($event);
        $reservation->setName($request->request->get('name'));
        $reservation->setEmail($request->request->get('email'));
        $reservation->setPhone($request->request->get('phone'));

        // Link to logged-in user if any
        if ($this->getUser()) {
            $reservation->setUser($this->getUser());
        }

        $this->em->persist($reservation);
        $this->em->flush();

        return $this->render('user/confirmation.html.twig', [
            'reservation' => $reservation,
            'event' => $event,
        ]);
    }
}
