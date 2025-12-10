<?php

namespace App\Controllers;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use App\Controllers\EmployeeController;
use App\Controllers\RateController;

class ViewController
{
    protected $twig;
    private $employee;
    private $rate;

    public function __construct()
    {
        $loader = new FilesystemLoader(dirname(__DIR__, 1) . '/Views');
        $this->twig = new Environment($loader);
        $this->twig->addGlobal('session', $_SESSION);
        $this->employee = new EmployeeController();
        $this->rate = new RateController();
    }

    public function render($view, $data = [])
    {
        echo $this->twig->render($view, $data);
    }

    public function home()
    {
        $this->render('home.twig');
    }

    public function questMain()
    {
        $this->render('quest/main.twig');
    }

    public function questSecond()
    {
        $this->render('quest/second.twig');
    }

    public function employeeMain()
    {
        $this->render('employee/main.twig');
    }

    public function employeeDetail($employee_id)
    {
        $datas = json_decode($this->employee->find($employee_id), true);
        $this->render('employee/detail.twig', ['datas' => $datas]);
    }


    public function statistic($month = null, $year = null)
    {
        // Ambil dari query string jika tidak lewat route
        if ($month === null) {
            $month = $_GET['month'] ?? date('m');   // DEFAULT: bulan sekarang
        }

        if ($year === null) {
            $year = $_GET['year'] ?? date('Y');     // DEFAULT: tahun sekarang
        }

        // Ambil data sesuai month & year
        $skms = $this->rate->viewRateSKM($month, $year);
        $zis  = $this->rate->viewRateZI($month, $year);

        $this->render('statistic.twig', [
            'skms'  => $skms,
            'zis'   => $zis,
            'month' => $month,
            'year'  => $year,
        ]);
    }

    public function login()
    {
        $this->render('login.twig');
    }

    public function missing()
    {
        $this->render('missing.twig');
    }
}
