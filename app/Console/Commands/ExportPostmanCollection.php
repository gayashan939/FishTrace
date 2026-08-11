<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use ReflectionMethod;
use Throwable;

class ExportPostmanCollection extends Command
{
    protected $signature = 'fishtrace:export-postman {--path=postman : Directory, relative to the project root, for the generated files}';

    protected $description = 'Export the current FishTrace API route contract as a Postman collection and local environment';

    public function handle(Router $router, Filesystem $files): int
    {
        $directory = base_path(trim((string) $this->option('path'), '/\\'));
        $files->ensureDirectoryExists($directory);

        $routes = collect($router->getRoutes()->getRoutes())
            ->filter(fn (Route $route): bool => str_starts_with($route->uri(), 'api/'))
            ->sortBy(fn (Route $route): string => $route->uri().'|'.implode('|', $route->methods()))
            ->values();

        $collection = [
            'info' => [
                '_postman_id' => 'a7d3e6e4-5e92-4d0f-bdb6-fishtrace-api-v1',
                'name' => 'FishTrace API',
                'description' => 'Generated from the registered FishTrace API routes. Run the Login request first to store `accessToken`. Mutating requests include their Form Request class in the description; use its validation rules as the authoritative payload contract.',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'auth' => ['type' => 'bearer', 'bearer' => [['key' => 'token', 'value' => '{{accessToken}}', 'type' => 'string']]],
            'variable' => $this->collectionVariables($routes),
            'item' => $routes
                ->groupBy(fn (Route $route): string => $this->folderName($route->uri()))
                ->map(fn ($folderRoutes, string $folder): array => [
                    'name' => $folder,
                    'item' => $folderRoutes->map(fn (Route $route): array => $this->request($route))->values()->all(),
                ])
                ->values()
                ->all(),
        ];

        $environment = [
            'id' => 'fishtrace-local-environment',
            'name' => 'FishTrace Local',
            'values' => [
                ['key' => 'baseUrl', 'value' => 'http://localhost:8002', 'enabled' => true],
                ['key' => 'accessToken', 'value' => '', 'enabled' => true],
                ['key' => 'demoEmail', 'value' => 'admin@fishtrace.demo', 'enabled' => true],
                ['key' => 'demoPassword', 'value' => 'FishTrace@2026', 'enabled' => true],
            ],
            '_postman_variable_scope' => 'environment',
            '_postman_exported_using' => 'FishTrace route exporter',
        ];

        $collectionPath = $directory.DIRECTORY_SEPARATOR.'FishTrace.postman_collection.json';
        $environmentPath = $directory.DIRECTORY_SEPARATOR.'FishTrace.local.postman_environment.json';
        $files->put($collectionPath, json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
        $files->put($environmentPath, json_encode($environment, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);

        $this->info("Exported {$routes->count()} API requests.");
        $this->line('Collection: '.$collectionPath);
        $this->line('Environment: '.$environmentPath);

        return self::SUCCESS;
    }

    /** @return list<array{key: string, value: string}> */
    private function collectionVariables($routes): array
    {
        return $routes
            ->flatMap(fn (Route $route): array => $route->parameterNames())
            ->unique()
            ->sort()
            ->map(fn (string $parameter): array => ['key' => $parameter, 'value' => 'REPLACE_'.$parameter])
            ->prepend(['key' => 'baseUrl', 'value' => '{{baseUrl}}'])
            ->values()
            ->all();
    }

    private function folderName(string $uri): string
    {
        $segments = explode('/', preg_replace('#^api/v1/#', '', $uri) ?? $uri);

        return match ($segments[0] ?? '') {
            'auth' => 'Authentication',
            'public' => 'Public trace',
            'admin' => 'Administration',
            default => ucfirst(str_replace('-', ' ', $segments[0] ?? 'API')),
        };
    }

    /** @return array<string, mixed> */
    private function request(Route $route): array
    {
        $method = collect($route->methods())->reject(fn (string $method): bool => $method === 'HEAD')->first() ?? 'GET';
        $uri = preg_replace_callback('/\{([^}]+)\}/', fn (array $matches): string => '{{'.$matches[1].'}}', $route->uri()) ?? $route->uri();
        $requiresAuthentication = collect($route->gatherMiddleware())->contains(fn (string $middleware): bool => str_contains($middleware, 'auth:sanctum'));
        $formRequest = $this->formRequest($route);
        $request = [
            'name' => $method.' /'.$route->uri(),
            'request' => [
                'method' => $method,
                'header' => [['key' => 'Accept', 'value' => 'application/json']],
                'url' => ['raw' => '{{baseUrl}}/'.$uri, 'host' => ['{{baseUrl}}'], 'path' => explode('/', $uri)],
                'description' => $this->description($route, $formRequest),
            ],
            'response' => [],
        ];

        if (! $requiresAuthentication) {
            $request['request']['auth'] = ['type' => 'noauth'];
        }

        if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $request['request']['header'][] = ['key' => 'Content-Type', 'value' => 'application/json'];
            $request['request']['body'] = ['mode' => 'raw', 'raw' => $this->exampleBody($route), 'options' => ['raw' => ['language' => 'json']]];
        }

        if ($route->uri() === 'api/v1/auth/login') {
            $request['event'] = [['listen' => 'test', 'script' => ['type' => 'text/javascript', 'exec' => ['const response = pm.response.json();', "pm.environment.set('accessToken', response.data.token);"]]]];
        }

        return $request;
    }

    private function description(Route $route, ?string $formRequest): string
    {
        $action = $route->getActionName();
        $requestContract = $formRequest === null ? 'No dedicated Form Request.' : 'Validation: `'.$formRequest.'`.';

        return "Controller action: `{$action}`. {$requestContract}";
    }

    private function formRequest(Route $route): ?string
    {
        $action = $route->getActionName();
        if (! str_contains($action, '@')) {
            return null;
        }

        [$controller, $method] = explode('@', $action, 2);
        try {
            foreach ((new ReflectionMethod($controller, $method))->getParameters() as $parameter) {
                $type = $parameter->getType();
                $class = $type instanceof \ReflectionNamedType ? $type->getName() : null;
                if ($class !== null && is_a($class, FormRequest::class, true)) {
                    return $class;
                }
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    private function exampleBody(Route $route): string
    {
        return match ($route->uri()) {
            'api/v1/auth/login' => json_encode(['email' => '{{demoEmail}}', 'password' => '{{demoPassword}}', 'device_name' => 'Postman'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            default => '{}',
        };
    }
}
