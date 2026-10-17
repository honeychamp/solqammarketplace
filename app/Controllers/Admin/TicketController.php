<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SupportTicketModel;
use App\Models\TicketReplyModel;

class TicketController extends BaseController
{
    public function index()
    {
        $ticketModel = new SupportTicketModel();
        $tickets = $ticketModel
            ->select('support_tickets.*, users.name as customer_name, users.email as customer_email')
            ->join('users', 'users.id = support_tickets.user_id', 'left')
            ->orderBy('support_tickets.id', 'DESC')
            ->paginate(25);

        return view('admin/tickets/index', [
            'title'   => 'Help Tickets — Solqam Admin',
            'tickets' => $tickets,
            'pager'   => $ticketModel->pager,
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

    public function update($id)
    {
        $id     = (int) $id;
        $ticket = (new SupportTicketModel())->find($id);
        if (! $ticket) {
            return redirect()->to('/admin/tickets')->with('error', 'Ticket not found.');
        }

        $status  = (string) $this->request->getPost('status');
        $subject = trim((string) $this->request->getPost('subject'));
        $payload = [];
        if ($subject !== '') {
            $payload['subject'] = $subject;
        }
        if (in_array($status, ['open', 'replied', 'closed'], true)) {
            $payload['status'] = $status;
        }
        if ($payload !== []) {
            (new SupportTicketModel())->update($id, $payload);
        }

        return redirect()->to('/admin/tickets/' . $id)->with('success', 'Ticket updated.');
    }

    public function delete($id)
    {
        $id = (int) $id;
        (new TicketReplyModel())->where('ticket_id', $id)->delete();
        (new SupportTicketModel())->delete($id);

        return redirect()->to('/admin/tickets')->with('success', 'Ticket deleted.');
    }
}
