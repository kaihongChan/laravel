<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AuthController extends Controller
{

    /**
     * 用户名密码登录
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function loginWithPassword(Request $request)
    {
        // 表单验证
        $validator = validator($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ], [
            'username.required' => '用户名必填！',
            'username.string' => '用户名格式错误！',
            'password.required' => '密码必填！',
            'password.string' => '密码格式错误！',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        // 用户验证
        $username = $request->input('username');
        $password = $request->input('password');
        $user = (new User())->findForPassport($username);
        if (!$user) {
            return response()->json([
                'message' => '用户不存在！'
            ], Response::HTTP_UNAUTHORIZED);
        }
        if (!Hash::check($password, $user->password)) {
            return response()->json([
                'message' => '密码错误！'
            ], Response::HTTP_UNAUTHORIZED);
        }
        if ($user->status == 0) {
            return response()->json([
                'message' => '用户已被禁用！'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // 请求转发
        $httpClient = new Client();
        try {
            $response = $httpClient->post(route('passport.token'), [
                'form_params' => array_merge(config('passport.admin.password'), [
                    'username' => $username,
                    'password' => $password,
                ]),
            ]);
        } catch (RequestException $e) {
            throw new UnauthorizedHttpException('', '用户认证失败！');
        }
        if ($response->getStatusCode() == 401) {
            throw new UnauthorizedHttpException('', '用户认证失败!');
        }

        $token = json_decode($response->getBody()->getContents(), true);

        return \response()->json([
            'message' => '登录成功！',
            'data' => [
                'user' => $user,
                'token' => $token,
            ]
        ]);
    }


    public function refreshToken()
    {

    }

    /**
     * 注销登录
     *
     * @return JsonResponse
     */
    public function logout()
    {
        if (Auth::guard('api')->check()) {
            Auth::guard('api')->user()->token()->delete();
        }

        return response()->json([
            'message' => '注销成功'
        ]);
    }
}
