<?php
/**
* Plugin Name: Divi Media Recrawler
* Plugin URI: https://caveim.com/
* Description: Recrawl external url files
* Version: 1.0
* Author: Cave Interactive Media
* Author URI: https://caveim.com/
* License: GPLv2 or later
**/

define('ABSPATH',ABSPATH);

class CAVEIMDEV_DIVI_MEDIA_RECRAWLER {

	public function __construct()
	{
        add_action( 'admin_menu', array( &$this, 'dmc_menu') );
        add_action( 'admin_enqueue_scripts', array( &$this, 'dmc_enqueue') );
        add_action( 'wp_ajax_dmc_action', array( &$this, 'dmc_action') );
        add_action( 'wp_ajax_dmc_bulk_upload_media', array( &$this, 'dmc_bulk_upload_media') );
        add_action( 'wp_ajax_dmc_single_upload_media', array( &$this, 'dmc_single_upload_media') );
    }
    
    public function dmc_enqueue()
    {
        wp_enqueue_script( 'ajax-script', plugins_url( '/assets/js/media-crawler.js', __FILE__ ), array('jquery') );
        wp_localize_script(
            'ajax-script',
            'ajax_object',
            array(
                'ajax_url'  => admin_url( 'admin-ajax.php' )
            )
        );
    }

	public function dmc_menu()
	{
		add_menu_page(
			__( 'Divi Media Crawler', 'divi-media-recrawler' ),
			__( 'Divi Media Crawler', 'divi-media-recrawler' ),
			'manage_options',
			'divi-media-recrawler',
			array(&$this,'dmc_page'),
			'dashicons-schedule',
			3
		);
    }

    /**
     * Plugin Layout
     * Usage: the main layout page of the plugin
     **/    
	public function dmc_page()
	{
		?>

        <style>
            * {
                font-family:'Poppins', sans-serif;
                font-weight:normal;
                font-style:normal;
            }
            #wpwrap {
                background:#F1F1F1;
            }

            .dmc-header {
                margin-top:25px;
                margin-bottom:15px;
            }
            
            .result-table {
                width:100%;
                height:300px;
            }
            .link-fa-white,
            .link-fa-white:before {
                color:#fff!important;
            }
            .footer-label {
                font-size:14px;
                padding:7px 0;
                display:inline-block;
                vertical-align:middle;
                letter-spacing:3px;
                font-weight:600;
                text-transform: uppercase;
            }
            table.table-single,
            table.table-bulk,
            table.table-bulk-result {
                table-layout: fixed;
                word-wrap: break-word;
            }
            table.table-bulk thead tr th:nth-child(1),
            table.table-single thead tr th:nth-child(1),
            table.table-single thead tr th:nth-child(3) {
                width:10%;
            }
            table.table-bulk thead tr th:nth-child(2) {
                width:90%;
            }
            table.table-bulk thead tr th:nth-child(3) {
                width:10%;
            }
            table.table.table-dark.table-single thead tr th:nth-child(2) {
                width:70%;
            }
            table.table.table-dark.table-single thead tr th:nth-child(3) {
                width:10%;
            }
            table.table-bulk-result thead tr th:nth-child(1),
            table.table-bulk-result thead tr th:nth-child(4)
            {
                width:10%;
            }
            table.table-bulk-result thead tr th:nth-child(2),
            table.table-bulk-result thead tr th:nth-child(3) {
                width:40%;
            }
            .dmc-result {
                font-size: 38px;
                text-align:center;
            }
        </style>
		
		<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">

		<link href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700&display=swap" rel="stylesheet">

		<div class="container-fluid">

			<div class="row dmc-header">
				<div class="col-lg-12 col-md-12">
					<h3>Welcome to DMC</h3>
					<hr>
				</div>
			</div>

            <div class="row">
				<div class="col-lg-6 col-md-6 col-sm-12">
                    <form action="" method="POST">
                        
                        <div class="form-group">
                            <label for="urls" style="vertical-align: top;"><i class="fas fa-network-wired"></i> External URL</label>
                            <br/>
                            <input type="text" name="external-url" class="form-control" id="external-url" value="northok.publishpath.com">
                            <br/>
                            <button type="button" class="btn btn-success float-right link-fa-white" name="search-external" id="search-external"><i class="fas fa-folder-tree"></i> Search</button>
                        </div>

                    </form>
                </div>
				<div class="col-lg-6 col-md-6">
                    <div class="form-group">
                        <div id="searchResult"></div>
                    </div>
                </div>
            </div>

		</div>

		<script src="https://kit.fontawesome.com/f4bc91b179.js" crossorigin="anonymous"></script>

		<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>

    <?php
    }

    /**
     * Get the urls fetched captured from the domain name inputted
     * Usage: function to show the first step of showing the urls fetched
     * 
     * Called functions:
     * dmc_get_url_only (get)
     * dmc_get_url (post)
     * 
     * == Post ==
     * Data : array('pid' => $result->ID, 'url' => $external_url, 'content' => $result->post_content, 'image' => $img_g['image']);
     * Function : dmc_get_url
     * Format : json
     * 
     * == Get ==
     * Data : array($external_url, $result->post_content, 'solo') :: solo/bulk
     * Function : dmc_get_url_only
     * Format : string
     * 
     * @return view
     **/
    public function dmc_action()
    {

        global $wpdb;
        
        $external_url = $_POST['external_url'];

        $query = $wpdb->prepare("SELECT * FROM $wpdb->posts WHERE `post_status` = 'publish' AND `post_content` LIKE %s", '%'.$wpdb->esc_like($external_url).'%');
        
        $results = $wpdb->get_results($query);

        foreach($results as $result) {

            $img_url = $this->dmc_get_url_only($external_url, $result->post_content,'solo');

            $data[] = array('pid' => $result->ID, 'url' => $external_url, 'content' => $result->post_content, 'image' => $img_url);

            // Grouped
            $img_group = $this->dmc_get_url_only($external_url, $result->post_content,'bulk');

            foreach($img_group as $img_g):
                $data_group[] = array('pid' => $result->ID, 'url' => $external_url, 'content' => $result->post_content, 'image' => $img_g['image']);
            endforeach;

        }
        
        ?>

        <!-- Bulk Process -->
        <div class="">
            <h4>Bulk</h4>
            <small>Chances of server timeout</small>
            <table class="table table-dark table-bulk">
                <thead>
                    <tr>
                        <th>POST ID</th>
                        <th>External URL</th>
                        <th>Type</th>
                    </tr>
                </thead>
                <?php // foreach($data as $files): ?>
                <?php foreach($data as $files): ?>
                    <?php echo $this->dmc_get_url($files['pid'], $files['url'], $files['content'], 'bulk'); ?>
                <?php endforeach; ?>
                <?php $data_group = htmlspecialchars(json_encode($data_group)); ?>
                <tr>
                    <td colspan="3">
                        <span class="footer-label">Bulk Reupload</span>
                        <input type="text" name="bulk-data" id="bulk-data" class="form-control" data-value='<?php print_r($data_group);?>' style="display:none;">
                        <?php if($data_group !== NULL): ?>
                            <button class="btn btn-warning float-right bulk-reupload" id="bulk-reupload" name="bulk-reupload"><i class="fas fa-retweet-alt link-fa-white"></i></button>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            <div id="bulkProcessResult"></div>
        </div>

        <!-- Single Process -->
        <h4>Single File Reupload</h4>
        <table class="table table-dark table-single">
            <thead>
                <tr>
                    <th>POST ID</th>
                    <th>External URL</th>
                    <th>Type</th>
                    <th>Action</th>
                </tr>
            </thead>

            <?php foreach($data as $files): ?>
                <?php echo $this->dmc_get_url($files['pid'], $files['url'], $files['content'], 'single'); ?>
            <?php endforeach; ?>
            
        </table>
        <div id="singleProcessResult"></div>

        <?php
        wp_die();
    }

    /**
     * Get only the url of the source code
     * Usage: get only the url from the data processed
     * 
     * @param string $url - external url 
     * @param string $data - post content
     * @param string $type - solo/bulk
     * 
     * data : array('image'=>$file_url)
     * 
     * @return array
     **/
    public function dmc_get_url_only($url, $data, $type)
    {   
        // $re     = '/<img[^>]+src="(http|https):\/\/'.$url.'([^">]+)"/m';
        $re = '/(href|src)\s*=\s*"((http|https):\/\/'.$url.'\/[^\s]+\.(jpg|jpeg|png|gif|bmp|webp|pdf|doc|docx|csv|xlsx|txt|pptx|ppt))/m';
        $data   = preg_match_all($re, $data, $matches, PREG_SET_ORDER, 0);

        foreach($matches as $file_match):

            $file_url = $file_match[2];    
            $file_end = end(explode('/', $file_url));

            if($type == 'solo'):
                return($file_url);
            endif;

            if($type == 'bulk'):
                $file_array[] = array('image' => $file_url);
            endif;

        endforeach;

        if($type == 'bulk'):
            return($file_array);
        endif;
    }

    /**
     * Bulk Media Output
     * Usage: Function returns <table> format to show interpreted results
     * Output: dmc_action
     * 
     * @param int $postID - Post ID
     * @param string $url - URL requested 
     * @param string $data - json data [pid:$postID, url:$url, content:$content, image:$image]
     * @param string $type - string bulk/solo
     * 
     * @return string - in a table format
     **/
    public function dmc_get_url($postID, $url, $data, $type)
    {   
        $re = '/(href|src)\s*=\s*"((http|https):\/\/'.$url.'\/[^\s]+\.(jpg|jpeg|png|gif|bmp|webp|pdf|doc|docx|csv|xlsx|txt|pptx|ppt))/m';
        // $re     = '/<img[^>]+src="(http|https):\/\/'.$url.'([^">]+)"/m';
        $content = $data;
        $data   = preg_match_all($re, $data, $matches, PREG_SET_ORDER, 0);
        
    ?>
    <?php
        if($type == 'bulk'):
            foreach($matches as $file_match):

                $file_url = $file_match[2]; // File URL
                $file_ext = $file_match[4]; // File Extension
    ?>
    <tr>
        <td>
            <?php echo $postID; ?>
        </td>
        <td>
            <?php echo $file_match[2]; ?>
        </td>
        <td>
            <?php echo $file_ext; ?>
        </td>
    </tr>
    <?php
            endforeach;
        elseif($type == 'single'):
            foreach($matches as $file_match):
                $file_url = $file_match[2];
                $file_ext = $file_match[4]; // File Extension
                $file_end = end(explode('/', $file_url));

                $data = array('pid' => $postID, 'url' => $url, 'content' => $content, 'image' => $file_match[2]);
    ?>
    <tr>
        <td>
            <?php echo $postID; ?>
        </td>
        <td>
            <?php echo $file_match[2]; ?>
        </td>
        <td>
            <?php echo $file_ext; ?>
        </td>
        <td>
            <!-- Action for: dmc_single_upload_media -->
            <a class="btn btn-warning float-right" id="single-reupload" data-value='<?php print_r(htmlspecialchars(json_encode($data))); ?>' ><i class="fas fa-retweet-alt link-fa-white"></i></a>
        </td>
    </tr>
    <?php
            endforeach;
        endif;
    ?>
    <?php
    }

    /**
     * Bulk Media Output
     * Usage: Function returns <table> format to show interpreted results
     * Receiver: dmc_replace_old_image
     * 
     * @return string - in a table format
     * @return view
     **/
    public function dmc_bulk_upload_media()
    {
        global $wpdb;
        
        $url    = $_POST['external_url'];
        $data_array   = $_POST['bulk_data'];

        // print_r('<textarea>'.json_encode($data_array).'</textarea>');

        // Part where the reuploading of media happens
        $media_array = $this->dmc_reupload_media($data_array,'bulk');
        
        $replace_process = $this->dmc_replace_old_image($media_array,'bulk');

        // print_r('<textarea>'.json_encode($replace_process).'</textarea>');

        ?>

        <table class="table table-dark table-bulk-result">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>External URL</th>
                    <th>Internal URL</th>
                    <th>Status</th>
                </tr>
            </thead>

        <?php foreach($replace_process as $process): ?>
            <tr>
                <td>
                    <?php print_r($process['id']); ?>
                </td>
                <td>
                    <?php print_r($process['old_image']); ?>
                </td>
                <td>
                    <?php print_r($process['new_image']); ?>
                </td>
                <td>
                    <?php print_r( $process['status'] == 1 ? '<i class="far fa-check dmc-result"></i>' : '<i class="far fa-times dmc-result"></i>' ); ?>
                </td>
            </tr>
        <?php endforeach; ?>

        </table>

        <?php
    }

    /**
     * Single Media Output
     * Usage: Function returns <table> format to show interpreted results
     * Receiver: dmc_replace_old_image
     * 
     * @return string - in a table format
     * @return view
     **/
    public function dmc_single_upload_media()
    {
        global $wpdb;
        
        $url    = $_POST['external_url'];
        $data_array   = $_POST['single_data'];

        // Part where the reuploading of media happens
        $media_array = $this->dmc_reupload_media($data_array,'single');

        // Replacing of file values
        $replace_process = $this->dmc_replace_old_image($media_array,'single');

        foreach($replace_process as $process):
            $result = $process['status'];
        endforeach;

        print_r($result);
        return($result);
    }

    /**
     * Replace old images url with new image url
     * Usage: get the $media_array and return with result to function dmc_bulk_upload_media
     * 
     * @param string $status - 0 = failed, 1 = success uploaded
     * @param string $ID - post id
     * @param string $old_image - old image url
     * @param string $new_image - new image url
     * 
     * @usage array('status' => 1, 'id' => $ID, 'old_image' => $k, 'new_image' => $v)
     * @return array
     **/

    private function dmc_replace_old_image($media_array,$type)
    {
        global $wpdb;

        if($type == 'single') {

            $chunks = array();
            foreach($media_array as $x){
                $chunks[$media_array['img_pid']]['img_pid'] = $media_array['img_pid'];
                $chunks[$media_array['img_pid']]['content'] = stripslashes($media_array['content']);
                $chunks[$media_array['img_pid']]['data'][] = array('new_image'=>$media_array['img_url'],'old_image'=>$media_array['img_old']);
            }

        } else {

            // Possibility that 1 or more images or docs files can be found inside the single post id, then combine them
            $chunks = array();
            foreach($media_array as $x){
                $chunks[$x['img_pid']]['img_pid'] = $x['img_pid'];
                $chunks[$x['img_pid']]['content'] = stripslashes($x['content']);
                $chunks[$x['img_pid']]['data'][] = array('new_image'=>$x['img_url'],'old_image'=>$x['img_old']);
            }

        }

        // print_r('<textarea style="width:100%;height:300px;">'.json_encode($chunks).'</textarea>');

        $chunkz = array();

        // print_r('<textarea>'.json_encode($chunks).'</textarea>');

        foreach($chunks as $data):

            $ID = $data['img_pid'];
            $content = utf8_decode(urldecode(stripslashes($data['content'])));

            if($type == 'single') {

                $newArr = array_unique($data['data']);

                foreach($newArr as $xz){

                    // Check if wordpress or not
                    if(strpos($xz['old_image'], 'wp-content') !== false){
                        $old_image = utf8_decode(urldecode($xz['old_image']));
                    } else{
                        $old_image = utf8_decode(urldecode(strtok($xz['old_image'], '&w')));
                    }

                    $new_image = $xz['new_image'];
    
                    $chunkz[$data['img_pid']][] = array($old_image=>$xz['new_image']);
                }

            } else {

                foreach($data['data'] as $xz){

                    // Remove the ex
                    $old_image = utf8_decode(urldecode(strtok($xz['old_image'], '&w')));
                    $new_image = $xz['new_image'];
    
                    $chunkz[$data['img_pid']][] = array($old_image=>$xz['new_image']);
                }

            }

            foreach($chunkz as $image) {

                // Flat the arrays first to avoid parent array for strtr replace
                $flat = call_user_func_array('array_merge', $image);

                $img_identifier = key($image[0]);

                if(strpos($img_identifier, 'wp-content') !== false){
                    // Remove the &w at the last value in the image url
                    $newContent = $content;
                } else {
                    $newContent = preg_replace('(&w[^"]+)', '', $content);
                }
                
                // Strtr to replace multiple values of in the $content
                $newContent = strtr($newContent,$flat);

                // Removing special characters
                $newContent = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $newContent);
                // print_r($newContent);

                // Run the database for new content
                $query = $wpdb->query(
                    $wpdb->prepare("UPDATE $wpdb->posts SET `post_content`=%s WHERE ID= %d", $newContent, $ID)
                );

                if($query){
                    // Success
                    foreach($flat as $k => $v):
                        $result[] = array('status' => 1, 'id' => $ID, 'old_image' => $k, 'new_image' => $v);
                    endforeach;
                } else {
                    foreach($flat as $k => $v):
                        $result[] = array('status' => 0, 'id' => $ID, 'old_image' => $k, 'new_image' => $v);
                    endforeach;
                }

            }

        endforeach;

        return $result;

    }

    /**
     * Get the real image url from the url
     * Usage: Filter, replace and remove to generate proper image url
     * 
     * @return string
     **/
    private function dmc_get_image_url($data)
	{   
        $image_url = htmlentities($data);
        $image_url = str_replace("ResizeImage.aspx?img=%2f", "", $image_url);
        $image_url = str_replace("ResizeImage.aspx?img=%2F", "", $image_url);
        $image_url = str_replace('%2f','/',$image_url);
        $image_url = str_replace('%2F','/',$image_url);
        $image_url = strtok($image_url, '&');
        return $image_url;
    }

    /**
     * Generate a clean safe filename for wordpress media saving
     * Usage: Create a clean filename for media saving
     * 
     * @return string
     **/
    private function dmc_clean_filename($image_url) {
		$filename = basename( str_replace(' ','-', $image_url) );
		$filename = basename( str_replace('%2B','-',$filename) );
		$filename = basename( str_replace('%20','-',$filename) );
		$filename = basename( str_replace('_','',$filename) );
		$filename = basename( preg_replace('/[^A-Za-z0-9\.]/','',$filename) );
		$filename = basename( strtolower($filename) );
		return $filename;
    }

    /**
     * Major Process : Reupload from the url to the media library
     * Generates the result
     * 
     * @param string $image_name = Title Safe for <img>
     * @param string $reupload_image_url = Reuploaded url from media library
     * @param string $image_pid = Post ID of the found old url
     * @param string $image_oldurl = Old Image Url
     * @param string $image_content = Old Image Content
     * 
     * @usage array('img_title' => $image_name, 'img_url' => $reupload_image_url, 'img_pid' => $image_pid, 'img_old' => $image_oldurl, 'content' => $image_content )
     * @return array
     **/
    private function dmc_reupload_media($data_array,$type)
    {
        if($type == 'single') {

            $image_pid = $data_array['pid'];
            $image_content = $data_array['content'];
            $image_oldurl = $data_array['image'];
            $image_url = $this->dmc_get_image_url($data_array['image']);

            $upload_dir = wp_upload_dir();
            $image_data = file_get_contents( $image_url );
            $image_name = basename( str_replace('-',' ',$image_url) );
            $filename = basename( $this->dmc_clean_filename($image_url) );

            if ( wp_mkdir_p( $upload_dir['path'] ) ) {
                $file = $upload_dir['path'] . '/' . $filename;
            } else {
                $file = $upload_dir['basedir'] . '/' . $filename;
            }
            file_put_contents( $file, $image_data );
            $wp_filetype = wp_check_filetype( $filename, null );
            $attachment = array(
                'post_mime_type' => $wp_filetype['type'],
                'post_title' => sanitize_file_name( $filename ),
                'post_content' => '',
                'post_status' => 'inherit'
            );

            // If exist update, if not then add
            if (post_exists($filename)){
                $page = get_page_by_title($filename, OBJECT, 'attachment');
                $attach_id = $page->ID;
                $reupload_image_url = wp_get_attachment_url( $attach_id );
            } else {
                $attach_id = wp_insert_attachment( $attachment, $file );
                require_once( ABSPATH . 'wp-admin/includes/image.php' );
                $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
                wp_update_attachment_metadata( $attach_id, $attach_data );
                $reupload_image_url = wp_get_attachment_url( $attach_id );
            }

            $image_urls = array('img_title' => $image_name, 'img_url' => $reupload_image_url, 'img_pid' => $image_pid, 'img_old' => $image_oldurl, 'content' => $image_content );

        } else {

            foreach($data_array as $data):

                $image_pid = $data['pid'];
                $image_content = $data['content'];
                $image_oldurl = $data['image'];
                $image_url = $this->dmc_get_image_url($data['image']);
                // print_r($image_content);
                // print_r("http://".$image_url."<br/><br/>");
                
                $upload_dir = wp_upload_dir();
                $image_data = file_get_contents( $image_url );
                $image_name = basename( str_replace('-',' ',$image_url) );
                $filename = basename( $this->dmc_clean_filename($image_url) );
    
                if ( wp_mkdir_p( $upload_dir['path'] ) ) {
                  $file = $upload_dir['path'] . '/' . $filename;
                } else {
                  $file = $upload_dir['basedir'] . '/' . $filename;
                }
                file_put_contents( $file, $image_data );
                $wp_filetype = wp_check_filetype( $filename, null );
                $attachment = array(
                  'post_mime_type' => $wp_filetype['type'],
                  'post_title' => sanitize_file_name( $filename ),
                  'post_content' => '',
                  'post_status' => 'inherit'
                );
    
                // If exist update, if not then add
                if (post_exists($filename)){
                    $page = get_page_by_title($filename, OBJECT, 'attachment');
                    $attach_id = $page->ID;
                    $reupload_image_url = wp_get_attachment_url( $attach_id );
                } else {
                    $attach_id = wp_insert_attachment( $attachment, $file );
                    require_once( ABSPATH . 'wp-admin/includes/image.php' );
                    $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
                    wp_update_attachment_metadata( $attach_id, $attach_data );
                    $reupload_image_url = wp_get_attachment_url( $attach_id );
                }
    
                $image_urls[] = array('img_title' => $image_name, 'img_url' => $reupload_image_url, 'img_pid' => $image_pid, 'img_old' => $image_oldurl, 'content' => $image_content );
    
            endforeach;
        }

        return($image_urls);

    }

    /**
     * Get the Original URL of the 
     * Usage: Get the Original URL of the 
     * 
     * @param string 
     * 
     * @return string
     **/
    private function dmc_getOriginalURL($string)
    {
        // Decode to utf8
        $string = utf8_decode(urldecode($string));

        // Get the string between
        $img = $this->dmc_getString($string,'img=','&');

        // Replace the excess img=
        $img = str_replace('img=','',$img);

        return $img;
    }

    /**
     * Get the string between the from[$startStr] and to assigned[$endStr]
     * 
     * @param string $string
     * @param string $starStr
     * @param string $endStr
     * 
     * @return string
     **/
    private function dmc_getString($string,$startStr,$endStr) 
    {
        $startpos = strpos($string,$startStr);
        $endpos = strpos($string,$endStr,$startpos);
        $endpos = $endpos-$startpos;
        $string = substr($string,$startpos,$endpos);
        
        return $string;
    }


    // private function dmc_single_upload_media()
    // {
    //     foreach($data as $image) {

    //         if($slider == 1){
    //             $image_url = $url . $image;
    //             $image_url = htmlentities(str_replace(' ','%20', $image_url));
    //         } else {
    //             $image_url = $url . str_replace('imagesWebsites','Websites',$this->get_actual_img_url_v2($image));
    //         }

    //         $upload_dir = wp_upload_dir();
    //         $image_data = file_get_contents( $image_url );
    //         $image_name = basename( str_replace('-',' ',$image_url) );
    //         $filename = basename( $this->divi_clean_filename($image_url) );

    //         if ( wp_mkdir_p( $upload_dir['path'] ) ) {
    //           $file = $upload_dir['path'] . '/' . $filename;
    //         } else {
    //           $file = $upload_dir['basedir'] . '/' . $filename;
    //         }
    //         file_put_contents( $file, $image_data );
    //         $wp_filetype = wp_check_filetype( $filename, null );
    //         $attachment = array(
    //           'post_mime_type' => $wp_filetype['type'],
    //           'post_title' => sanitize_file_name( $filename ),
    //           'post_content' => '',
    //           'post_status' => 'inherit'
    //         );
    //         // If exist update, if not then add
    //         if (post_exists($filename)){
    //             $page = get_page_by_title($filename, OBJECT, 'attachment');
    //             $attach_id = $page->ID;
    //             $reupload_image_url = wp_get_attachment_url( $attach_id );
    //         } else {
    //             $attach_id = wp_insert_attachment( $attachment, $file );
    //             require_once( ABSPATH . 'wp-admin/includes/image.php' );
    //             $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
    //             wp_update_attachment_metadata( $attach_id, $attach_data );
    //             $reupload_image_url = wp_get_attachment_url( $attach_id );
    //         }

    //         $image_url = array('img_title' => $image_name, 'img_url' => $reupload_image_url);

    //         // Return only one value
    //         return $image_url;
    //     }
    // }

}
new CAVEIMDEV_DIVI_MEDIA_RECRAWLER();