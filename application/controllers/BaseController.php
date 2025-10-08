<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

use App\Libraries\Template;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var array
     */
    protected $helpers = ['form', 'url', 'database', 'file', 'session'];

    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */
    // protected $session;

    /**
     * Constructor.
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        $this->db = \Config\Database::connect();
        $this->template = new Template();
        $this->spreadsheet = new Spreadsheet();

        // Preload any models, libraries, etc, here.

        // E.g.: $this->session = \Config\Services::session();

        // $current_url = current_url(true);
        // $current_url = str_replace('/index.php','',$current_url);

        // if (strpos($current_url, 'auth') === false && strpos($current_url, 'api') === false && strpos($current_url, 'ajax') === false && strpos($current_url, 'item') === false) {
        //     $session = \Config\Services::session();
        //     $newdata = [
        //         'current_url'  => $current_url,
        //     ];
        //     $session->set($newdata);
        // } else {

        // }
    }
    function initDB()
    {
        $this->db = \Config\Database::connect();
    }

    function setFile($filepath)
    {
        $this->file = new File($filepath);
    }

    function selectWithQuery($q)
    {
        $query = $this->db->query($q);
        return $query = $query->getResultArray();
    }

    function deleteData($q)
    {
        $query = $this->db->query($q);
        return $query = $query->getResultArray();
    }
}
