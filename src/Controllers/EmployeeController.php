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
            return json_encode($result[0]);
        } else {
            return json_encode(['message' => 'Pegawai tidak ditemukan']);
        }
    }

    // ── Admin API ───────────────────────────────────────────

    public function apiListAll()
    {
        $result = $this->db->select('employee', [], 'employee_name ASC');
        echo json_encode($result);
    }

    public function apiCreate()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $required = ['employee_name'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                echo json_encode(['status' => 'error', 'message' => "Field {$field} wajib diisi."]);
                return;
            }
        }

        $params = [
            'employee_name'     => $data['employee_name'],
            'employee_ttl'      => $data['employee_ttl'] ?? null,
            'employee_position' => $data['employee_position'] ?? null,
            'employee_nip'      => $data['employee_nip'] ?? null,
            'employee_nik'      => $data['employee_nik'] ?? null,
            'employee_job'      => $data['employee_job'] ?? null,
            'employee_about'    => $data['employee_about'] ?? null,
            'employee_image'    => $data['employee_image'] ?? null,
        ];

        $this->db->create('employee', $params);
        echo json_encode(['status' => 'success', 'message' => 'Pegawai berhasil ditambahkan.']);
    }

    public function apiUpdate($employee_id)
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data)) {
            echo json_encode(['status' => 'error', 'message' => 'Tidak ada data untuk diperbarui.']);
            return;
        }

        $allowed = ['employee_name','employee_ttl','employee_position','employee_nip',
                     'employee_nik','employee_job','employee_about','employee_image'];
        $params = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $params[$field] = $data[$field];
            }
        }

        if (empty($params)) {
            echo json_encode(['status' => 'error', 'message' => 'Tidak ada field yang valid.']);
            return;
        }

        $affected = $this->db->update('employee', $params, ['employee_id' => $employee_id]);
        if ($affected > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Data pegawai berhasil diperbarui.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan atau tidak berubah.']);
        }
    }

    public function apiDelete($employee_id)
    {
        $affected = $this->db->delete('employee', ['employee_id' => $employee_id]);
        if ($affected > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Data pegawai berhasil dihapus.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan.']);
        }
    }
}
