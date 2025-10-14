<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Load custom env helper BEFORE Composer autoloader to prevent illuminate/support conflicts
require_once __DIR__ . '/../../application/helpers/env_helper.php';

require 'vendor/autoload.php';

require_once APPPATH . 'core/BaseController.php';

class Googlemeet extends BaseController {

    private $client;

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->model('mymodel');
        $this->load->library('template');
        $this->load->library('session');

        // Load environment helper
        $this->load->helper('env');

        // Set public methods (no permission required)
        $this->set_public_methods(['oauth2callback']);

        // Override method-to-action mapping if needed
        $this->set_method_permissions([
            'generate_link' => 'create'
        ]);

        $this->client = new Google_Client();

        // Use environment variables for Google OAuth config
        $this->client->setClientId(env('GOOGLE_CLIENT_ID'));
        $this->client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));

        $this->client->setScopes([
            Google_Service_Drive::DRIVE,
            Google_Service_Docs::DOCUMENTS,
            Google_Service_Oauth2::USERINFO_EMAIL,
            Google_Service_Calendar::CALENDAR
        ]);
        $this->client->setAccessType('offline');
        $this->client->setRedirectUri(base_url() . 'googlemeet/oauth2callback');
    }

    public function index() {
        if (!$this->session->userdata('access_token')) {
            $authUrl = $this->client->createAuthUrl();
            redirect($authUrl);
        }

        $this->client->setAccessToken($this->session->userdata('access_token'));

        if ($this->client->isAccessTokenExpired()) {
            $this->session->unset_userdata('access_token');
            redirect('googlemeet');
        }

        $calendar = new Google_Service_Calendar($this->client);

        $event = new Google_Service_Calendar_Event([
            'summary' => 'Interview Otomatis',
            'start' => ['dateTime' => '2025-08-02T10:00:00+07:00'],
            'end' => ['dateTime' => '2025-08-02T11:00:00+07:00'],
            'conferenceData' => [
                'createRequest' => [
                    'requestId' => uniqid(),
                    'conferenceSolutionKey' => ['type' => 'hangoutsMeet']
                ]
            ]
        ]);

        $optParams = ['conferenceDataVersion' => 1];

        $createdEvent = $calendar->events->insert('primary', $event, $optParams);
        $meetLink = $createdEvent->getHangoutLink();

        $this->db->insert('interview', ['interview_link' => $meetLink]);

        echo "Link GMeet berhasil dibuat dan disimpan:<br><a href='$meetLink' target='_blank'>$meetLink</a>";
    }

    public function generate_link() {
        if (!$this->session->userdata('access_token')) {
            echo json_encode([
                'status' => 'redirect',
                'message' => 'Anda belum login Google. Mengalihkan...',
                'redirect' => base_url() . 'googlemeet'
            ]);
            return;
        }


        $this->client->setAccessToken($this->session->userdata('access_token'));

        if ($this->client->isAccessTokenExpired()) {
            $this->session->unset_userdata('access_token');
            echo json_encode([
                'status' => 'redirect',
                'message' => 'Token kadaluarsa. Mengalihkan ke halaman login Google...',
                'redirect' => base_url() . 'googlemeet'
            ]);
            return;
        }


        $id = $this->input->post('id');
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'ID interview tidak valid.']);
            return;
        }

        $interview = $this->db->get_where('interview', ['id' => $id])->row_array();
        if (!$interview) {
            echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan.']);
            return;
        }

        $startTime = date('c', strtotime($interview['interview_datetime'] ?? '+1 hour'));
        $endTime = date('c', strtotime('+1 hour', strtotime($startTime)));

        $calendar = new Google_Service_Calendar($this->client);

        $event = new Google_Service_Calendar_Event([
            'summary' => 'Interview - BHSKIN',
            'start' => ['dateTime' => $startTime, 'timeZone' => 'Asia/Jakarta'],
            'end' => ['dateTime' => $endTime, 'timeZone' => 'Asia/Jakarta'],
            'conferenceData' => [
                'createRequest' => [
                    'requestId' => uniqid(),
                    'conferenceSolutionKey' => ['type' => 'hangoutsMeet']
                ]
            ]
        ]);

        try {
            $optParams = ['conferenceDataVersion' => 1];
            $createdEvent = $calendar->events->insert('primary', $event, $optParams);
            $meetLink = $createdEvent->getHangoutLink();

            $this->db->update('interview', ['interview_link' => $meetLink], ['id' => $id]);

            echo json_encode(['status' => 'success', 'link' => $meetLink]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }


    public function oauth2callback() {
        if ($this->input->get('code')) {
            $token = $this->client->fetchAccessTokenWithAuthCode($this->input->get('code'));
            $this->session->set_userdata('access_token', $token);
            redirect('interview');
        } else {
            echo "Gagal login.";
        }
    }
}
