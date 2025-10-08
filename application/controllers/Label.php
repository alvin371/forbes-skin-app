<?php

namespace App\Controllers;

use App\Models\BlastDailyModel;

require_once APPPATH . 'core/BaseController.php';

class Label extends BaseController
{
    public function index()
    {

        $data['title'] = 'Service - ' . $this->template->title();
        $db = \Config\Database::connect();
        $query = $db->query("SELECT * FROM label ORDER BY created_at DESC");
        $query = $query->getResultArray();
        $data['data'] = $query;


        $data['content'] = view("label/all", $data);
        return view("TemplateDashboard", $data);
    }

    public function edit()
    {
        $id = $_GET['id'];
        $db = \Config\Database::connect();
        $query = $db->query("SELECT * FROM label WHERE id = '$id'");
        $query = $query->getResultArray();
        $data['data'] = $query[0];

        return view("label/edit", $data);
    }

    public function update()
    {

        $user = $this->template->get_session('user');

        $id = $_POST['id'];
        $dt = $_POST['dt'];
        $dt['updated_at'] = DATE("Y-m-d H:i:s");
        $dt['updated_by'] = $user['id'];

        if ($_FILES['file']['name']) {
            $input = $this->validate([
                'file' => [
                    'uploaded[file]',
                    'mime_in[file,image/jpg,image/jpeg,image/png]',
                    'max_size[file,1024]',
                ]
            ]);

            if (!$input) {
                $msg = 'Pastikan tipe file .jpg, .jpeg atau .png!';
                echo $this->template->alert_danger($msg);
                die;
            } else {
                $img = $this->request->getFile('file');
                $dir = str_replace('public/', '', FCPATH . 'assets/img/label/');
                $img->move($dir);
                $data = [
                    'name' =>  $img->getName(),
                    'type'  => $img->getClientMimeType()
                ];
                $currentFileName = $dir . $data['name'];
                $newfile = $id . '.' . substr(strrchr($data['name'], "."), 1);
                $newFileName = $dir . $newfile;
                rename($currentFileName, $newFileName);
                $dt['img'] = $newfile;
            }
        }
        $db      = \Config\Database::connect();
        $model = $db->table('label');
        $model->where('id', $id);

        if ($model->update($dt)) {
            $msg = 'Update data was successful!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Update data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }

    public function create()
    {
        $data['data'] = array();

        return view("label/create", $data);
    }


    public function store()
    {

        $user = $this->template->get_session('user');

        $id = $_POST['id'];
        $dt = $_POST['dt'];
        $dt['created_at'] = DATE("Y-m-d H:i:s");
        $dt['created_by'] = $user['id'];

        if ($_FILES['file']['name']) {
            $input = $this->validate([
                'file' => [
                    'uploaded[file]',
                    'mime_in[file,image/jpg,image/jpeg,image/png]',
                    'max_size[file,1024]',
                ]
            ]);

            if (!$input) {
                $msg = 'Pastikan tipe file .jpg, .jpeg atau .png!';
                echo $this->template->alert_danger($msg);
                die;
            } else {
                $img = $this->request->getFile('file');
                $dir = str_replace('public/', '', FCPATH . 'assets/img/label/');
                $img->move($dir);
                $data = [
                    'name' =>  $img->getName(),
                    'type'  => $img->getClientMimeType()
                ];
                $currentFileName = $dir . $data['name'];
                $newfile = DATE('Ymdhis') . '.' . substr(strrchr($data['name'], "."), 1);
                $newFileName = $dir . $newfile;
                rename($currentFileName, $newFileName);
                $dt['img'] = $newfile;
            }
        }

        $db      = \Config\Database::connect();
        $model = $db->table('label');

        if ($model->insert($dt)) {
            $msg = 'Tambah data was successful!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Tambah data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }

    public function remove()
    {
        $id = $_GET['id'];
        $data['data']['id'] = $id;
        return view("label/delete", $data);
    }

    public function delete()
    {

        $label = $this->template->get_session('label');

        $id = $_POST['id'];

        $db      = \Config\Database::connect();
        $model = $db->table('label');

        if ($model->delete(['id' => $id])) {
            $msg = 'Hapus data was successful!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Hapus data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }
}
