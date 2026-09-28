<?php

namespace App\Controllers\Customer;

use App\Controllers\BaseController;
use App\Models\SupportTicketModel;
use App\Models\TicketReplyModel;

class HelpController extends BaseController
{
    public function index()
    {
        $tickets = [];
        $userId = (int) session()->get('user.id');
        if ($userId) {
            $tickets = (new SupportTicketModel())->where('user_id', $userId)->orderBy('id', 'DESC')->findAll();
        }

        return view('customer/help', [
            'title'   => 'Help Center — Solqam',
            'tickets' => $tickets,
        ]);
    }

    public function store()
    {
        $userId = (int) session()->get('user.id');
        $subject = trim((string) $this->request->getPost('subject'));
        $message = trim((string) $this->request->getPost('message'));
        if ($subject === '' || $message === '') {
            return redirect()->back()->withInput()->with('error', 'Subject and message are required.');
        }

        (new SupportTicketModel())->insert([
            'user_id' => $userId,
            'subject' => $subject,
            'message' => $message,
            'status'  => 'open',
        ]);

        return redirect()->to('/help')->with('success', 'Ticket opened. Support will reply here.');
    }

    public function show($id)
    {
        $userId = (int) session()->get('user.id');
        $ticket = (new SupportTicketModel())->find((int) $id);
        if (!$ticket || (int) $ticket['user_id'] !== $userId) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Ticket not found');
        }

        $replies = (new TicketReplyModel())
            ->select('ticket_replies.*, users.name as author_name, users.role as author_role')
            ->join('users', 'users.id = ticket_replies.user_id', 'left')
            ->where('ticket_id', (int) $id)
            ->orderBy('ticket_replies.id', 'ASC')
            ->findAll();

        return view('customer/tickets/show', [
            'title'   => 'Ticket #' . $ticket['id'] . ' — Solqam Help',
            'ticket'  => $ticket,
            'replies' => $replies,
        ]);
    }

    public function reply($id)
    {
        $userId = (int) session()->get('user.id');
        $ticket = (new SupportTicketModel())->find((int) $id);
        if (!$ticket || (int) $ticket['user_id'] !== $userId) {
            return redirect()->to('/help')->with('error', 'Ticket not found.');
        }

        $message = trim((string) $this->request->getPost('message'));
        if ($message === '') {
            return redirect()->back()->with('error', 'Write a reply.');
        }

        (new TicketReplyModel())->insert([
            'ticket_id' => (int) $id,
            'user_id'   => $userId,
            'message'   => $message,
        ]);
        (new SupportTicketModel())->update((int) $id, ['status' => 'open']);

        return redirect()->to('/help/tickets/' . $id);
    }
}
