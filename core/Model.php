<?php
namespace Core;
use PDO;

abstract class Model {
    protected static function db() {
        return \db();
    }
}
