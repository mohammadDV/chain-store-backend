<?php

namespace Core\Http\traits;

use Domain\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

trait GlobalFunc
{
    /**
     * Check the level access
     * @param bool $conditions
     * @return void
     */
    public function checkLevelAccess(bool $condition = false) {

        if (!$condition && Auth::user()->level != 3) {
            throw New \Exception('Unauthorized', 403);
        }
    }

    /**
     * Check the level access
     * @param bool $conditions
     * @return bool
     */
    public function checkNickname(string $nickname, int $userId = 0) : bool {

        if (User::query()
            ->where('nickname', $nickname)
            ->when(!empty($userId), function ($query) use($userId) {
                $query->where('id', '!=', $userId);
            })
            ->count() > 0) {
                return false;
        }

        return true;
    }

    /**
     * Manually check if user is authenticated via Sanctum token and get user ID
     * This method doesn't use Auth facade or middleware
     *
     * @param Request $request
     * @return int|null Returns user ID if authenticated, null otherwise
     */
    public function getUserIdFromToken(Request $request): ?int
    {
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        $fullToken = substr($authHeader, 7); // Remove "Bearer " prefix

        // Sanctum tokens are in format: {id}|{token}
        // We need to extract only the token part (after the |)
        if (str_contains($fullToken, '|')) {
            $tokenParts = explode('|', $fullToken, 2);
            $token = $tokenParts[1]; // Get the token part after the |
        } else {
            $token = $fullToken; // Fallback if no | separator
        }

        $hashedToken = hash('sha256', $token);

        // Check if token exists in personal_access_tokens table
        $tokenRecord = DB::table('personal_access_tokens')
            ->where('token', $hashedToken)
            ->first();

        return $tokenRecord ? $tokenRecord->tokenable_id : null;
    }
}
;