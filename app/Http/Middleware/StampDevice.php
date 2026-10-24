<?php

namespace App\Http\Middleware;

use App\Services\Access\DeviceStamp;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StampDevice
{
    public function __construct(private readonly DeviceStamp $devices) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->devices->token($request);
        $response = $next($request);
        $this->devices->attach($request, $response);

        return $response;
    }
}
