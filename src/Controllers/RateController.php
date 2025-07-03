<?php

namespace App\Controllers;

use App\Utility\MySQL;

class RateController
{
    protected $db;

    public function __construct()
    {
        $this->db = new MySQL();
    }

    public function submitRate()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (isset($data['type']) && $data['type'] == 'create') {
            if (isset($data['params'])) {
                $this->db->create('skm', $data['params']);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Terimakasih atas penilaian anda, ini akan menjadi bahan evaluasi kami kedepannya.'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Terjadi kesalahan input.'
                ]);
            }
        }

        if (isset($data['type']) && $data['type'] == 'select') {
            echo json_encode($this->db->select('skm'));
        }
    }

    public function employeeRate()
    {
        if (isset($_POST['pid'])) {
            $this->db->create('rating', [
                'rate_employee_id' => $_POST['pid'],
                'rate_value' => $_POST['rate']
            ]);
            echo json_encode([
                'status' => 'success',
                'message' => 'Terimakasih atas penilaian anda, ini akan menjadi bahan evaluasi kami kedepannya.'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Terjadi kesalahan input.'
            ]);
        }
    }
}
