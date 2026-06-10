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

$router->before('GET',    '/api/rating/stats',   'AuthController@authenticate');
$router->get('/api/rating/stats',               'RateController@apiStats');
$router->before('GET',    '/api/rating/list',    'AuthController@authenticate');
$router->get('/api/rating/list',                'RateController@apiRatings');
$router->before('PATCH',  '/api/rating/status',  'AuthController@authenticate');
$router->patch('/api/rating/status',            'RateController@updateRatingStatus');
$router->before('DELETE', '/api/rating/delete',  'AuthController@authenticate');
$router->delete('/api/rating/delete',           'RateController@deleteRating');

// Employee CRUD API
$router->before('GET',    '/api/employee/list',            'AuthController@authenticate');
$router->get('/api/employee/list',                         'EmployeeController@apiListAll');
$router->before('POST',   '/api/employee/create',          'AuthController@authenticate');
$router->post('/api/employee/create',                      'EmployeeController@apiCreate');
$router->before('PATCH',  '/api/employee/update/(\d+)',    'AuthController@authenticate');
$router->patch('/api/employee/update/(\d+)',               'EmployeeController@apiUpdate');
$router->before('DELETE', '/api/employee/delete/(\d+)',    'AuthController@authenticate');
$router->delete('/api/employee/delete/(\d+)',              'EmployeeController@apiDelete');

// Survey data APIs
$router->before('GET', '/api/survey/skm', 'AuthController@authenticate');
$router->get('/api/survey/skm', 'RateController@apiSKM');
$router->before('GET', '/api/survey/zi', 'AuthController@authenticate');
$router->get('/api/survey/zi', 'RateController@apiZI');

$router->set404('ViewController@missing');
$router->run();
