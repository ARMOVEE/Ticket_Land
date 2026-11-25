<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Category;
use App\Models\Event;
use App\Models\Ticket;

class HomeController extends Controller
{
    public function index(): void
    {
        $pdo = Database::getInstance();
        $dbName = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();

        $categoryModel = new Category();
        $eventModel = new Event();
        $ticketModel = new Ticket();

        $categories = $categoryModel->getAll();
        $events = $eventModel->getAllActive();
        $demoTickets = $ticketModel->getDemoTickets();

        $this->view('home/index', [
            'dbName'      => $dbName,
            'categories'  => $categories,
            'events'      => $events,
            'demoTickets' => $demoTickets,
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
