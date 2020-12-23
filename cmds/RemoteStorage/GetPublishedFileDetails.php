<?php


namespace Steam\Command\RemoteStorage;


use Steam\Command\CommandInterface;

class GetPublishedFileDetails implements CommandInterface {
	private $files = [];

	public function __construct(...$files){
		$this->files = $files;
	}

	/**
	 * @return string
	 */
	public function getInterface(){
		return 'ISteamRemoteStorage';
	}

	/**
	 * @return string
	 */
	public function getMethod(){
		return 'GetPublishedFileDetails';
	}

	/**
	 * @return string
	 */
	public function getVersion(){
		return 'v1';
	}

	/**
	 * @return string
	 */
	public function getRequestMethod(){
		return 'POST';
	}

	/**
	 * @return array
	 */
	public function getParams(){
		$out = [];
		foreach ($this->files as $id => $file){
			$out["publishedfileids[{$id}]"] = $file;
		}
		$out['itemcount'] = count($this->files);
		return $out;
	}
}