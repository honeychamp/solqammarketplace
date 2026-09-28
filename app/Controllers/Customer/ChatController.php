<?php

namespace App\Controllers\Customer;

use App\Controllers\BaseController;
use App\Models\ConversationModel;
use App\Models\MessageModel;
use App\Models\ProductModel;
use App\Models\UserModel;

class ChatController extends BaseController
{
    public function index()
    {
        $userId = (int) session()->get('user.id');
        $threads = (new ConversationModel())->forUser($userId, 'customer');

        return view('customer/chat/index', [
            'title'   => 'Messages — Solqam',
            'threads' => $threads,
        ]);
    }

    public function start()
    {
        $customerId = (int) session()->get('user.id');
        $sellerId   = (int) $this->request->getGet('seller_id');
        $productId  = $this->request->getGet('product_id') ? (int) $this->request->getGet('product_id') : null;

        if ($sellerId < 1 || $sellerId === $customerId) {
            return redirect()->back()->with('error', 'Invalid seller.');
        }

        $seller = (new UserModel())->find($sellerId);
        if (!$seller || $seller['role'] !== 'seller') {
            return redirect()->back()->with('error', 'Seller not found.');
        }

        if ($productId) {
            $product = (new ProductModel())->find($productId);
            if (!$product || (int) $product['seller_id'] !== $sellerId) {
                $productId = null;
            }
        }

        $conversation = (new ConversationModel())->findOrCreate($customerId, $sellerId, $productId);

        return redirect()->to('/messages/' . $conversation['id']);
    }

    public function show($id)
    {
        $userId = (int) session()->get('user.id');
        $conversation = (new ConversationModel())->find((int) $id);
        if (!$conversation || (int) $conversation['customer_id'] !== $userId) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Chat not found');
        }

        $messages = (new MessageModel())->where('conversation_id', (int) $id)->orderBy('id', 'ASC')->findAll();
        $seller = (new UserModel())->find($conversation['seller_id']);

        return view('customer/chat/show', [
            'title'        => 'Chat with seller — Solqam',
            'conversation' => $conversation,
            'messages'     => $messages,
            'peerName'     => $seller['name'] ?? 'Seller',
        ]);
    }

    public function send($id)
    {
        $userId = (int) session()->get('user.id');
        $conversation = (new ConversationModel())->find((int) $id);
        if (!$conversation || (int) $conversation['customer_id'] !== $userId) {
            return redirect()->to('/messages')->with('error', 'Chat not found.');
        }

        $body = trim((string) $this->request->getPost('body'));
        if ($body === '') {
            return redirect()->back()->with('error', 'Write a message first.');
        }

        (new MessageModel())->insert([
            'conversation_id' => (int) $id,
            'sender_id'       => $userId,
            'body'            => $body,
        ]);
        (new ConversationModel())->update((int) $id, ['last_message_at' => date('Y-m-d H:i:s')]);

        return redirect()->to('/messages/' . $id);
    }
}
