<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var list<array{method:string,pattern:string,regex:string,handler:callable|array{class-string,string},middleware:list<string>,params:list<string>}> */
    private array $routes = [];

    /** @var list<string> */
    private array $groupMiddleware = [];

    private string $groupPrefix = '';

    public function get(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function put(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('PUT', $path, $handler, $middleware);
    }

    public function patch(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('PATCH', $path, $handler, $middleware);
    }

    public function delete(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('DELETE', $path, $handler, $middleware);
    }

    /**
     * @param callable(self): void $callback
     */
    public function group(string $prefix, callable $callback, array $middleware = []): void
    {
        $previousPrefix = $this->groupPrefix;
        $previousMiddleware = $this->groupMiddleware;

        $this->groupPrefix = rtrim($previousPrefix . '/' . trim($prefix, '/'), '/');
        $this->groupMiddleware = array_merge($previousMiddleware, $middleware);

        $callback($this);

        $this->groupPrefix = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    private function add(string $method, string $path, callable|array $handler, array $middleware): void
    {
        $fullPath = rtrim($this->groupPrefix . '/' . trim($path, '/'), '/');

        if ($fullPath === '') {
            $fullPath = '/';
        }

        $params = [];
        $regex = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            static function (array $matches) use (&$params): string {
                $params[] = $matches[1];

                return '([^/]+)';
            },
            $fullPath
        );

        $this->routes[] = [
            'method' => $method,
            'pattern' => $fullPath,
            'regex' => '#^' . $regex . '$#',
            'handler' => $handler,
            'middleware' => array_merge($this->groupMiddleware, $middleware),
            'params' => $params,
        ];
    }

    public function dispatch(Request $request): Response
    {
        $path = $request->path();
        $allowed = [];

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            if ($route['method'] !== $request->method()) {
                $allowed[] = $route['method'];
                continue;
            }

            $params = [];
            foreach ($route['params'] as $index => $name) {
                $params[$name] = rawurldecode($matches[$index + 1]);
            }

            $request->setRouteParams($params);

            return $this->run($route['middleware'], $request, $route['handler']);
        }

        if ($allowed !== []) {
            throw new HttpException(
                'Metode ' . $request->method() . ' tidak diizinkan untuk ' . $path,
                405
            );
        }

        throw HttpException::notFound('Endpoint ' . $path . ' tidak ditemukan');
    }

    /**
     * @param list<string> $middleware
     * @param callable|array{class-string,string} $handler
     */
    private function run(array $middleware, Request $request, callable|array $handler): Response
    {
        $pipeline = array_reduce(
            array_reverse($middleware),
            static function (callable $next, string $definition): callable {
                return static function (Request $request) use ($definition, $next): Response {
                    [$class, $args] = self::parseMiddleware($definition);

                    return $class::handle($request, $args, $next);
                };
            },
            static function (Request $request) use ($handler): Response {
                if (is_array($handler)) {
                    [$class, $method] = $handler;
                    $controller = new $class();

                    return $controller->{$method}($request);
                }

                return $handler($request);
            }
        );

        return $pipeline($request);
    }

    /** @return array{class-string, list<string>} */
    private static function parseMiddleware(string $definition): array
    {
        $parts = explode(':', $definition, 2);
        $class = $parts[0];

        $aliases = [
            'auth' => Middleware\AuthMiddleware::class,
            'permission' => Middleware\PermissionMiddleware::class,
        ];

        if (isset($aliases[$class])) {
            $class = $aliases[$class];
        } elseif (!str_contains($class, '\\')) {
            $class = 'App\\Core\\Middleware\\' . $class;
        }

        $args = isset($parts[1]) ? explode(',', $parts[1]) : [];

        if (!class_exists($class)) {
            throw new HttpException('Middleware tidak ditemukan: ' . $class, 500);
        }

        return [$class, $args];
    }
}
