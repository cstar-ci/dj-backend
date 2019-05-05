<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Class : Login (LoginController)
 * Login class to control to authenticate user credentials and starts user's session.
 * @author : Kishor Mali
 * @version : 1.1
 * @since : 15 November 2016
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Contact extends CI_Controller
{
    /**
     * This is default constructor of the class
     */
    public function __construct()
    {
        parent::__construct();
//        $this->load->model('login_model');
        $this->load->library('email');
        $config = array();
        $config['protocol'] = 'smtp';
        $config['smtp_host'] = 'smtp.mail.yahoo.com';
        $config['smtp_user'] = 'djbitz1530@yahoo.com';
        $config['smtp_pass'] = 'gMpoweR098';
        $config['smtp_crypto'] = 'tls';
        $config['smtp_port'] = 587;
        $config['mailtype'] = 'text';
        $config['charset'] = 'utf-8';
        $config['wordwrap'] = TRUE;
        $this->email->initialize($config);
        $this->email->set_newline("\r\n");
    }

    /**
     * Index Page for this controller.
     */
    public function index()
    {
        $this->isLoggedIn();
    }
    
    /**
     * This function used to check the user is logged in or not
     */
    function isLoggedIn()
    {
        $isLoggedIn = $this->session->userdata('isLoggedIn');
        
        if(!isset($isLoggedIn) || $isLoggedIn != TRUE)
        {
            $this->load->view('login');
        }
        else
        {
            redirect('/index.php/dashboard');
        }
    }

    /**
     * This function used to generate reset password request link
     */
    function sendRequest()
    {
        $this->load->helper('string');
        $email = $this->input->post('sender_email');;
        $firstName = $this->input->post('first_name');
        $lastName = $this->input->post('last_name');
        $djPref = $this->input->post('dj_pref');
        $eventDate = $this->input->post('event_date');
        $eventName = $this->input->post('event_name');

        if ($djPref) {
            $this->load->model('djs_model');
            $dj = $this->djs_model->getDJsInfo($djPref);
        }

        if (count($dj)) {
            $dj = $dj[0];

            $this->email->from("djbitz1530@yahoo.com", $firstName . $lastName);
            $this->email->to("mrg8406@gmail.com");
            $this->email->subject("You have received the Event Request from $firstName $lastName");
            $this->email->message("$firstName $lastName requested $dj->name to the new event $eventName on $eventDate.");

            if($this->email->send())
                echo json_encode(array('status' => "success", 'msg' => "Congratulation Email Send Successfully."));
            else
                echo var_dump($this->email->print_debugger());
        } else {
            echo json_encode(array('status' => 'failed', 'msg' => "DJ is not available"));
        }

    }

    function sendEmail(){
        $this->load->helper('string');
        $email = $this->input->post('sender_email');;
        $firstName = $this->input->post('first_name');
        $lastName = $this->input->post('last_name');
        $djPref = $this->input->post('dj_pref');
        $eventDate = $this->input->post('event_date');
        $eventName = $this->input->post('event_name');

        if ($djPref) {
            $this->load->model('djs_model');
            $dj = $this->djs_model->getDJsInfo($djPref);
        }

        if (count($dj)) {
            $dj = $dj[0];

            $mail = new PHPMailer(true);                              // Passing `true` enables exceptions
            try {
                //Server settings
                $mail->SMTPDebug = 0;                                 // Enable verbose debug output
                $mail->isSMTP();                                      // Set mailer to use SMTP
                $mail->Host = 'smtp.mail.yahoo.com';  // Specify main and backup SMTP servers
                $mail->SMTPAuth = true;                               // Enable SMTP authentication
                $mail->Username = 'djbitz1530@yahoo.com';                 // SMTP username
                $mail->Password = 'gMpoweR098';                           // SMTP password
                $mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
                $mail->Port = 587;                                    // TCP port to connect to

                //Recipients
                $mail->setFrom('djbitz1530@yahoo.com', 'DJ App');
                $mail->addAddress('Gary.then87@gmail.com', 'DJ Music');     // Add a recipient
                //Content
                $mail->isHTML(true);                                  // Set email format to HTML
                $mail->Subject = "You have received the Event Request from $firstName $lastName";
                $mail->Body    = "$firstName $lastName requested $dj->name ( $email ) to the new event $eventName on $eventDate.";
                $mail->AltBody = "$firstName $lastName requested $dj->name to the new event $eventName on $eventDate.";

                $mail->send();
                echo json_encode(array('status' => "success", 'msg' => "Congratulation Email Send Successfully."));
            } catch (Exception $e) {
                echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
            }
        } else {
            echo json_encode(array('status' => 'failed', 'msg' => "DJ is not available"));
        }
    }

}

?>