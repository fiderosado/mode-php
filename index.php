<?php
require 'autoload.php';

use Core\Router;
use Core\Utils\GzipCompressor;

GzipCompressor::start();
Router::handle();
GzipCompressor::end();
