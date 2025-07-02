<?php

namespace App\Controllers;

use App\Utility\MySQL;

class EmployeeController
{
    protected $db;

    public function __construct()
    {
        $this->db = new MySQL();
    }

    public function store()
    {
        $result = $this->db->select('employee', [
            'employee_job' => ['IN', ['FO', 'FD', 'OPR']]
        ], 'employee_name ASC');
        echo json_encode($result);
    }

    public function find($employee_id)
    {
        $result = $this->db->select('employee', [
            'employee_id' => $employee_id,
            'employee_job' => ['IN', ['FO', 'FD', 'OPR']]
        ], 'employee_id DESC');

        if (count($result) > 0) {
            echo json_encode($result[0]);
        } else {
            echo json_encode(['message' => 'Pegawai tidak ditemukan']);
        }
    }
}
