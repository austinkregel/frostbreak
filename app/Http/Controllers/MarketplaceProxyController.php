<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MarketplaceProxyController extends Controller
{
    public function install(Request $request)
    {
        // TODO: Implement install logic for marketplace proxy
        return response()->json(['status' => 'pending-implementation']);
    }

    public function proxy(Request $request, $segment1, $segment2 = null)
    {
        $endpoint = $segment1;
        if ($segment2) {
            $endpoint .= '/' . $segment2;
        }
        $winterUrl = "https://api.wintercms.com/marketplace/{$endpoint}";

        // Forward the request (POST or GET)
        if ($request->isMethod('post')) {
            $response = Http::post($winterUrl, $request->all());
        } else {
            $response = Http::get($winterUrl, $request->query());
        }

        Log::info('[Marketplace Proxy] Forwarded', [
            'endpoint' => $endpoint,
            'request' => $request->all(),
            'response' => $response->headers(),
            'body' => $response->body(),
            'status' => $response->status(),
        ]);

        return response($response->body(), $response->status())
            ->withHeaders($response->headers());
    }
}
