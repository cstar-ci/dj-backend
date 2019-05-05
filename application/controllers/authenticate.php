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

}

?>