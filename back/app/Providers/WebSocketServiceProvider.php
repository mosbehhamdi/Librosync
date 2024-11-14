<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\WebSocket\ReservationWebSocketHandler;

class WebSocketServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->singleton('websocket.server', function () {
            $ws = new WsServer(new ReservationWebSocketHandler());
            $server = IoServer::factory(
                new HttpServer($ws),
                6001
            );
            return $server;
        });
    }
}
