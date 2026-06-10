<?php

use Bramus\Router\Router;
use App\Utility\Security;

// Inisialisasi CSRF token di awal setiap request
Security::csrfToken();

$router = new Router();
$router->setNamespace('App\Controllers');

$router->get('/', 'ViewController@home');

$router->before('GET', '/login', 'AuthController@logged');
$router->get('/login', 'ViewController@login');
$router->post('/login', 'AuthController@login');

// Logout via POST untuk mencegah CSRF logout attack
$router->post('/logout', 'AuthController@logout');
// Route untuk menyajikan berkas gambar pegawai dari folder root /storage/uploads
$router->get('/storage/uploads/(.*)', function ($filename) {
    $path = dirname(__DIR__, 2) . '/storage/uploads/' . $filename;
    if (file_exists($path) && is_file($path)) {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp'
        ];
        $contentType = $mimes[$ext] ?? 'application/octet-stream';
        header('Content-Type: ' . $contentType);
        readfile($path);
        exit;
    }
    header("HTTP/1.0 404 Not Found");
    echo "File not found.";
    exit;
});

$router->get('/survei-kepuasan-masyarakat', 'ViewController@questMain');
$router->get('/survei-pembangunan-zi', 'ViewController@questSecond');
$router->before('GET', '/dashboard', 'AuthController@authenticate');
$router->get('/dashboard', 'ViewController@dashboard');

// Route /pegawai dilindungi autentikasi (berisi NIP/NIK/data PII)
$router->before('GET', '/penilaian-pegawai', 'AuthController@authenticate');
$router->get('/penilaian-pegawai', 'ViewController@employeeMain');
$router->before('GET', '/penilaian-pegawai/(.*)', 'AuthController@authenticate');
$router->get('/penilaian-pegawai/{employee_id}', 'ViewController@employeeDetail');

$router->before('GET', '/pegawai', 'AuthController@authenticate');
$router->get('/pegawai', 'EmployeeController@store');
$router->before('GET', '/pegawai/(.*)', 'AuthController@authenticate');
$router->get('/pegawai/{employee_id}', 'EmployeeController@find');

// Endpoint publik survei — rate limited di controller
$router->post('/submit/quest/{category}', 'RateController@submitQuest');
$router->post('/rating', 'RateController@employeeRate');

// API admin — wajib autentikasi + CSRF
$router->before('GET',    '/api/rating/stats',   'AuthController@authenticate');
$router->get('/api/rating/stats',               'RateController@apiStats');
$router->before('GET',    '/api/rating/list',    'AuthController@authenticate');
$router->get('/api/rating/list',                'RateController@apiRatings');

$router->before('DELETE', '/api/rating/delete',  'AuthController@authenticate');
$router->before('DELETE', '/api/rating/delete',  function () {
    \App\Utility\Security::verifyCsrf();
});
$router->delete('/api/rating/delete',           'RateController@deleteRating');

// Employee CRUD API — wajib autentikasi + CSRF
$router->before('GET',    '/api/employee/list',            'AuthController@authenticate');
$router->get('/api/employee/list',                         'EmployeeController@apiListAll');

$router->before('POST',   '/api/employee/create',          'AuthController@authenticate');
$router->before('POST',   '/api/employee/create',          function () {
    \App\Utility\Security::verifyCsrf();
});
$router->post('/api/employee/create',                      'EmployeeController@apiCreate');

$router->before('POST',  '/api/employee/update/(\d+)',    'AuthController@authenticate');
$router->before('POST',  '/api/employee/update/(\d+)',    function () {
    \App\Utility\Security::verifyCsrf();
});
$router->post('/api/employee/update/(\d+)',               'EmployeeController@apiUpdate');

$router->before('DELETE', '/api/employee/delete/(\d+)',    'AuthController@authenticate');
$router->before('DELETE', '/api/employee/delete/(\d+)',    function () {
    \App\Utility\Security::verifyCsrf();
});
$router->delete('/api/employee/delete/(\d+)',              'EmployeeController@apiDelete');

// Survey data APIs
$router->before('GET', '/api/survey/skm', 'AuthController@authenticate');
$router->get('/api/survey/skm', 'RateController@apiSKM');
$router->before('GET', '/api/survey/zi', 'AuthController@authenticate');
$router->get('/api/survey/zi', 'RateController@apiZI');

$router->set404('ViewController@missing');
$router->run();
