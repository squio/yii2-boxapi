<?php
// based on https://github.com/maengkom/boxapi
namespace squio\boxapi\traits;
use yii\helpers\Json;

use squio\boxapi\helpers\Curl;

trait BoxContent {

	/*
	|
	| ================================= Folder API Methods ==================================
	| Check documentation here https://box-content.readme.io/reference#folder-object
	|
	*/
    private $_curl;

    public function curl()
    {
        if (! $this->_curl) {
            $this->_curl = new Curl();
        }
        return $this->_curl;
    }

	/* Get the details of the mentioned folder */
	public function getFolderInfo($folder_id) {
		$url = $this->api_url . "/folders/$folder_id";
		return $this->http_get($url);
	}

	/* Get the list of items in the mentioned folder */
	public function getFolderItems($folder_id) {
		$url = $this->api_url . "/folders/$folder_id/items";
		return $this->http_get($url);
	}

	/* Create folder */
	public function createFolder($name, $parent_id) {
		$url = $this->api_url . "/folders";
		$data = "-d '{\"name\":\"$name\", \"parent\": {\"id\": \"$parent_id\"}}'";
		return $this->http_post($url, $data);
	}

	/* Update folder */
	public function updateFolder($folder_id, $folder_name) {
		$url = $this->api_url . "/folders/$folder_id";
		$data = "-d '{\"name\":\"$folder_name\"}'";
		return $this->http_put($url, $data);
	}

	/* Delete folder */
	public function deleteFolder($folder_id) {
		$url = $this->api_url . "/folders/$folder_id";
		return $this->http_delete($url);
	}

	/* Copy folder */
	public function copyFolder($folder_id, $folder_dest_id) {
		$url = $this->api_url . "/folders/$folder_id/copy";
		$data = "-d '{\"parent\": {\"id\": \"$folder_dest_id\"}}'";
		return $this->http_post($url, $data);
	}

	/* Create shared link folder */
	public function createSharedLinkFolder($folder_id) {
		$url = $this->api_url . "/folders/$folder_id";
		$data = "-d '{\"shared_link\": {\"access\": \"open\"}}'";
		return $this->http_put($url, $data);
	}

	/* Get folder collaborations */
	public function getFolderCollaborations($folder_id) {
		$url = $this->api_url . "/folders/$folder_id/collaborations";
		return $this->http_get($url);
	}

	/* Get trashed items */
	public function getTrashedItems($limit = 10, $offset = 0) {
		$url = $this->api_url . "/folders/trash/items?limit=$limit&offset=$offset";
		return $this->http_get($url);
	}

	/* Get trashed folder */
	public function getTrashedFolder($folder_id) {
		$url = $this->api_url . "/folders/$folder_id/trash";
		return $this->http_get($url);
	}

	/* Delete folder permanently */
	public function deleteFolderPermanent($folder_id) {
		$url = $this->api_url . "/folders/$folder_id/trash";
		return $this->http_delete($url);
	}

	/* Restore a folder */
	public function restoreFolder($folder_id, $newname = '') {
		$url = $this->api_url . "/folders/$folder_id";
		if (empty($newname)) {
			$data = "-d '{\"name\": \"$newname\"}'";
		}
		return $this->http_post($url, $data);
	}


	/*
	|
	| ================================= File API Methods ==================================
	| Check Box documentation here https://box-content.readme.io/reference#files
	|
	*/

	/* Get the details of the mentioned file */
	public function getFileInfo($file_id) {
		$url = $this->api_url . "/files/$file_id";
		return $this->http_get($url);
	}

    /* Get details of previous versions the file */
	public function getFileVersionInfo($file_id) {
		$url = $this->api_url . "/files/$file_id/versions";
		return $this->http_get($url);
	}
	/* Update file */
	public function updateFileInfo($file_id, $file_name) {
		$url = $this->api_url . "/files/$file_id";
		$data = "-d '{\"name\":\"$file_name\"}'";
		return $this->http_put($url, $data);
	}

	/* Toggle lock or unlock a file */
	public function toggleLock($file_id, $lockType = null, $expire = null, $canDownload = false) {
		$url = $this->api_url . "/files/$file_id";
		$data = "-d '{\"lock\":{\"type\": \"$lockType\", \"expires_at\": \"$expire\", \"is_download_prevented\": $canDownload}'";
		return $this->http_put($url, $data);
	}

	/* Download file */
	public function downloadFile($file_id, $version_id = null) {

		//set the headers
		$headers = $this->auth_header;

        $url = $this->api_url . "/files/$file_id/content";
        if ($version_id) {
            $url .= '?version=' . $version_id;
        }
        // return $this->http_get($url);
        $curl = curl_init();
		//set the options
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array($headers));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //returns to a variable instead of straight to page
		curl_setopt($curl, CURLOPT_HEADER, true); //returns headers as part of output
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true); //I needed this for it to work
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); //I needed this for it to work

		$headers = curl_exec($curl); //because the returned page is blank, this will include headers only

		return curl_getinfo($curl, CURLINFO_REDIRECT_URL);
	}

	/* Upload a file */
	public function uploadFile($filename ,$parent_id, $name = null) {
		$url = $this->upload_url . '/files/content';

		if ( ! isset($name)) {
			$name = basename($filename);
		}

		$attributes = "-F attributes='{\"name\":\"$name\", \"parent\":{\"id\":\"$parent_id\"}}'";
		$attributes = $attributes . " -F file=@$filename";
		return $this->http_post($url, $attributes);
	}

	/* Delete a file */
	public function deleteFile($file_id) {
		$url = $this->api_url . "/files/$file_id";
		return $this->http_delete($url);
	}

	/* Update a file - new upload to update content of file */
	public function updateFile($filename, $file_id) {
		$url = $this->upload_url . '/files/$file_id/content';
		$attributes = " -F file=@$filename";
		return $this->http_post($url, $attributes);
	}

	/* Copy a file */
	public function copyFile($file_id, $folder_dest_id) {
		$url = $this->api_url . "/files/$file_id/copy";
		$data = "-d '{\"parent\": {\"id\": \"$folder_dest_id\"}}'";
		return $this->http_post($url, $data);
	}

	/* Get thumbnail of a file */
	public function getThumbnail($file_id, $min_height = '256', $min_width = '256', $max_height = '256', $max_width = '256') {

		$url = $this->api_url . "/files/$file_id/thumbnail.png?min_height=$min_height&min_width=$min_width&max_height=$min_width&max_width=$min_width";

		//set the headers
		$headers = $this->auth_header;

		$curl = curl_init();

		//set the options
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array($headers));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //returns to a variable instead of straight to page
		curl_setopt($curl, CURLOPT_HEADER, true); //returns headers as part of output
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); //I needed this for it to work
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); //I needed this for it to work

		$response = curl_exec($curl); //because the returned page is blank, this will include headers only

		// Then, after your curl_exec call:
		$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
		$header = substr($response, 0, $header_size);
		$body = substr($response, $header_size);

		return $body;

	}

	/* Get embed link of a file */
	public function getEmbedLink($file_id) {
		$url = $this->api_url . "/files/$file_id?fields=expiring_embed_link";
		return $this->http_get($url);
	}

	/* Share a file */
	public function createShareLink($file_id, $access) {
		$url = $this->api_url . "/files/$file_id";
		$data = "-d '{\"shared_link\": {\"access\": \"$access\"}}'";
		return $this->http_put($url, $data);
	}

	/* Get trashed file */
	public function getTrashedFile($file_id) {
		$url = $this->api_url . "/files/$file_id/trash";
		return $this->http_get($url);
	}

	/* Delete file permanently */
	public function deleteFilePermanent($file_id) {
		$url = $this->api_url . "/files/$file_id/trash";
		return $this->http_delete($url);
	}

	/* Restore a file */
	public function restoreItem($file_id, $newname = '') {
		$url = $this->api_url . "/files/$file_id";
		if (empty($newname)) {
			$data = "-d '{\"name\": \"$newname\"}'";
		}
		return $this->http_post($url, $data);
	}

	/* View comments */
	public function viewComments($file_id) {
		$url = $this->api_url . "/files/$file_id/comments";
		return $this->http_get($url);
	}

	/* Get file tasks */
	public function getFileTasks($file_id) {
		$url = $this->api_url . "/files/$file_id/tasks";
		return $this->http_get($url);
	}

    /* Search for files/folders */
    public function search($query, $type = '', $scope = 'user_content', $limit = 100)
    {

        $url = $this->api_url . "/search?query=$query&type=$type&scope=$scope&limit=$limit";
        return $this->http_get($url);
    }


	// ================================= Helper Methods ==================================

	protected function http_get($url, $data = '') {
        $headers = [
            $this->auth_header,
            'Accept: application/json',
            'Content-Type: application/json',
        ];
        $this->curl()->setOption(CURLOPT_HTTPHEADER, $headers);
        $data = $this->curl()->get($url, []);
		return ($data) ? Json::decode($data, true) : null;
	}

	protected function http_post($url, $data = '', $content_type='application/json') {
        $headers = [
            $this->auth_header,
            'Accept: application/json',
            'Content-Type: ' . $content_type,
        ];
        $this->curl()->setOption(CURLOPT_HTTPHEADER, $headers);
        $data = $this->curl()->post($url, [], $data);
        return ($data) ? Json::decode($data, true) : null;
	}

	protected function http_put($url, $data = '') {
        $headers = [
            $this->auth_header,
            'Accept: application/json',
            'Content-Type: application/json',
        ];
        $this->curl()->setOption(CURLOPT_HTTPHEADER, $headers);
        $data = $this->curl()->put($url, [], $data);
        return ($data) ? Json::decode($data, true) : null;
	}

	protected function http_delete($url) {
        $headers = [
            $this->auth_header,
            'Accept: application/json',
            'Content-Type: application/json',
        ];
        $this->curl()->setOption(CURLOPT_HTTPHEADER, $headers);
        $data = $this->curl()->delete($url);
		return $data;
	}

}
