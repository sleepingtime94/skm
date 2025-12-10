<?php

use Bramus\Router\Router;

$router = new Router();
$router->setNamespace('App\Controllers');

$router->get('/', 'ViewController@home');

$router->before('GET', '/login', 'AuthController@logged');
$router->get('/login', 'ViewController@login');
$router->post('/login', 'AuthController@login');
$router->get('/logout', 'AuthController@logout');



$router->get('/survei-kepuasan-masyarakat', 'ViewController@questMain');
$router->get('/survei-pembangunan-zi', 'ViewController@questSecond');
$router->before('GET', '/statistik', 'AuthController@authenticate');
$router->get('/statistik', 'ViewController@statistic');

$router->get('/penilaian-pegawai', 'ViewController@employeeMain');
$router->get('/penilaian-pegawai/{employee_id}', 'ViewController@employeeDetail');

$router->get('/pegawai', 'EmployeeController@store');
$router->get('/pegawai/{employee_id}', 'EmployeeController@find');

$router->post('/submit/quest/{category}', 'RateController@submitQuest');
$router->post('/rating', 'RateController@employeeRate');

$router->set404('ViewController@missing');
$router->run();
