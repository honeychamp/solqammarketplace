<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SupportTicketModel;
use App\Models\TicketReplyModel;

class TicketController extends BaseController
{
    public function index()
    {
        $tickets = (new SupportTicketModel())
            ->select('support_tickets.*, users.name as customer_name, users.email as customer_email')
            ->join('users', 'users.id = support_tickets.user_id', 'left')
            ->orderBy('support_tickets.id', 'DESC')
            ->findAll();

        return view('admin/tickets/index', [
            'title'   => 'Help Tickets — Solqam Admin',
            'tickets' => $tickets,
        ]);
    }

    public function show($id)
    {
        $ticket = (new SupportTicketModel())
            ->select('support_tickets.*, users.name as customer_name, users.email as customer_email')
            ->join('users', 'users.id = support_tickets.user_id', 'left')
            ->where('support_tickets.id', (int) $id)
            ->first();
        if (!$ticket) {
            return redirect()->to('/admin/tickets')->with('error', 'Ticket not found.');
        }

        $replies = (new TicketReplyModel())
            ->select('ticket_replies.*, users.name as author_name, users.role as author_role')
            ->join('users', 'users.id = ticket_replies.user_id', 'left')
            ->where('ticket_id', (int) $id)
            ->orderBy('ticket_replies.id', 'ASC')
            ->findAll();

        return view('admin/tickets/show', [
            'title'   => 'Ticket #' . $ticket['id'],
            'ticket'  => $ticket,
            'replies' => $replies,
        ]);
    }

    public function reply($id)
    {
        $adminId = (int) session()->get('user.id');
        $ticket = (new SupportTicketModel())->find((int) $id);
        if (!$ticket) {
            return redirect()->to('/admin/tickets')->with('error', 'Ticket not found.');
        }

        $message = trim((string) $this->request->getPost('message'));
        $status  = $this->request->getPost('status') ?: 'replied';
        if ($message === '') {
            return redirect()->back()->with('error', 'Write a reply.');
        }

        (new TicketReplyModel())->insert([
            'ticket_id' => (int) $id,
            'user_id'   => $adminId,
            'message'   => $message,
        ]);
        (new SupportTicketModel())->update((int) $id, ['status' => in_array($status, ['open', 'replied', 'closed'], true) ? $status : 'replied']);

        return $this->redirectAfterHub('/admin/tickets/' . $id, 'success', 'Reply sent.');
    }
}
