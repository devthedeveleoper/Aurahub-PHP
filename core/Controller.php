<?php
namespace Core;

abstract class Controller {
    protected $route_params = [];

    public function __construct($route_params) {
        $this->route_params = $route_params;
    }

    public function view($view, $args = []) {
        extract($args, EXTR_SKIP);
        $file = __DIR__ . "/../app/Views/$view.php";

        if (is_readable($file)) {
            require $file;
        } else {
            throw new \Exception("View $file not found");
        }
    }
}
