<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    /**
     * Signed, expiring route opened straight from the email.
     *
     * The link lands in a browser, so the default answer is a redirect back to
     * the Next.js app; JSON is returned when the caller asks for it.
     */
    public function verify(Request $request, string $id, string $hash): JsonResponse|RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            return $this->fail($request, 'invalid');
        }

        $user = User::find($id);

        // A wrong id or hash is reported the same way as an expired signature so
        // the endpoint reveals nothing about which accounts exist.
        if (! $user || ! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return $this->fail($request, 'invalid');
        }

        if ($user->hasVerifiedEmail()) {
            return $this->succeed($request, 'already-verified', 'Email address is already verified.');
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return $this->succeed($request, 'verified', 'Email verified successfully.');
    }

    /**
     * Re-sends the verification email to the authenticated user.
     */
    public function resend(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return ApiResponse::success('Email address is already verified.');
        }

        $user->sendEmailVerificationNotification();

        return ApiResponse::success('Verification link sent.');
    }

    private function succeed(Request $request, string $status, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return ApiResponse::success($message);
        }

        return redirect()->to($this->frontendUrl($status));
    }

    private function fail(Request $request, string $status): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            throw ApiException::invalidVerificationLink();
        }

        return redirect()->to($this->frontendUrl($status));
    }

    private function frontendUrl(string $status): string
    {
        return rtrim(config('app.frontend_url'), '/')
            .'/auth/email-verified?status='.$status;
    }
}
