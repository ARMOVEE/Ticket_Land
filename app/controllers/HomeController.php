<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Category;
use App\Models\Event;

class HomeController extends Controller
{
    public function index(): void
    {
        $categoryModel = new Category();
        $eventModel = new Event();

        $categories = $categoryModel->getAll();
        $events = $eventModel->getAllActive();

        $heroEvent = null;
        $recommendedEvents = [];

        foreach ($events as $ev) {
            if ((int) $ev['is_hero'] === 1 && $heroEvent === null) {
                $heroEvent = $ev;
            }
            if ((int) $ev['is_featured'] === 1 && count($recommendedEvents) < 3) {
                $recommendedEvents[] = $ev;
            }
        }

        if ($heroEvent === null && !empty($events)) {
            $heroEvent = $events[0];
        }

        if (count($recommendedEvents) < 3) {
            foreach ($events as $ev) {
                $already = false;
                foreach ($recommendedEvents as $rec) {
                    if ((int) $rec['id'] === (int) $ev['id']) {
                        $already = true;
                        break;
                    }
                }
                if (!$already) {
                    $recommendedEvents[] = $ev;
                }
                if (count($recommendedEvents) >= 3) {
                    break;
                }
            }
        }

        $this->view('home/index', [
            'categories'        => $categories,
            'events'            => $events,
            'heroEvent'         => $heroEvent,
            'recommendedEvents' => $recommendedEvents,
            'customer'          => $_SESSION['customer'] ?? null,
            'csrfToken'         => self::csrfToken(),
        ]);
    }

    public function eventsApi(): void
    {
        $categoryModel = new Category();
        $eventModel = new Event();

        $events = $eventModel->getAllActive();
        $categoryId = isset($_GET['category_id']) && is_numeric($_GET['category_id']) ? (int) $_GET['category_id'] : null;
        $q = isset($_GET['q']) ? trim(strtolower((string) $_GET['q'])) : '';

        if ($categoryId !== null) {
            $events = array_values(array_filter($events, static fn(array $e): bool => (int) $e['category_id'] === $categoryId));
        }

        if ($q !== '') {
            $events = array_values(array_filter($events, static function (array $e) use ($q): bool {
                $hay = strtolower(($e['nama'] ?? '') . ' ' . ($e['penyelenggara'] ?? '') . ' ' . ($e['kota'] ?? '') . ' ' . ($e['venue'] ?? '') . ' ' . ($e['kode'] ?? ''));
                return str_contains($hay, $q);
            }));
        }

        $this->json([
            'ok'         => true,
            'categories' => $categoryModel->getAll(),
            'events'     => $events,
        ]);
    }

    public function health(): void
    {
        $pdo = Database::getInstance();
        $dbName = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
        $this->json([
            'status'   => 'ok',
            'app'      => 'Ticket Land',
            'database' => $dbName,
        ]);
    }
}
