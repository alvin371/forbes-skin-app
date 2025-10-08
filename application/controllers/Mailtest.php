<?php
class Mailtest extends CI_Controller {
    private function trySend($cfg) {
        $this->email->initialize($cfg);
        $this->email->set_newline("\r\n");
        $this->email->set_crlf("\r\n");
        $this->email->from('mou@bhskin.co.id', 'BHSKIN - MoU System');
        $this->email->to('viraanjayyy@gmail.com');
        $this->email->subject('Tes Email Hostinger');
        $this->email->message('<p>Halo, ini percobaan kirim email via Hostinger SMTP.</p>');
        return $this->email->send() ? true : $this->email->print_debugger(['headers','subject','body']);
    }

    public function index() {
        $this->load->library('email');

        $ssl465 = [
            'protocol'=>'smtp','smtp_host'=>'smtp.hostinger.com','smtp_user'=>'mou@bhskin.co.id','smtp_pass'=>'P~2c*8#tg5',
            'smtp_port'=>465,'smtp_crypto'=>'ssl','smtp_timeout'=>15,'mailtype'=>'html','charset'=>'utf-8',
            'newline'=>"\r\n",'crlf'=>"\r\n",'wordwrap'=>true,'validate'=>true,
        ];
        $tls587 = $ssl465; $tls587['smtp_port']=587; $tls587['smtp_crypto']='tls';

        $r1 = $this->trySend($ssl465);
        if ($r1 === true) { echo "✅ 465/SSL OK"; return; }

        $r2 = $this->trySend($tls587);
        if ($r2 === true) { echo "✅ 587/TLS OK (fallback)"; return; }

        echo "❌ Gagal 465 & 587.<br><b>Log 465:</b><br>$r1<br><br><b>Log 587:</b><br>$r2";
    }

}
