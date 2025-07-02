<?php

namespace App\Controllers;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class ViewController
{

    protected $twig;

    public function __construct()
    {
        $loader = new FilesystemLoader(dirname(__DIR__, 2) . '/views');
        $this->twig = new Environment($loader);
        $this->twig->addGlobal('session', $_SESSION);
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

    public function missing()
    {
        $this->render('missing.twig');
    }
}
