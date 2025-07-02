<?php

use Bramus\Router\Router;

$router = new Router();
$router->setNamespace('App\Controllers');
$router->get('/', 'ViewController@home');
$router->get('/penilaian-layanan', 'ViewController@quest');
$router->get('/penilaian-pegawai', 'ViewController@employee');
$router->post('/survey/submit', 'RateController@submitRate');
$router->get('/pegawai', 'EmployeeController@store');
$router->get('/pegawai/{employee_id}', 'EmployeeController@find');
$router->set404('ViewController@missing');
$router->run();
