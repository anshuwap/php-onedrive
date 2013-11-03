<?php

/**********************************************************
php-skydrive.
A PHP client library for Microsoft SkyDrive.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
**********************************************************/

// Define security credentials for your app.
// You can get these when you register your app on the Live Connect Developer Center.

define("client_id", "YOUR CLIENT ID");
define("client_secret", "YOUR CLIENT SECRET");
define("callback_uri", "YOUR CALLBACK URL");
define("skydrive_base_url", "https://apis.live.net/v5.0/");

class skydrive {

	public $access_token = '';

	public function __construct($passed_access_token) {
		$this->access_token = $passed_access_token;
	}
	
	
	// Gets the contents of a SkyDrive folder.
	// Pass in the ID of the folder you want to get.
	// Or leave the second parameter blank for the root directory (/me/skydrive/files)
	// Returns an array of the contents of the folder.

	public function get_folder($folderid, $sort_by='name', $sort_order='ascending', $limit='100') {
		if ($folderid === null) {
			$response = $this->curl_get(skydrive_base_url."me/skydrive/files?sort_by=".$sort_by."&sort_order=".$sort_order."&limit=".$limit."&access_token=".$this->access_token);
		} else {
			$response = $this->curl_get(skydrive_base_url.$folderid."/files?sort_by=".$sort_by."&sort_order=".$sort_order."&limit=".$limit."&access_token=".$this->access_token);
		}
		if (@array_key_exists('error', $response)) {
			throw new Exception($response['error']." - ".$response['description']);
			exit;
		} else {		
			$arraytoreturn = Array();
			foreach ($response as $subarray) {
				foreach ($subarray as $item) {
					array_push($arraytoreturn, Array('name' => $item['name'], 'id' => $item['id'], 'type' => $item['type']));
				}
			}
			return $arraytoreturn;
		}
	}

	// Gets the remaining quota of your SkyDrive account.
	// Returns an array containing your total quota and quota available in bytes.

	function get_quota() {
		$response = $this->curl_get(skydrive_base_url."me/skydrive/quota?access_token=".$this->access_token);
		if (@array_key_exists('error', $response)) {
			throw new Exception($response['error']." - ".$response['description']);
			exit;
		} else {			
			return $response;
		}
	}

	// Gets the properties of the folder.
	// Returns an array of folder properties.
	// You can pass null as $folderid to get the properties of your root SkyDrive folder.

	public function get_folder_properties($folderid) {
		$arraytoreturn = Array();
		if ($folderid === null) {
			$response = $this->curl_get(skydrive_base_url."/me/skydrive?access_token=".$this->access_token);
		} else {
			$response = $this->curl_get(skydrive_base_url.$folderid."?access_token=".$this->access_token);
		}
		
		if (@array_key_exists('error', $response)) {
			throw new Exception($response['error']." - ".$response['description']);
			exit;
		} else {			
			@$arraytoreturn = Array('id' => $response['id'], 'name' => $response['name'], 'parent_id' => $response['parent_id'], 'size' => $response['size'], 'source' => $response['source'], 'created_time' => $response['created_time'], 'updated_time' => $response['updated_time'], 'link' => $response['link'], 'upload_location' => $response['upload_location'], 'is_embeddable' => $response['is_embeddable'], 'count' => $response['count']);
			return $arraytoreturn;
		}
	}

	// Gets the properties of the file.
	// Returns an array of file properties.

	public function get_file_properties($fileid) {
		$response = $this->curl_get(skydrive_base_url.$fileid."?access_token=".$this->access_token);
		if (@array_key_exists('error', $response)) {
			throw new Exception($response['error']." - ".$response['description']);
			exit;
		} else {
			$arraytoreturn = Array('id' => $response['id'], 'name' => $response['name'], 'parent_id' => $response['parent_id'], 'size' => $response['size'], 'source' => $response['source'], 'created_time' => $response['created_time'], 'updated_time' => $response['updated_time'], 'link' => $response['link'], 'upload_location' => $response['upload_location'], 'is_embeddable' => $response['is_embeddable']);
			return $arraytoreturn;
		}
	}

	// Gets a pre-signed (public) direct URL to the item
	// Pass in a file ID
	// Returns a string containing the pre-signed URL.

	public function get_source_link($fileid) {
		$response = $this->get_file_properties($fileid);
		if (@array_key_exists('error', $response)) {
			throw new Exception($response['error']." - ".$response['description']);
			exit;
		} else {
			return $response['source'];
		}
	}

	// Gets a shared read link to the item.
	// This is different to the 'link' returned from get_file_properties in that it's pre-signed.
	// It's also a link to the file inside SkyDrive's interface rather than directly to the file data.

	function get_shared_read_link($fileid) {
		$response = curl_get(skydrive_base_url.$fileid."/shared_read_link?access_token=".$this->access_token);
		if (@array_key_exists('error', $response)) {
			throw new Exception($response['error']." - ".$response['description']);
			exit;
		} else {	
			return $response['link'];
		}
	}

	// Gets a shared edit (read-write) link to the item.

	function get_shared_edit_link($fileid) {
		$response = curl_get(skydrive_base_url.$fileid."/shared_edit_link?access_token=".$this->access_token);
		if (@array_key_exists('error', $response)) {
			throw new Exception($response['error']." - ".$response['description']);
			exit;
		} else {	
			return $response['link'];
		}
	}

	// Deletes an object.

	function delete_object($fileid) {
		$response = curl_delete(skydrive_base_url.$fileid."?access_token=".$this->access_token);
		if (@array_key_exists('error', $response)) {
			throw new Exception($response['error']." - ".$response['description']);
			exit;
		} else {
			return true;
		}
	}
	
	// Uploads a file from disk.
	// Pass the $folderid of the folder you want to send the file to, and the $filename path to the file.

	function put_file($folderid, $filename) {
		$r2s = skydrive_base_url.$folderid."/files/".basename($filename)."?access_token=".$this->access_token;
		$response = $this->curl_put($r2s, $filename);
		if (@array_key_exists('error', $response)) {
			throw new Exception($response['error']." - ".$response['description']);
			exit;
		} else {
			return $response;
		}
			
	}
	
	// Internally used function to make a GET request to SkyDrive and JSON-decode the result.
	
	protected function curl_get($uri) {
		$output = "";
		$output = @file_get_contents($uri);
		if ($http_response_header[0] == "HTTP/1.1 200 OK") {
			return json_decode($output, true);
		} else {
			return Array('error' => 'HTTP status code not expected - got ', 'description' => $http_response_header[0]);
		}
	}

	// Internally used function to make a PUT request to SkyDrive.

	protected function curl_put($uri, $fp) {
	  $output = "";
	  try {
	  	$pointer = fopen($fp, 'r+');
	  	$stat = fstat($pointer);
	  	$pointersize = $stat['size'];
		$ch = curl_init($uri);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($ch, CURLOPT_PUT, true);
		curl_setopt($ch, CURLOPT_INFILE, $pointer);
		curl_setopt($ch, CURLOPT_INFILESIZE, (int)$pointersize);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 4);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
		$output = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	  } catch (Exception $e) {
	  }
	  	if ($httpcode == "200") {
	  		return json_decode($output, true);
	  	} else {
	  		return array('error' => 'HTTP status code not expected - got ', 'description' => $httpcode);
	  	}
		
	}

	// Internally used function to make a DELETE request to SkyDrive.

	protected function curl_delete($uri) {
	  $output = "";
	  try {
		$ch = curl_init($uri);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');    
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 4);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
		$output = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	  } catch (Exception $e) {
	  }
	  	if ($httpcode == "200") {
	  		return json_decode($output, true);
	  	} else {
	  		return array('error' => 'HTTP status code not expected - got ', 'description' => $httpcode);
	  	}
	}
	

}

class skydrive_auth {

	// Builds a URL for the user to log in to SkyDrive and get the authorization code, which can then be
	// passed onto get_oauth_token to get a valid oAuth token.

	public static function build_oauth_url() {
		$response = "https://login.live.com/oauth20_authorize.srf?client_id=".client_id."&scope=wl.signin%20wl.skydrive_update%20wl.basic&response_type=code&redirect_uri=".urlencode(callback_uri);
		return $response;
	}


	// Obtains an oAuth token
	// Pass in the authorization code parameter obtained from the inital callback.
	// Returns the oAuth token and an expiry time in seconds from now (usually 3600 but may vary in future).

	public static function get_oauth_token($auth) {
		$arraytoreturn = array();
		$output = "";
		try {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://login.live.com/oauth20_token.srf");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);	
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/x-www-form-urlencoded',
				));
			curl_setopt($ch, CURLOPT_POST, TRUE);

			$data = "client_id=".client_id."&redirect_uri=".urlencode(callback_uri)."&client_secret=".urlencode(client_secret)."&code=".$auth."&grant_type=authorization_code";	
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			$output = curl_exec($ch);
		} catch (Exception $e) {
		}
	
		$out2 = json_decode($output, true);
		$arraytoreturn = Array('access_token' => $out2['access_token'], 'expires_in' => $out2['expires_in']);
		return $arraytoreturn;
	}
}



?>