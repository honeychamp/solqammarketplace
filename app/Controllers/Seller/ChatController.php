<?php

namespace App\Controllers\Seller;

use App\Controllers\BaseController;
use App\Models\ConversationModel;
use App\Models\MessageModel;
use App\Models\UserModel;

class ChatController extends BaseController
{
    public function index()
    {
        $sellerId = (int) session()->get('user.id');
        $threads = (new ConversationModel())->forUser($sellerId, 'seller');

        return view('seller/chat/index', [
            'title'   => 'Inbox — Seller Hub',
            'threads' => $threads,
        ]);
    }

    public function show($id)
    {
        $sellerId = (int) session()->get('user.id');
        $conversation = (new ConversationModel())->find((int) $id);
        if (!$conversation || (int) $conversation['seller_id'] !== $sellerId) {
            return redirect()->to('/seller/messages')->with('error', 'Chat not found.');
        }

        $messages = (new MessageModel())->where('conversation_id', (int) $id)->orderBy('id', 'ASC')->findAll();
        $buyer = (new UserModel())->find($conversation['customer_id']);

        return view('seller/chat/show', [
            'title'        => 'Buyer chat — Seller Hub',
            'conversation' => $conversation,
            'messages'     => $messages,
            'peerName'     => $buyer['name'] ?? 'Buyer',
        ]);
    }

    public function send($id)
    {
        $sellerId = (int) session()->get('user.id');
        $conversation = (new ConversationModel())->find((int) $id);
        if (!$conversation || (int) $conversation['seller_id'] !== $sellerId) {
            return redirect()->to('/seller/messages')->with('error', 'Chat not found.');
        }

        $body = trim((string) $this->request->getPost('body'));
        if ($body === '') {
            return redirect()->back()->with('error', 'Write a message first.');
        }

        (new MessageModel())->insert([
            'conversation_id' => (int) $id,
            'sender_id'       => $sellerId,
            'body'            => $body,
        ]);
        (new ConversationModel())->update((int) $id, ['last_message_at' => date('Y-m-d H:i:s')]);

        return redirect()->to('/seller/messages/' . $id);
    }
}
