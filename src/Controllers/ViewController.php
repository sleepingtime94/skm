<?php

namespace App\Controllers;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use App\Controllers\EmployeeController;

class ViewController
{

    protected $twig;
    private $employee;

    public function __construct()
    {
        $loader = new FilesystemLoader(dirname(__DIR__, 2) . '/views');
        $this->twig = new Environment($loader);
        $this->twig->addGlobal('session', $_SESSION);
        $this->employee = new EmployeeController();
    }

    public function render($view, $data = [])
    {
        echo $this->twig->render($view, $data);
    }

    public function home()
    {
        $this->render('home.twig');
    }

    public function quest()
    {
        $this->render('quest.twig');
    }

    public function employee()
    {
        $this->render('employee.twig');
    }

    public function employ($employee_id)
    {
        $datas = json_decode($this->employee->find($employee_id), true);
        $this->render('employ.twig', ['datas' => $datas]);
    }

    public function missing()
    {
        $this->render('missing.twig');
    }
}
