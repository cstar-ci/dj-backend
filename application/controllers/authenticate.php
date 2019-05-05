<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Class : Login (LoginController)
 * Login class to control to authenticate user credentials and starts user's session.
 * @author : Kishor Mali
 * @version : 1.1
 * @since : 15 November 2016
 */

class Authenticate extends CI_Controller
{
    /**
     * This is default constructor of the class
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('customer_model');
    }

    /**
     * Index Page for this controller.
     */
    public function index()
    {

    }

    function registerToken(){
        $token = $this->input->post('token');

        $this->load->model('devicetoken_model');

        if (!$this->devicetoken_model->is_registered($token)) {
            $this->devicetoken_model->addToken($token);
        }

//        echo json_encode(array('status' => "Success", 'msg' => "Token registered successfully"));
        echo json_encode(array('status' => "Success", 'msg' => $token));
    }

    /**
    * This function used to register new user
     */
    function register() {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('username','Full Name','trim|required|max_length[128]|xss_clean');
        $this->form_validation->set_rules('email','Email','trim|required|valid_email|xss_clean|max_length[128]');
        $this->form_validation->set_rules('password','Password','required|max_length[20]');

        if($this->form_validation->run() == FALSE)
        {
            echo json_encode(array('status' => "failed", 'msg' => "Validation failed."));
        }
        else {
            $name = ucwords(strtolower($this->input->post('username')));
            $email = $this->input->post('email');
            $password = $this->input->post('password');

            /* Check if the same email is already registered */
            $checkSameEmail = $this->customer_model->checkEmailExists($email);

            /* Register new user */
            if (!$checkSameEmail) {
                $userInfo = array('username' => $name, 'email' => $email, 'password' => getHashedPassword($password), 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'));

                $this->customer_model->register($userInfo);
                echo json_encode(array('status' => "Success", 'msg' => "User registered successfully."));
            } else {
                echo json_encode(array('status' => "failed", 'msg' => "Same email was already registered."));
            }
        }

        exit(1);
    }

    /**
     * This function used to logged in user
     */
    public function login()
    {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email|max_length[128]|xss_clean|trim');
        $this->form_validation->set_rules('password', 'Password', 'required|max_length[32]|');

        if($this->form_validation->run() == FALSE)
        {
            echo json_encode(array('status' => "failed", 'msg' => "Validation failed."));
        }
        else
        {
            $email = $this->input->post('email');
            $password = $this->input->post('password');

            $result = $this->customer_model->login($email, $password);

            if ($result) {
                echo json_encode(array('status' => "success", 'msg' => "Login Success", 'userInfo' => $result));
            } else {
                echo json_encode(array('status' => "failed", 'msg' => "Email or password mismatch"));
            }
        }

        exit(1);
    }

    /**
     * This function used to generate reset password request link
     */
    function resetPassword()
    {
        $this->load->library('form_validation');

        $this->form_validation->set_rules('registered_email','Email','trim|required|valid_email|xss_clean');
        $this->form_validation->set_rules('password','Password','required|max_length[20]');

        if($this->form_validation->run() == FALSE)
        {
            echo json_encode(array('status' => "failed", 'msg' => "Validation failed."));
        }
        else
        {
            $email = $this->input->post('registered_email');
            $password = $this->input->post('password');

            $user = $this->customer_model->checkEmailExists($email);

            if($user && count($user) > 0) {
                $save = $this->customer_model->changePassword($email, getHashedPassword($password));
                echo json_encode(array('status' => "success", 'msg' => "Password was updated successfully."));
            }
            else
            {
                echo json_encode(array('status' => "failed", 'msg' => "Email doesn't exist."));
            }
        }

        exit(1);
    }
}

?>