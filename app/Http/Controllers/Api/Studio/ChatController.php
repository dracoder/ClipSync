<?php

namespace App\Http\Controllers\Api\Studio;

use App\Http\Controllers\Controller;
use App\Http\Requests\Studio\Chat\ChatRequest;
use App\Http\Resources\Studio\Chats\ChatCollection;
use App\Http\Resources\Studio\Chats\ChatResource;
use App\Repositories\Studio\ChatRepository;
use App\Repositories\Studio\RoomRepository; 
use Illuminate\Http\Request;

class ChatController extends Controller
{
    protected $chatRepository;

    public function __construct()
    {
        $this->chatRepository = new ChatRepository();
    }

    public function index(Request $request,$slug)
    {
        $search = $request->get('search', '');
        $filters = $request->get('filters', []);
        $orderBy = $request->get('orderBy', []);
        $perPage = $request->get('perPage', null);
        $with = $request->get('with', []);

        $chats = $this->chatRepository->getList($search, $filters, $orderBy, $perPage, $with , $slug);

        $data = [
            'chats' => new ChatCollection($chats),
        ];

        return $this->response(true, $data, 'Chats retrieved successfully');
    }


    public function store(ChatRequest $request , $slug)
    {
        $data = $request->all();
        $data['sender_id'] = auth()->guard('sanctum')->id();
        $chat = $this->chatRepository->saveByRoomSlug($data , $slug);
        $data = [
            'chat' => new ChatResource($chat),
        ];
        return $this->response(true, $data, 'Chat created successfully', 201);
    }


    public function destroy($slug)
    {
        $chat = $this->chatRepository->deleteByRoomSlug($slug);
        
        if (!$chat) {
            return $this->response(false, null, 'Failed to delete chat', 400);
        }
        return $this->response(true, [] , 'Chat deleted successfully');
    }

 
    public function export($slug)
    {
        try {
            $messages = $this->chatRepository->getList(null, null, null, null, null, $slug);
    
            if ($messages->isEmpty()) {
                return $this->response(false, null, 'No chat messages found', 404);
            }
    
            $textContent = '';
            foreach ($messages as $message) {
                $textContent .= "[{$message->sender_name}]:\n";
                $textContent .= "{$message->message}\n\n";
            }
    
            return $this->response(true, $textContent, 'Chat messages exported successfully');
        } catch (\Exception $e) {
            log_error($e);
            return $this->response(false, [], __('messages.model_action.export_error', ['attribute' => 'chat']), 500);
        }
    }

}
