<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\AuthenticateUser;
use App\Actions\Auth\ChangeAuthenticatedPassword;
use App\Actions\Auth\CompletePasswordReset;
use App\Actions\Auth\LogoutAllTokens;
use App\Actions\Auth\LogoutCurrentToken;
use App\Actions\Auth\RequestPasswordReset;
use App\Actions\Auth\UpdateAuthenticatedProfile;
use App\Actions\Auth\VerifyPasswordResetOtp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Requests\Auth\VerifyPasswordResetOtpRequest;
use App\Http\Resources\UserResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(LoginRequest $request, AuthenticateUser $action): JsonResponse
    {
        $result = $action->execute($request->string('email')->toString(), $request->string('password')->toString(), $request->string('device_name')->toString());

        return ApiResponse::data(['token' => $result['token'], 'token_type' => 'Bearer', 'user' => (new UserResource($result['user']))->resolve($request)]);
    }

    public function me(Request $request): JsonResponse
    {
        $request->user()->loadMissing(['roles', 'organizations']);

        return ApiResponse::data((new UserResource($request->user()))->resolve($request));
    }

    public function updateProfile(UpdateProfileRequest $request, UpdateAuthenticatedProfile $action): JsonResponse
    {
        return ApiResponse::data(new UserResource($action->execute($request->user(), $request->validated())));
    }

    public function logout(Request $request, LogoutCurrentToken $action): JsonResponse
    {
        $action->execute($request->user());

        return ApiResponse::data(['message' => 'Logged out.']);
    }

    public function logoutAll(Request $request, LogoutAllTokens $action): JsonResponse
    {
        $action->execute($request->user());

        return ApiResponse::data(['message' => 'Logged out from all devices.']);
    }

    public function changePassword(ChangePasswordRequest $request, ChangeAuthenticatedPassword $action): JsonResponse
    {
        $action->execute($request->user(), $request->string('password')->toString());

        return ApiResponse::data(['message' => 'Password changed. Sign in again.']);
    }

    public function forgotPassword(ForgotPasswordRequest $request, RequestPasswordReset $action): JsonResponse
    {
        $action->execute($request->string('email')->toString());

        return ApiResponse::data(['message' => 'If that account exists, a verification code has been sent.']);
    }

    public function verifyOtp(VerifyPasswordResetOtpRequest $request, VerifyPasswordResetOtp $action): JsonResponse
    {
        $result = $action->execute($request->string('email')->toString(), $request->string('otp')->toString());

        return ApiResponse::data($result);
    }

    public function resetPassword(ResetPasswordRequest $request, CompletePasswordReset $action): JsonResponse
    {
        $action->execute(
            $request->string('email')->toString(),
            $request->string('reset_token')->toString(),
            $request->string('password')->toString(),
        );

        return ApiResponse::data(['message' => 'Password reset successfully.']);
    }
}
