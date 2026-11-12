<?php

namespace App\Http\Controllers;

use App\Services\AyolinxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AyolinxAccessTokenController extends Controller
{
    public function __construct(protected AyolinxService $ayolinx) {}

    public function __invoke(Request $request): JsonResponse
    {
        if (($request->input('grant_type') ?? $request->input('grantType')) !== 'client_credentials') {
            return response()->json([
                'responseCode' => '4007302',
                'responseMessage' => 'Invalid Mandatory Field grant_type',
            ], 400);
        }

        $token = $this->ayolinx->issueInboundAccessToken(
            (string) $request->header('X-CLIENT-KEY', ''),
            (string) $request->header('X-TIMESTAMP', ''),
            (string) $request->header('X-SIGNATURE', ''),
        );

        if ($token === null) {
            return response()->json([
                'responseCode' => '4017300',
                'responseMessage' => 'Unauthorized Signature',
            ], 401);
        }

        return response()->json([
            'responseCode' => '2007300',
            'responseMessage' => 'Successful',
            ...$token,
        ]);
    }
}
