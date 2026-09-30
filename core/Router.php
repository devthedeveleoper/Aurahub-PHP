<?php
namespace Core;

class Router {
    protected $routes = [];

    public function add($route, $params = []) {
        // Convert route to regular expression
        $route = preg_replace('/\//', '\\/', $route);
        $route = preg_replace('/\{([a-z]+)\}/', '(?P<\1>[a-z-]+)', $route);
        $route = preg_replace('/\{([a-z]+):([^\}]+)\}/', '(?P<\1>\2)', $route);
        $route = '/^' . $route . '$/i';
        $this->routes[$route] = $params;
    }

    public function dispatch($url) {
        // Remove query string variables
        $url = explode('?', $url)[0];
        $url = trim($url, '/');

        foreach ($this->routes as $route => $params) {
            if (preg_match($route, $url, $matches)) {
                foreach ($matches as $key => $match) {
                    if (is_string($key)) {
                        $params[$key] = $match;
                    }
                }

                $controller = $params['controller'];
                $controller = "App\Controllers\\$controller";

                if (class_exists($controller)) {
                    $controller_object = new $controller($params);
                    $action = $params['action'];

                    if (is_callable([$controller_object, $action])) {
                        $controller_object->$action();
                        return;
                    } else {
                        throw new \Exception("Method $action in controller $controller not found");
                    }
                } else {
                    throw new \Exception("Controller class $controller not found");
                }
            }
        }
        
        // Handle 404
        http_response_code(404);
        $file = __DIR__ . '/../app/Views/errors/404.php';
        if (is_readable($file)) {
            require $file;
        } else {
            echo '404 Not Found';
        }
    }
}
