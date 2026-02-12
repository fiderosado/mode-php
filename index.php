<?php
date_default_timezone_set('America/Havana');

require 'autoload.php';

use Core\Router;
use Core\Utils\GzipCompressor;

GzipCompressor::start();
Router::handle();
GzipCompressor::end();
