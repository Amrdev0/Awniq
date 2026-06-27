<?php

namespace App\Services;

use App\Models\IdempotencyKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IdempotencyService
{
    public function storedResponse(Request $request): ?JsonResponse
    {
        $key = $request->header('Idempotency-Key');

        if (! $key) {
            return null;
        }

        $record = $this->findRecord($request, $key);

        if (! $record) {
            return null;
        }

        abort_unless($record->request_hash === $this->requestHash($request), 409, 'Idempotency key was already used with a different request payload.');

        if ($record->response_code && $record->response_body !== null) {
            return response()->json($record->response_body, $record->response_code);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $body
     */
    public function rememberResponse(Request $request, int $statusCode, array $body): void
    {
        $key = $request->header('Idempotency-Key');

        if (! $key) {
            return;
        }

        IdempotencyKey::updateOrCreate(
            [
                'organization_id' => $request->user()?->organization_id,
                'user_id' => $request->user()?->id,
                'route' => $request->path(),
                'key' => $key,
            ],
            [
                'method' => $request->method(),
                'request_hash' => $this->requestHash($request),
                'response_code' => $statusCode,
                'response_body' => $body,
                'locked_until' => null,
            ],
        );
    }

    private function findRecord(Request $request, string $key): ?IdempotencyKey
    {
        return IdempotencyKey::query()
            ->where('organization_id', $request->user()?->organization_id)
            ->where('user_id', $request->user()?->id)
            ->where('route', $request->path())
            ->where('key', $key)
            ->first();
    }

    private function requestHash(Request $request): string
    {
        return hash('sha256', json_encode($request->all(), JSON_THROW_ON_ERROR));
    }
}
