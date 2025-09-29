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
        $loader = new FilesystemLoader(dirname(__DIR__, 2) . '/views');
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

    public function statistic()
    {
        $skms = $this->rate->viewRateSKM();
        $pgws = $this->rate->viewRateEmployee();
        $this->render('statistic.twig', ['skms' => $skms, 'pgws' => $pgws]);
    }

    public function missing()
    {
        $this->render('missing.twig');
    }
}
