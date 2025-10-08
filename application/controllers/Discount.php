<?php

namespace App\Controllers;

use App\Models\BlastDailyModel;

require_once APPPATH . 'core/BaseController.php';

class Discount extends BaseController
{
    public function index()
    {

        $data['title'] = 'Discount - ' . $this->template->title();
        $db = \Config\Database::connect();
        $query = $db->query("SELECT * FROM discount ORDER BY created_at DESC");
        $query = $query->getResultArray();
        $data['data'] = $query;


        $data['content'] = view("discount/all", $data);
        return view("TemplateDashboard", $data);
    }

    public function edit()
    {
        $id = $_GET['id'];
        $db = \Config\Database::connect();
        $query = $db->query("SELECT * FROM discount WHERE id = '$id'");
        $query = $query->getResultArray();
        $data['data'] = $query[0];
        return view("discount/edit", $data);
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
                $dir = str_replace('public/', '', FCPATH . 'assets/img/discount/');
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
        $model = $db->table('discount');
        $model->where('id', $id);

        if ($model->update($dt)) {
            $msg = 'Update data was successful!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Update data failed!';
            echo $this->template->alert_danger($msg);
        }
    }

    public function create()
    {
        $data['data'] = array();

        return view("discount/create", $data);
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
                $dir = str_replace('public/', '', FCPATH . 'assets/img/discount/');
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
        $model = $db->table('discount');

        if ($model->insert($dt)) {
            $msg = 'Create data was successful!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Create data failed!';
            echo $this->template->alert_danger($msg);
        }
    }

    public function remove()
    {
        $id = $_GET['id'];
        $data['data']['id'] = $id;
        return view("discount/delete", $data);
    }

    public function delete()
    {

        $discount = $this->template->get_session('discount');

        $id = $_POST['id'];

        $db      = \Config\Database::connect();
        $model = $db->table('discount');

        if ($model->delete(['id' => $id])) {
            $msg = 'Delete data was successful!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Delete data failed!';
            echo $this->template->alert_danger($msg);
        }
    }
}
