<?php
use Illuminate\Database\QueryException;


// We don't need this any more since we're not using the data-url image plugin any more.
// // This is a special validator for htmlpurifier to allow data urls with empty content type.
// class HTMLPurifier_URIScheme_data_platypus extends HTMLPurifier_URIScheme_data {
// 	public $allowed_types = array(
// 			// you better write validation code for other types if you
// 			// decide to allow them
// 			'image/jpeg' => true,
// 			'image/gif' => true,
// 			'image/png' => true,
// 			'' => true,
// 	);
// }
// HTMLPurifier_URISchemeRegistry::instance()->register('data', new HTMLPurifier_URIScheme_data_platypus());


// A special purifier to strip the hostname off urls that belong to us. 
// With this in place, there shouldn't be any issue with migrating the domain. 
class HTMLPurifier_URIScheme_http_platypus extends HTMLPurifier_URIScheme_http {

	public function doValidate(&$uri, $config, $context) {
		
		if (parent::doValidate($uri, $config, $context)) {
			
			// strip the hostname from local urls.
			if ( ($uri->host == Request::server ("SERVER_NAME")) 
					|| ($uri->host == Request::server ("HTTP_HOST")) 
					|| ($uri->host == parse_url ( Config::get('app.url'), PHP_URL_HOST)) ) {
						
				$uri->host = null;
				$uri->scheme = null;
				$uri->userinfo = null;
			}
							
			return true;
		} else {
			return false;
		}
		
	}
	
}
HTMLPurifier_URISchemeRegistry::instance()->register('http', new HTMLPurifier_URIScheme_http_platypus());


// and the version for https as well
class HTMLPurifier_URIScheme_https_platypus extends HTMLPurifier_URIScheme_http_platypus
{
	public $default_port = 443;
	public $secure = true;
}
HTMLPurifier_URISchemeRegistry::instance()->register('https', new HTMLPurifier_URIScheme_https_platypus());



// this is a filter that translates the URL of text blocks for archiving
class TextBlockLinkManipulatorforArchive extends HTMLPurifier_URIFilter {
	public $name = 'TextBlockLinkManipulatorforArchive';
	public $post = true;
	
	private $textBlock;
	private $pathPrefix;
	
	public function __construct(TextBlock $textBlock, $pathPrefix) {
		$this->textBlock = $textBlock;
		$this->pathPrefix = $pathPrefix;
	}
	
	public function prepare($config) {}
	
	public function filter(&$url, $config, $context) {
		if($url->scheme != null) return true;
		if($url->host != null) return true;
		// the URL should be local.
		
		$path = explode('/', $url->path);
		
		$attachment = null;
		
		foreach(array('viewTextBlockAttachment','downloadTextBlockAttachment') as $routeName) {
			$templatePath = route($routeName, array('textblock_id' => $this->textBlock->id, 'role' => 'ROLE', 'attachment_id' => 'ATTACHMENTID', 'file_name' => 'FILENAME'));
			$templatePath = parse_url( $templatePath, PHP_URL_PATH);
			$templatePath = explode('/', $templatePath);
			
			$attachment_id = null;
			
			if(count($path) != count($templatePath)) continue;
			for($i = 0; $i < count($path); $i++) {
				if($templatePath[$i] == 'ROLE') {
					if(!is_numeric($path[$i])) continue;
				} else if ($templatePath[$i] == 'ATTACHMENTID') {
					if(!is_numeric($path[$i])) continue;
					$attachment_id = $path[$i];
				} else if ($templatePath[$i] == 'ATTACHMENTID') {
					// any filename is allowed.
				} else {
					if($path[$i] != $templatePath[$i]) continue;
				}
			}
			
			if(!isset($attachment_id)) continue;
			
			$attachment = TextBlockAttachment::find($attachment_id);
			if(!isset($attachment) || ($attachment->text_block_id == $this->textBlock->id)) continue;
			
			break;
			
		}
		
		if(!isset($attachment)) return true;

		$attachment->injectCachedRelation('textBlock', $this->textBlock);
		
		$position = $attachment->position;
		if($position < 10) {
			$position = "0$position";
		}
		
		$path = $attachment->getArchiveFileName($this->pathPrefix);
		$url->path = $path;
		return true;
		
	}
}

class Platypus {

	static public function transaction($closure) {
		$maxAttempts = 10;
		
		$attempt = 0;
		while(true) {
			$attempt++;
			
			if (! DB::statement('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;')) {
				throw new Exception('Unable to set transaction isolation to serializable.');
			}
			

			try {
				return DB::transaction($closure);
			} catch ( QueryException $e ) {
				if ($e->getCode() != 40001 || $attempt == $maxAttempts) {
					throw $e;
				}
			}
			
		}
	}
	
	static public function sanitiseHtml($html) {

		// set up the purifier.
		$config = HTMLPurifier_Config::createDefault();
		$config->set('Cache.SerializerPath', storage_path().'/htmlpurifier');
		
	
		//$config->set('URI.AllowedSchemes', array('http' => true, 'https' => true, 'mailto' => true, 'ftp' => true, 'nntp' => true, 'news' => true, 'data' => true));
		
		$purifier = new HTMLPurifier($config);
			
		// purify.
		return $purifier->purify($html);
	}

	static public function translateAttachmentUrls(TextBlock $textBlock, $attachmentPathPrefix) {
		// set up the purifier.
		$config = HTMLPurifier_Config::createDefault();
		$config->set('Cache.SerializerPath', storage_path().'/htmlpurifier');
		
		$uri = $config->getDefinition('URI');
		$uri->addFilter(new TextBlockLinkManipulatorforArchive($textBlock, $attachmentPathPrefix), $config);
		
		$purifier = new HTMLPurifier($config);
		return $purifier->purify($textBlock->presenter()->text);		
	}
	
}

