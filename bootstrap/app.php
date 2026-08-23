<?php

use App\Http\Middleware\CacheControl;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RequestId;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // SecurityHeaders を先頭に置く（NFR-S1 / ADR-015）。
        // prepend することで、後続で例外が発生してエラーレスポンスに
        // 差し替わった場合でも、戻りの経路で必ずヘッダが付く。
        $middleware->prepend([
            SecurityHeaders::class,
            CacheControl::class,
            RequestId::class,
        ]);

        // セッションと CSRF を web グループから外す（P-1 / U1-PF-4 / NFR-S9）。
        //
        // このサイトには認証もフォームも POST も存在しない（ADR-004）ため、
        // セッションと CSRF トークンは機能上まったく使われない。
        // 一方でこれらが有効だと Set-Cookie が付き、CloudFront が HTML を
        // キャッシュしなくなる。キャッシュは濫用対策の一部でもある（ADR-013）ため、
        // 使っていない機能のために対策が効かなくなる状態を避ける。
        //
        // Inertia の errors 共有は $request->hasSession() で守られているため、
        // セッションが無くても動作する（vendor 側で確認済み）。
        // 注意: Laravel 13 では CSRF ミドルウェアの名称が
        // ValidateCsrfToken から PreventRequestForgery に変わっている。
        // 旧名で remove しても外れず、セッション不在で例外になる。
        $middleware->web(remove: [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            PreventRequestForgery::class,
        ]);

        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->expectsJson(),
        );

        // 本番では内部情報を一切返さず、固定文言のエラーページを表示する
        // （NFR-S6 / SECURITY-09, 15 / LC-5）。
        // ローカルでは Laravel の既定のデバッグ画面をそのまま使う。
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            if (app()->environment('local') || $request->expectsJson()) {
                return $response;
            }

            return Inertia::render('Error', [
                'status' => $response->getStatusCode(),
            ])
                ->toResponse($request)
                ->setStatusCode($response->getStatusCode());
        });
    })->create();
