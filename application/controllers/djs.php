<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . '/libraries/BaseController.php';

/**
 * Class : djs (djsController)
 * djs Class to control all djs related operations.
 * @author : Kishor Mali
 * @version : 1.1
 * @since : 15 November 2016
 */
class Djs extends BaseController
{
    /**
     * This is default constructor of the class
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('djs_model');
        date_default_timezone_set('Africa/Lagos');
    }
    
    /**
     * This function used to load the first screen of the djs
     */
    public function index()
    {
        $this->isLoggedIn();
        $this->global['pageTitle'] = 'CodeInsect : Dashboard';
        
        $this->loadViews("dashboard", $this->global, NULL , NULL);
    }
    
    /**
     * This function is used to load the djs list
     */
    function djsListing()
    {
        $this->isLoggedIn();
        if($this->isAdmin() == TRUE)
        {
            $this->loadThis();
        }
        else
        {
            $searchText = $this->input->post('searchText');
            $data['searchText'] = $searchText;
            
            $this->load->library('pagination');
            
            $count = $this->djs_model->djsListingCount($searchText);

			$returns = $this->paginationCompress ( "djsListing/", $count, 5 );
            
            $data['djsRecords'] = $this->djs_model->djsListing($searchText, $returns["page"], $returns["segment"]);

            $this->global['pageTitle'] = 'CodeInsect : djs Listing';
            
            $this->loadViews("dj/list", $this->global, $data, NULL);
        }
    }

    /**
     * This function is used to add new djs to the system
     */
    function addNewDJs()
    {
        $this->isLoggedIn();
        if($this->isAdmin() == TRUE)
        {
            $this->loadThis();
        }
        else
        {
            $this->load->library('form_validation');
            
            $this->form_validation->set_rules('name','Name','trim|required|max_length[128]|xss_clean');
            $this->form_validation->set_rules('email','Email','trim|required|valid_email|xss_clean|max_length[128]');
            $this->form_validation->set_rules('mobile','Mobile Number','required|min_length[10]|xss_clean');
            
            if($this->form_validation->run() == FALSE)
            {
                $this->djsListing();
            }
            else
            {
                $name = ucwords(strtolower($this->input->post('name')));
                $email = $this->input->post('email');
                $mobile = $this->input->post('mobile');
                
                $djsInfo = array('name'=> $name, 'email'=>$email, 'mobile'=>$mobile, 'createdBy'=>$this->vendorId, 'updatedBy'=>$this->vendorId, 'created_date'=>date('Y-m-d H:i:s'), 'updated_date'=>date('Y-m-d H:i:s'));

                $uploaddir = 'assets/dj-avatars/';
                $path = $_FILES['avatar']['name'];

                if ($_FILES['avatar']['name']) {
                    $ext = pathinfo($path, 4);
                    $dest_filename = md5(uniqid(rand(), true)) . '.' . $ext;
                    $uploadfile = $uploaddir . $dest_filename;
                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadfile)) {
                        $djsInfo['avatar_url'] = $uploadfile;
                    }
                }

                $coversdir = 'assets/dj-covers/';
                $path = $_FILES['cover']['name'];

                if ($_FILES['cover']['name']) {
                    $ext = pathinfo($path, 4);
                    $cover_filename = md5(uniqid(rand(), true)) . '.' . $ext;
                    $coverfile = $coversdir . $cover_filename;
                    if (move_uploaded_file($_FILES['cover']['tmp_name'], $coverfile)) {
                        $djsInfo['profile_cover'] = $coverfile;
                    }
                }

                $result = $this->djs_model->addNewDJs($djsInfo);
                
                if($result > 0)
                {
                    $this->session->set_flashdata('success', 'New djs created successfully');
                }
                else
                {
                    $this->session->set_flashdata('error', 'djs creation failed');
                }
                
                redirect('index.php/djsListing');
            }
        }
    }

    /**
     * This function is used to edit the djs information
     */
    function editDJs()
    {
        $this->isLoggedIn();
        if($this->isAdmin() == TRUE)
        {
            $this->loadThis();
        }
        else
        {
            $this->load->library('form_validation');
            
            $djsId = $this->input->post('djId');
            
            $this->form_validation->set_rules('name','Name','trim|required|max_length[128]|xss_clean');
            $this->form_validation->set_rules('email','Email','trim|required|valid_email|xss_clean|max_length[32]');
            $this->form_validation->set_rules('mobile','Mobile Number','required|min_length[10]|xss_clean');
            
            if($this->form_validation->run() == FALSE)
            {
                redirect('index.php/djsListing');
            }
            else
            {
                $name = ucwords(strtolower($this->input->post('name')));
                $email = $this->input->post('email');
                $mobile = $this->input->post('mobile');

                $djsInfo = array('name'=>ucwords($name), 'email'=>$email, 'mobile'=>$mobile, 'updatedBy'=>$this->vendorId, 'updated_date'=>date('Y-m-d H:i:s'));

                $uploaddir = 'assets/dj-avatars/';
                $path = $_FILES['avatar']['name'];

                if ($_FILES['avatar']['name']) {
                    $ext = pathinfo($path, 4);
                    $dest_filename = md5(uniqid(rand(), true)) . '.' . $ext;
                    $uploadfile = $uploaddir . $dest_filename;
                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadfile)) {
                        $djsInfo['avatar_url'] = $uploadfile;
                    }
                }

                $coversdir = 'assets/dj-covers/';
                $path = $_FILES['cover']['name'];

                if ($_FILES['cover']['name']) {
                    $ext = pathinfo($path, 4);
                    $cover_filename = md5(uniqid(rand(), true)) . '.' . $ext;
                    $coverfile = $coversdir . $cover_filename;
                    if (move_uploaded_file($_FILES['cover']['tmp_name'], $coverfile)) {
                        $djsInfo['profile_cover'] = $coverfile;
                    }
                }

                $result = $this->djs_model->editDJs($djsInfo, $djsId);
                
                if($result == true)
                {
                    $this->session->set_flashdata('success', 'djs updated successfully');
                }
                else
                {
                    $this->session->set_flashdata('error', 'djs updation failed');
                }

                redirect('index.php/djsListing');
            }
        }
    }

    /**
     * This function is used to delete the djs using djsId
     * @return boolean $result : TRUE / FALSE
     */
    function deleteDJs()
    {
        $this->isLoggedIn();
        if($this->isAdmin() == TRUE)
        {
            $this->loadThis();
        }
        else
        {
            $djsId = $this->input->post('djId');
            $djsInfo = array('isDeleted'=>1,'updatedBy'=>$this->vendorId, 'updated_date'=>date('Y-m-d H:i:s'));
            
            $result = $this->djs_model->deleteDJs($djsId, $djsInfo);

            if($result == true)
            {
                $this->session->set_flashdata('success', 'djs deleted successfully');
            }
            else
            {
                $this->session->set_flashdata('error', 'djs delete failed');
            }

            redirect('index.php/djsListing');
        }
    }

    function pageNotFound()
    {
        $this->global['pageTitle'] = 'CodeInsect : 404 - Page Not Found';
        
        $this->loadViews("404", $this->global, NULL, NULL);
    }

    /*
     * Mobile API
     */
    public function getDJsList() {
        $searchText = $this->input->post('searchText');
        $data['searchText'] = $searchText;

        $this->load->library('pagination');

        $count = $this->djs_model->djsListingCount($searchText);

        // store the result in array form
        $result_set = array();
        if (count($count) > 0) {

            $data['djsRecords'] = $this->djs_model->djsListing($searchText);

            $status = "success";
            $msg = "Success!";
        } else {
            $status = "failed";
            $msg = "djs not existing.";
        }

        echo json_encode(array('status' => $status, 'msg' => $msg, 'result' => $data));
    }
}

?>