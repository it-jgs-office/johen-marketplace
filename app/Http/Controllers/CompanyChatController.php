<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CompanyChatController extends Controller
{
    public function handle(Request $request, string $path)
    {
        $guest = str_starts_with($path, 'guest/');
        if (! $guest) abort_unless($request->user(), 401);
        $path = preg_replace('#^guest/#', '', $path);
        $method = $request->method();
        $data = $request->only(['message', 'message_type', 'reply_to_message_id', 'media_duration', 'emoji']);
        if ($path === 'channels' && $method === 'GET') {
            $data['action'] = 'channels';
        } elseif ($path === 'unread' && $method === 'GET') {
            $data['action'] = 'unread';
        } elseif ($path === 'conversation' && $method === 'POST') {
            $data += ['action' => 'open', 'channel' => $request->input('channel_slug', 'johen-cs')];
        } elseif (preg_match('#^conversation/([a-z_-]+)$#', $path, $match) && $method === 'GET') {
            $data += ['action' => 'open', 'channel' => $match[1]];
        } elseif (preg_match('#^conversation/(\d+)/read$#', $path, $match) && $method === 'PATCH') {
            $data += ['action' => 'read', 'conversation_id' => $match[1]];
        } elseif (preg_match('#^conversation/(\d+)/route-back$#', $path, $match) && $method === 'POST') {
            $data += ['action' => 'open', 'channel' => 'johen-cs'];
        } elseif ($path === 'messages' && $method === 'POST') {
            $data += ['action' => 'send', 'conversation_id' => $request->input('conversation_id')];
        } elseif (preg_match('#^messages/(\d+)$#', $path, $match) && in_array($method, ['GET', 'DELETE'], true)) {
            $data += $method === 'GET' ? ['action' => 'messages', 'conversation_id' => $match[1]] : ['action' => 'delete', 'message_id' => $match[1]];
        } elseif (preg_match('#^messages/(\d+)/(hide|star|reaction)$#', $path, $match) && $method === 'POST') {
            $data += ['action' => $match[2], 'message_id' => $match[1]];
        } else {
            abort(404);
        }
        $key = (string) config('services.jba_api.clients.marketplace.key');
        abort_if($key === '', 503, 'Koneksi chat company belum dikonfigurasi.');
        if ($guest) {
            $request->validate(['guest_id' => 'nullable|string|max:64']);
            // Token browser hanya memilih sesi milik browser ini, bukan identitas user company.
            $clientId = (string) $request->input('guest_id', 'default');
            $sessionKey = 'company_chat.'.hash('sha256', $clientId);
            if (! $request->session()->has($sessionKey)) {
                $request->session()->put($sessionKey, Str::random(64));
            }
            $identity = 'guest:'.$request->session()->get($sessionKey);
            $name = $request->session()->get($sessionKey.'_name', 'Tamu Marketplace');
            if ($data['action'] === 'open') {
                $contact = $request->validate([
                    'guest_name' => 'required|string|min:2|max:60',
                    'guest_email' => 'required|email|max:255',
                    'guest_whatsapp' => 'nullable|string|max:30',
                ]);
                $name = $contact['guest_name'];
                $request->session()->put($sessionKey.'_name', $name);
                $data['email'] = mb_strtolower(trim($contact['guest_email']));
                $data['whatsapp'] = trim((string) ($contact['guest_whatsapp'] ?? '')) ?: null;
            }
        } else {
            $identity = 'user:'.$request->user()->id;
            $name = mb_substr($request->user()->name, 0, 60);
        }
        $data += ['identity' => hash_hmac('sha256', $identity, (string) config('app.key')), 'name' => $name];
        $request->validate(['media' => 'nullable|array|max:10', 'media.*' => 'file|max:204800']);
        $remote = Http::acceptJson()->withToken($key)->connectTimeout(5)->timeout(60);
        foreach ($request->file('media', []) as $index => $file) {
            $remote->attach('media['.$index.']', fopen($file->getRealPath(), 'r'), $file->getClientOriginalName());
        }
        try {
            $response = $remote->post(rtrim(config('services.jba_api.base_url'), '/').'/marketplace/chat', array_filter($data, fn ($value) => $value !== null));
        } catch (\Illuminate\Http\Client\ConnectionException $exception) {
            return response()->json(['message' => 'Chat company belum dapat dihubungi. Silakan coba lagi.'], 503);
        }
        if (! $response->successful()) {
            if ($response->serverError()) {
                return response()->json(['message' => 'Chat company sedang tidak tersedia. Silakan coba lagi.'], 503);
            }
            return response()->json(['message' => $response->json('message') ?: $response->json('error') ?: 'Chat gagal diproses.'], $response->status());
        }
        return response()->json($response->json());
    }
}
