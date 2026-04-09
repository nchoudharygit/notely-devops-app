<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use DI\Bridge\Slim\Bridge;

require __DIR__ . "/../vendor/autoload.php";

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . "/../config/container.php");
$container = $containerBuilder->build();

$app = Bridge::create($container);
$app->addBodyParsingMiddleware();
$app->addErrorMiddleware(true, true, true);

$routes = require __DIR__ . "/../config/routes.php";
$routes($app);

$app->run();