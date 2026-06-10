<?php

namespace App\Controllers;

use App\Utility\MySQL;
use App\Utility\Security;

class RateController
{
    protected $db;

    public function __construct()
    {
        $this->db = new MySQL();
    }

    public function submitQuest($category)
    {
        // Rate limiting: maks 5 submission per 5 menit
        Security::rateLimit('survey_' . $category, 5, 300);

        $data = json_decode(file_get_contents('php://input'), true);

        if (!in_array($category, ['skm', 'zi'])) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Kategori tidak valid.'
            ]);
            return;
        }

        // Whitelist field yang diizinkan per kategori
        $allowedFields = [
            'skm' => ['nama','kontak','umur','pendidikan','pekerjaan','layanan',
                      'q1','q2','q3','q4','q5','q6','q7','q8','q9','saran'],
            'zi'  => ['nama','kontak','umur','pendidikan','pekerjaan','layanan','saran',
                      'q1','q2','q3','q4','q5','q6','q7','q8','q9','q10','q11','q12','q13'],
        ];

        switch ($category) {
            case 'skm':
                $tableName = 'survey_skm';
                break;
            case 'zi':
                $tableName = 'survey_zi';
                break;
        }

        if (isset($data['type']) && $data['type'] == 'create') {
            if (isset($data['params']) && is_array($data['params'])) {
                // Terapkan whitelist — buang field yang tidak diizinkan
                $safeParams = array_intersect_key(
                    $data['params'],
                    array_flip($allowedFields[$category])
                );

                if (empty($safeParams)) {
                    echo json_encode(['status' => 'error', 'message' => 'Parameter tidak valid.']);
                    return;
                }

                $this->db->create($tableName, $safeParams);
                echo json_encode([
                    'status'  => 'success',
                    'message' => 'Terimakasih atas penilaian anda, ini akan menjadi bahan evaluasi kami kedepannya.'
                ]);
            } else {
                echo json_encode([
                    'status'  => 'error',
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
        // Rate limiting: maks 10 penilaian per 10 menit
        Security::rateLimit('employee_rate', 10, 600);

        $pid  = filter_var($_POST['pid']  ?? null, FILTER_VALIDATE_INT);
        $rate = (int) ($_POST['rate'] ?? 0);

        if (!$pid || $pid <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID pegawai tidak valid.']);
            return;
        }

        if (!in_array($rate, [1, 3, 5])) {
            echo json_encode(['status' => 'error', 'message' => 'Nilai penilaian tidak valid. Gunakan 1, 3, atau 5.']);
            return;
        }

        $this->db->create('rating', [
            'rate_employee_id' => $pid,
            'rate_value'       => $rate,
        ]);
        echo json_encode([
            'status'  => 'success',
            'message' => 'Terimakasih atas penilaian anda, ini akan menjadi bahan evaluasi kami kedepannya.'
        ]);
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
