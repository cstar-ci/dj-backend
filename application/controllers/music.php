<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . '/libraries/BaseController.php';

use sngrl\PhpFirebaseCloudMessaging\Client;
use sngrl\PhpFirebaseCloudMessaging\Message;
use sngrl\PhpFirebaseCloudMessaging\Recipient\Device;
use sngrl\PhpFirebaseCloudMessaging\Recipient\Topic;
use sngrl\PhpFirebaseCloudMessaging\Notification;

/**
 * Class : music (musicController)
 * music Class to control all music related operations.
 * @author : Kishor Mali
 * @version : 1.1
 * @since : 15 November 2016
 */
class Music extends BaseController
{
    /**
     * This is default constructor of the class
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('music_model');
        $this->load->model('djs_model');
        $this->load->model('genres_model');
        $this->load->model('artists_model');

        $this->load->library('mp3file');

        date_default_timezone_set('Africa/Lagos');

        $server_key = 'AIzaSyD4Tekho88a9WWDOKiFNeE7xNQC1_ffebU';
        $this->client = new Client();
        $this->client->setApiKey($server_key);
    }
    
    /**
     * This function used to load the first screen of the music
     */
    public function index()
    {
        $this->isLoggedIn();
        $this->global['pageTitle'] = 'CodeInsect : Dashboard';
        
        $this->loadViews("dashboard", $this->global, NULL , NULL);
    }
    
    /**
     * This function is used to load the music list
     */
    public function musicListing()
    {
        $this->isLoggedIn();
        if($this->isAdmin() == TRUE)
        {
            $this->loadThis();
        }
        else
        {
            $this->global['pageTitle'] = 'CodeInsect : music Listing';
            $this->global['search'] = '';

            $searchText = $this->input->post('searchText');
            $this->global['search'] = $searchText;
            
            $this->load->library('pagination');
            
            $count = $this->music_model->musicListingCount($searchText);

			$returns = $this->paginationCompress ( "musicListing/", $count, 5);

            $this->global['musics'] = $this->music_model->musicListing($searchText, null, $returns["page"], $returns["segment"]);

            $this->loadViews("music/list", $this->global, null, NULL);
        }
    }

    /**
     * This function is used to add new music to the system
     */
    public function addNewMusic()
    {
        $this->isLoggedIn();
        $this->global['pageTitle'] = 'Add New Music';
        $data['djs'] = $this->djs_model->djsListing();
        $data['genres'] = $this->genres_model->genresListing();
        $data['artists'] = $this->artists_model->artistsListing();

        $this->loadViews("music/add", $this->global, $data , NULL);
    }

    public function saveNewMusic()
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
            $this->form_validation->set_rules('description','Description','trim|xss_clean');
            $this->form_validation->set_rules('dj','Dj','trim|xss_clean');
            $this->form_validation->set_rules('genre','Genre','trim|xss_clean');
            $this->form_validation->set_rules('artist','Artist','trim|xss_clean');

            if($this->form_validation->run() == FALSE)
            {
                $this->musicListing();
            }
            else
            {
                $name = ucwords(strtolower($this->input->post('name')));
                $description = $this->input->post('description');
                $dj = $this->input->post('dj');
                $genre = $this->input->post('genre');
                $artist = $this->input->post('artist');

                $musicInfo = array('name'=> $name, 'description'=>$description, 'dj'=>$dj, 'genre'=>$genre, 'artist'=>$artist, 'createdBy'=>$this->vendorId, 'updatedBy'=>$this->vendorId, 'created_date'=>date('Y-m-d H:i:s'), 'updated_date'=>date('Y-m-d H:i:s'));

                $uploaddir = 'assets/thumbimages/music/';
                $path = $_FILES['thumbimg']['name'];

                if ($_FILES['thumbimg']['name']) {
                    $ext = pathinfo($path, PATHINFO_EXTENSION);
                    $dest_filename = md5(uniqid(rand(), true)) . '.' . $ext;
                    $uploadfile = $uploaddir . $dest_filename;
                    if (move_uploaded_file($_FILES['thumbimg']['tmp_name'], $uploadfile)) {
                        $musicInfo['thumb'] = $uploadfile;
                    }
                }

                $musidDir = 'assets/music_files/';
                $path = $_FILES['music']['name'];

                if ($_FILES['music']['name']) {
                    $ext = pathinfo($path, PATHINFO_EXTENSION);
                    $dest_filename = md5(uniqid(rand(), true)) . '.' . $ext;
                    $musicfile = $musidDir . $dest_filename;
                    if (move_uploaded_file($_FILES['music']['tmp_name'], $musicfile)) {
                        $duration = $this->mp3file->getDurationEstimate($musicfile);
                        $musicInfo['music'] = $musicfile;
                        $musicInfo['duration'] = $duration;
                    }
                }

                $result = $this->music_model->addNewMusic($musicInfo);
                
                if($result > 0)
                {
                    $this->sendNotification($name);
                    $this->session->set_flashdata('success', 'New music created successfully');
                }
                else
                {
                    $this->session->set_flashdata('error', 'music creation failed');
                }

                redirect('index.php/musicListing');
            }
        }
    }

    public function sendNotification($name) {
        $notification = new Notification('Notification from DJ Bitz', "New Mix \"$name\" was added.");
        $notification->setBadge(1);
        $notification->setSound('default');

        $message = new Message();
        $message->setPriority('high');
        $message->setContentAvailable(true);
        $message->addRecipient(new Topic("new-mix"));
        $message
            ->setNotification($notification)
            ->setData(['content_available' => 'true'])
        ;

        $response = $this->client->send($message);
    }

    /**
     * This function is used to edit the music information
     */
    public function editMusic($music_id){
        $this->isLoggedIn();
        $music = $this->music_model->getMusicInfo($music_id);

        $data['djs'] = $this->djs_model->djsListing();
        $data['genres'] = $this->genres_model->genresListing();
        $data['artists'] = $this->artists_model->artistsListing();
        $data['music'] = $music[0];

        $this->global['pageTitle'] = 'Edit Music';
        $this->loadViews("music/edit", $this->global, $data , NULL);
    }

    function saveEditMusic()
    {
        $this->isLoggedIn();
        if($this->isAdmin() == TRUE)
        {
            $this->loadThis();
        }
        else
        {
            $this->load->library('form_validation');

            $musicId = $this->input->post('musicId');
            
            $this->form_validation->set_rules('name','Name','trim|required|max_length[128]|xss_clean');
            $this->form_validation->set_rules('description','Description','trim|xss_clean');
            $this->form_validation->set_rules('dj','Dj','trim|xss_clean');
            $this->form_validation->set_rules('genre','Genre','trim|xss_clean');
            $this->form_validation->set_rules('artist','Artist','trim|xss_clean');

            if($this->form_validation->run() == FALSE)
            {
                redirect('index.php/musicListing');
            }
            else
            {
                $name = ucwords(strtolower($this->input->post('name')));
                $description = $this->input->post('description');
                $dj = $this->input->post('dj');
                $genre = $this->input->post('genre');
                $artist = $this->input->post('artist');

                $musicInfo = array('name'=> $name, 'description'=>$description, 'dj'=>$dj, 'genre'=>$genre, 'artist'=>$artist, 'updatedBy'=>$this->vendorId, 'updated_date'=>date('Y-m-d H:i:s'));

                $uploaddir = 'assets/thumbimages/music/';
                $path = $_FILES['thumbimg']['name'];

                if ($_FILES['thumbimg']['name']) {
                    $ext = pathinfo($path, PATHINFO_EXTENSION);
                    $dest_filename = md5(uniqid(rand(), true)) . '.' . $ext;
                    $uploadfile = $uploaddir . $dest_filename;
                    if (move_uploaded_file($_FILES['thumbimg']['tmp_name'], $uploadfile)) {
                        $musicInfo['thumb'] = $uploadfile;
                    }
                }

                $musidDir = 'assets/music_files/';
                $path = $_FILES['music']['name'];

                if ($_FILES['music']['name']) {
                    $ext = pathinfo($path, PATHINFO_EXTENSION);
                    $dest_filename = md5(uniqid(rand(), true)) . '.' . $ext;
                    $musicfile = $musidDir . $dest_filename;
                    if (move_uploaded_file($_FILES['music']['tmp_name'], $musicfile)) {
                        $duration = $this->mp3file->getDurationEstimate($musicfile);
                        $musicInfo['music'] = $musicfile;
                        $musicInfo['duration'] = $duration;
                    }
                }

                $result = $this->music_model->editMusic($musicInfo, $musicId);

                if($result == true)
                {
                    $this->session->set_flashdata('success', 'music updated successfully');
                }
                else
                {
                    $this->session->set_flashdata('error', 'music updation failed');
                }

                redirect('index.php/musicListing');
            }
        }
    }

    /**
     * This function is used to delete the music using musicId
     * @return boolean $result : TRUE / FALSE
     */
    function deleteMusic()
    {
        $this->isLoggedIn();
        if($this->isAdmin() == TRUE)
        {
            $this->loadThis();
        }
        else
        {
            $musicId = $this->input->post('musicId');
            $musicInfo = array('isDeleted'=>1,'updatedBy'=>$this->vendorId, 'updated_date'=>date('Y-m-d H:i:s'));
            
            $result = $this->music_model->deleteMusic($musicId, $musicInfo);

            if($result == true)
            {
                $this->session->set_flashdata('success', 'music deleted successfully');
            }
            else
            {
                $this->session->set_flashdata('error', 'music delete failed');
            }

            redirect('index.php/musicListing');
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
    public function getMusicList() {
        $searchText = $this->input->post('searchText');
        $data['searchText'] = $searchText;

        $this->load->library('pagination');

        $count = $this->music_model->musicListingCount($searchText);

        // store the result in array form
        $result_set = array();
        if (count($count) > 0) {

            $data['musicRecords'] = $this->music_model->musicListing($searchText);

            if (is_array($data['musicRecords'])) {
                foreach ($data['musicRecords'] as $music) {
                    if ($music->music && !$music->duration) {
                        $duration = $this->mp3file->getDurationEstimate($music->music);
                        $musicData = array('duration' => $duration);
                        $this->music_model->editMusic($musicData, $music->id);
                    }
                }
            }

            $status = "success";
            $msg = "Success!";
        } else {
            $status = "failed";
            $msg = "music not existing.";
        }

        echo json_encode(array('status' => $status, 'msg' => $msg, 'result' => $data));
    }

    public function getTopMusicList() {

        $this->load->model("playlog_model");
        $topPlayLogs = $this->playlog_model->playlogListing();

        $topTenMusicIds = array();
        if ($topPlayLogs && is_array($topPlayLogs)) {
            foreach ($topPlayLogs as $log) {
                $topTenMusicIds[] = $log->music_id;
            }
        } else {
            echo json_encode(array('status' => "failed", 'msg' => "Can't find the play logs"));
            exit;
        }

        $topTenMusicLists = $this->music_model->musicListing('', $topTenMusicIds);
        if ($topTenMusicLists) {
            if (is_array($topTenMusicLists)) {
                $newList = array();
                foreach ($topTenMusicLists as $music) {
                    $newList[$music->id] = $music;
                }

                $topTenMusicLists = array_replace(array_flip($topTenMusicIds), $newList);

                $newItems = array();
                foreach ($topTenMusicLists as $item) {
                    $newItems[] = $item;
                }

                $topTenMusicLists = $newItems;
            }
            echo json_encode(array('status' => "Success", 'result' => $topTenMusicLists));
        } else {
            echo json_encode(array('status' => "faile", 'msg' => "Can't find the Musics"));
        }
    }

    public function getMusicsWithGenre($genreId) {
        $searchText = $this->input->post('searchText');
        $data['searchText'] = $searchText;

        $this->load->library('pagination');

        $count = $this->music_model->musicListingCount($searchText);

        // store the result in array form
        $result_set = array();
        if (count($count) > 0) {

            $data['musicRecords'] = $this->music_model->musicListingWithGenre($genreId, $searchText);

            $status = "success";
            $msg = "Success!";
        } else {
            $status = "failed";
            $msg = "music not existing.";
        }

        echo json_encode(array('status' => $status, 'msg' => $msg, 'result' => $data));
    }

    public function getMusicsWithDJ($djId) {
        $searchText = $this->input->post('searchText');
        $data['searchText'] = $searchText;

        $this->load->library('pagination');

        $count = $this->music_model->musicListingCount($searchText);

        // store the result in array form
        $result_set = array();
        if (count($count) > 0) {

            $data['musicRecords'] = $this->music_model->musicListingWithDJ($djId, $searchText);

            $status = "success";
            $msg = "Success!";
        } else {
            $status = "failed";
            $msg = "music not existing.";
        }

        echo json_encode(array('status' => $status, 'msg' => $msg, 'result' => $data));
    }

    public function getMusicPlay($mid) {
        $musicInfo = $this->music_model->getMusicInfo($mid);

        if ($musicInfo) {
            $this->load->model("playlog_model");
            $this->playlog_model->addNewPlaylog(array(
                'music_id' => $mid,
                'created_at' => date('Y-m-d H:i:s')
            ));

            echo json_encode(array('status' => "success", 'result' => $musicInfo, 'duration' => $duration));
        } else {
            echo json_encode(array('status' => "failed", 'msg' => "Music data is not valid."));
        }


    }
}

?>