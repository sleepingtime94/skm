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

        // Hapus data PII agar tidak bocor ke publik
        foreach ($result as &$row) {
            unset($row['employee_nip']);
            unset($row['employee_nik']);
            unset($row['employee_ttl']);
        }
        unset($row); // break reference

        echo json_encode($result);
    }

    public function find($employee_id)
    {
        $result = $this->db->select('employee', [
            'employee_id' => $employee_id,
            'employee_job' => ['IN', ['FO', 'FD', 'OPR']]
        ], 'employee_id DESC');

        if (count($result) > 0) {
            $row = $result[0];
            // Hapus data PII agar tidak bocor ke publik
            unset($row['employee_nip']);
            unset($row['employee_nik']);
            unset($row['employee_ttl']);
            return json_encode($row);
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
        $data = $_POST;
        $required = ['employee_name'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                echo json_encode(['status' => 'error', 'message' => "Field {$field} wajib diisi."]);
                return;
            }
        }

        $imageName = null;
        if (isset($_FILES['employee_image']) && $_FILES['employee_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = dirname(__DIR__, 2) . '/storage/uploads';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileTmpPath = $_FILES['employee_image']['tmp_name'];
            $originalName = $_FILES['employee_image']['name'];
            $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($fileExtension, $allowedExtensions)) {
                $newFileName = md5(time() . $originalName) . '.' . $fileExtension;
                $destPath = $uploadDir . '/' . $newFileName;
                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    $imageName = $newFileName;
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Format file tidak didukung.']);
                return;
            }
        }

        $params = [
            'employee_name'     => $data['employee_name'],
            'employee_ttl'      => !empty($data['employee_ttl']) ? $data['employee_ttl'] : null,
            'employee_position' => !empty($data['employee_position']) ? $data['employee_position'] : null,
            'employee_nip'      => !empty($data['employee_nip']) ? $data['employee_nip'] : null,
            'employee_nik'      => !empty($data['employee_nik']) ? $data['employee_nik'] : null,
            'employee_job'      => !empty($data['employee_job']) ? $data['employee_job'] : null,
            'employee_about'    => !empty($data['employee_about']) ? $data['employee_about'] : null,
            'employee_image'    => $imageName,
        ];

        $this->db->create('employee', $params);
        echo json_encode(['status' => 'success', 'message' => 'Pegawai berhasil ditambahkan.']);
    }

    public function apiUpdate($employee_id)
    {
        $data = $_POST;
        if (empty($data) && empty($_FILES)) {
            echo json_encode(['status' => 'error', 'message' => 'Tidak ada data untuk diperbarui.']);
            return;
        }

        $allowed = ['employee_name','employee_ttl','employee_position','employee_nip',
                     'employee_nik','employee_job','employee_about'];
        $params = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $params[$field] = !empty($data[$field]) ? $data[$field] : null;
            }
        }

        if (isset($_FILES['employee_image']) && $_FILES['employee_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = dirname(__DIR__, 2) . '/storage/uploads';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileTmpPath = $_FILES['employee_image']['tmp_name'];
            $originalName = $_FILES['employee_image']['name'];
            $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($fileExtension, $allowedExtensions)) {
                $newFileName = md5(time() . $originalName) . '.' . $fileExtension;
                $destPath = $uploadDir . '/' . $newFileName;
                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    $oldEmp = $this->db->select('employee', ['employee_id' => $employee_id]);
                    if (!empty($oldEmp) && !empty($oldEmp[0]['employee_image'])) {
                        $oldImagePath = $uploadDir . '/' . $oldEmp[0]['employee_image'];
                        if (file_exists($oldImagePath)) {
                            @unlink($oldImagePath);
                        }
                    }
                    $params['employee_image'] = $newFileName;
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Format file tidak didukung.']);
                return;
            }
        }

        if (empty($params)) {
            echo json_encode(['status' => 'error', 'message' => 'Tidak ada field yang valid.']);
            return;
        }

        $affected = $this->db->update('employee', $params, ['employee_id' => $employee_id]);
        if ($affected > 0 || isset($params['employee_image'])) {
            echo json_encode(['status' => 'success', 'message' => 'Data pegawai berhasil diperbarui.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan atau tidak berubah.']);
        }
    }

    public function apiDelete($employee_id)
    {
        $emp = $this->db->select('employee', ['employee_id' => $employee_id]);
        if (!empty($emp) && !empty($emp[0]['employee_image'])) {
            $uploadDir = dirname(__DIR__, 2) . '/storage/uploads';
            $imagePath = $uploadDir . '/' . $emp[0]['employee_image'];
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
        }

        $affected = $this->db->delete('employee', ['employee_id' => $employee_id]);
        if ($affected > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Data pegawai berhasil dihapus.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan.']);
        }
    }
}
