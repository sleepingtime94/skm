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

    public function viewRateSKM($month = null, $year = null)
    {
        $pattern = ($month === null || $year === null)
            ? date('Y-m') . '%'
            : sprintf('%04d-%02d', $year, $month) . '%';

        return $this->db->select('survey_skm', [
            'created_at' => ['LIKE', $pattern]
        ], 'created_at DESC');
    }

    public function viewRateEmployee()
    {
        return $this->db->select('rating', '', 'rate_created DESC');
    }

    public function viewRateEmployeeWithNames()
    {
        $result = $this->db->query(
            "SELECT r.*, e.employee_name, e.employee_job
             FROM rating r
             LEFT JOIN employee e ON r.rate_employee_id = e.employee_id
             ORDER BY r.rate_created DESC"
        );
        return $result->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function ratingStats()
    {
        $result = $this->db->query(
            "SELECT e.employee_id, e.employee_name, e.employee_job,
                    COUNT(r.rate_id) AS total_ratings,
                    SUM(CASE WHEN r.rate_value = 5 THEN 1 ELSE 0 END) AS bagus,
                    SUM(CASE WHEN r.rate_value = 3 THEN 1 ELSE 0 END) AS lumayan,
                    SUM(CASE WHEN r.rate_value = 1 THEN 1 ELSE 0 END) AS buruk,
                    IFNULL(AVG(r.rate_value), 0) AS avg_value
             FROM employee e
             LEFT JOIN rating r ON e.employee_id = r.rate_employee_id
             WHERE e.employee_job IN ('FO','FD','OPR')
             GROUP BY e.employee_id, e.employee_name, e.employee_job
             ORDER BY avg_value DESC"
        );
        return $result->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function updateRatingStatus()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['rate_id']) || !isset($data['rate_status'])) {
            echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap.']);
            return;
        }
        $allowed = ['pending', 'approved', 'rejected'];
        if (!in_array($data['rate_status'], $allowed)) {
            echo json_encode(['status' => 'error', 'message' => 'Status tidak valid.']);
            return;
        }
        $affected = $this->db->update('rating', ['rate_status' => $data['rate_status']], ['rate_id' => $data['rate_id']]);
        if ($affected > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Status berhasil diperbarui.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan.']);
        }
    }

    public function deleteRating()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['rate_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'rate_id diperlukan.']);
            return;
        }
        $affected = $this->db->delete('rating', ['rate_id' => $data['rate_id']]);
        if ($affected > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Data rating berhasil dihapus.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan.']);
        }
    }

    public function apiRatings()
    {
        $rows = $this->viewRateEmployeeWithNames();
        echo json_encode($rows);
    }

    public function apiStats()
    {
        $rows = $this->ratingStats();
        echo json_encode($rows);
    }

    public function viewRateZI($month = null, $year = null)
    {
        $pattern = ($month === null || $year === null)
            ? date('Y-m') . '%'
            : sprintf('%04d-%02d', $year, $month) . '%';

        return $this->db->select('survey_zi', [
            'created_at' => ['LIKE', $pattern]
        ], 'created_at DESC');
    }

    public function apiSKM()
    {
        $month = $_GET['month'] ?? date('m');
        $year  = $_GET['year']  ?? date('Y');
        echo json_encode($this->viewRateSKM($month, $year));
    }

    public function apiZI()
    {
        $month = $_GET['month'] ?? date('m');
        $year  = $_GET['year']  ?? date('Y');
        echo json_encode($this->viewRateZI($month, $year));
    }
}
