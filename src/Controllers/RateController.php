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

    public function submitQuest($category)
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!in_array($category, ['skm', 'zi'])) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Kategori tidak valid.'
            ]);
            return;
        }

        switch ($category) {
            case 'skm':
                $tableName = 'survey_skm';
                break;
            case 'zi':
                $tableName = 'survey_zi';
                break;
        }

        if (isset($data['type']) && $data['type'] == 'create') {
            if (isset($data['params'])) {
                $this->db->create($tableName, $data['params']);
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
            echo json_encode($this->db->select($tableName));
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

    public function viewRateSKM()
    {
        return $this->db->select('survey_skm', '', 'created_at DESC');
    }

    public function viewRateEmployee()
    {
        return $this->db->select('rating', '', 'rate_created DESC');
    }

    public function viewRateZI()
    {
        return $this->db->select('survey_zi', '', 'created DESC');
    }
}
