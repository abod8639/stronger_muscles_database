<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "Stronger Muscles API",
    description: "API documentation for Stronger Muscles Store",
    contact: new OA\Contact(email: "support@strongermuscles.com"),
    license: new OA\License(name: "Apache 2.0", url: "http://www.apache.org/licenses/LICENSE-2.0.html")
)]
#[OA\Server(url: "http://localhost:8080", description: "Local API Server")]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT",
    description: "Enter token in format: Bearer {token}"
)]
abstract class Controller
{
    //
}
