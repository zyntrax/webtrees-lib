<?php
/**
 * webtrees-lib: MyArtJaub library for webtrees
 *
 * @package MyArtJaub\Webtrees
 * @subpackage Functions
 * @author Jonathan Jaubart <dev@jaubart.com>
 * @copyright Copyright (c) 2009-2016, Jonathan Jaubart
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3
 */
namespace MyArtJaub\Webtrees\Functions; 

use \Fisharebest\Webtrees as fw;
use Fisharebest\Webtrees\Filter;

/**
 * General functions.
 */
class Functions {
	
	/**
	 * This array contains the cached short month names, based on the cal_info functions.
	 * Retrieves the abbrevmonths values of months: Jan, Feb, Mar...
	 * 
	 * @uses cal_info
	 * @var array $calendarShortMonths Cached array of abbreviated short month names
	 */
	private static $calendarShortMonths = array();
	
	/**
	 * Debug tool: prompt a Javascript pop-up with a text
	 *
	 * @param string $text Text to display
	 */
	static public function promptAlert($text){
		echo '<script>';
		echo 'alert("',fw\Filter::escapeHtml($text),'")';
		echo '</script>';
	}
	
	/**
	 * Return the result of a division, and a default value if denomintaor is 0
	 * 
	 * @param integer $num Numerator
	 * @param integer $denom Denominator
	 * @param float $default Default value if denominator null or 0
	 * @return float Result of the safe division
	 */
	public static function safeDivision($num, $denom, $default = 0) {
		if($denom && $denom!=0){
			return $num / $denom;
		}
		return $default;
	}
	
	/**
	 * Returns the percentage of two numbers
	 *
	 * @param int $num Numerator
	 * @param int $denom Denominator
	 * @param float $default Default value if denominator null or 0
	 * @return float Percentage
	 */
	public static function getPercentage($num, $denom, $default = 0){
		return 100 * self::safeDivision($num, $denom, $default);
	}
	
	/**
	 * Get width and heigth of an image resized in order fit a target size.
	 *
	 * @param string $file The image to resize
	 * @param int $target	The final max width/height
	 * @return array array of ($width, $height). One of them must be $target
	 */
	static public function getResizedImageSize($file, $target=25){
		list($width, $height, , ) = getimagesize($file);
		$max = max($width, $height);
		$rapp = $target / $max;
		$width = intval($rapp * $width);
		$height = intval($rapp * $height);
		return array($width, $height);
	}

	
	/**
	 * Checks if a table exist in the DB schema
	 *
	 * @param string $table Name of the table to look for
	 * @return boolean Does the table exist
	 */
	public static function doesTableExist($table) {
		try {
			fw\Database::prepare("SELECT 1 FROM {$table}")->fetchOne();
			return true;
		} catch (\PDOException $ex) {
			return false;
		}
	}
	
	/**
	 * Returns a randomy generated token of a given size
	 *
	 * @param int $length Length of the token, default to 32
	 * @return string Random token
	 */
	public static function generateRandomToken($length=32) {
		$chars = str_split('abcdefghijkmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
		$len_chars = count($chars);
		$token = '';
		
		for ($i = 0; $i < $length; $i++)
			$token .= $chars[ mt_rand(0, $len_chars - 1) ];
		
		# Number of 32 char chunks
		$chunks = ceil( strlen($token) / 32 );
		$md5token = '';
		
		# Run each chunk through md5
		for ( $i=1; $i<=$chunks; $i++ )
			$md5token .= md5( substr($token, $i * 32 - 32, 32) );
		
			# Trim the token
		return substr($md5token, 0, $length);		
	}
	
	/**
	 * Generate the key used by the encryption to safe base64 functions.
	 * 
	 * @return string Encryption key
	 */
	protected static function getBase64EncryptionKey() {	    
	    $key = 'STANDARDKEYIFNOSERVER';
	    if(!empty(Filter::server('SERVER_NAME')) && !empty(Filter::server('SERVER_SOFTWARE')))
	        $key = md5(Filter::server('SERVER_NAME').Filter::server('SERVER_SOFTWARE'));
	    
	    return $key;
	}
	
	/**	  
	 * Encrypt a text, and encode it to base64 compatible with URL use
	 * 	(no +, no /, no =)
	 *
	 * @param string $data Text to encrypt
	 * @return string Encrypted and encoded text
	 */
	public static function encryptToSafeBase64($data){		
		$nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);	
		$id = sodium_crypto_secretbox($data, $nonce, self::getBase64EncryptionKey());
		$encrypted = base64_encode($nonce.$id);
		
		//sodium_memzero($data);   // Requires PHP 7.2
		
		// +, / and = are not URL-compatible
		$encrypted = str_replace('+', '-', $encrypted);
		$encrypted = str_replace('/', '_', $encrypted);
		$encrypted = str_replace('=', '*', $encrypted);
		return $encrypted;
	}
	
	/**
	 * Decode and encrypt a text from base64 compatible with URL use
	 *
	 * @param string $encrypted Text to decrypt
	 * @return string Decrypted text
	 */
	public static function decryptFromSafeBase64($encrypted){
		$encrypted = str_replace('-', '+', $encrypted);
		$encrypted = str_replace('_', '/', $encrypted);
		$encrypted = str_replace('*', '=', $encrypted);
		$encrypted = base64_decode($encrypted);
		if($encrypted === false)
			throw new \InvalidArgumentException('The encrypted value is not in correct base64 format.');
		
		if (mb_strlen($encrypted, '8bit') < (SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES))
		    throw new \InvalidArgumentException('The encrypted value does not contain enough characters for the key.');

	    $nonce = mb_substr($encrypted, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
	    $ciphertext = mb_substr($encrypted, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');
        
        $decrypted = sodium_crypto_secretbox_open($ciphertext, $nonce, self::getBase64EncryptionKey());
		
        if($decrypted === false) {
            throw new \InvalidArgumentException('The message has been tampered with in transit.');
        }
        
        //sodium_memzero($encrypted);   // Requires PHP 7.2
        
        return $decrypted;
	}
	
	/**
	 * Encode a string from the file system encoding to UTF-8 (if necessary)
	 *
	 * @param string $string Filesystem encoded string to encode
	 * @return string UTF-8 encoded string
	 */
	public static function encodeFileSystemToUtf8($string){
		if (strtoupper(substr(php_uname('s'), 0, 7)) === 'WINDOWS') {
		    return iconv('cp1252', 'utf-8//IGNORE',$string);
		}
		return $string;
	}
	
	/**
	 * Encode a string from UTF-8 to the file system encoding (if necessary)
	 *
	 * @param string $string UTF-8 encoded string to encode
	 * @return string Filesystem encoded string
	 */
	public static function encodeUtf8ToFileSystem($string){
		if (preg_match('//u', $string) && strtoupper(substr(php_uname('s'), 0, 7)) === 'WINDOWS') {
			return iconv('utf-8', 'cp1252//IGNORE' ,  $string);
		}
		return $string;
	}
	
	/**
	 * Check whether a path is under a valid form.
	 * 
	 * @param string $filename Filename path to check
	 * @param boolean $acceptfolder Should folders be accepted?
	 * @return boolean True if path valid
	 */
	public static function isValidPath($filename, $acceptfolder = FALSE) {		
		if(strpbrk($filename, $acceptfolder ? '?%*:|"<>' : '\\/?%*:|"<>') === FALSE) return true;
		return false;
	}
	
	/**
	 * Return short names for the months of the specific calendar.
	 * 
	 * @see \cal_info()
	 * @param integer $calendarId Calendar ID (according to PHP cal_info)
	 * @return array Array of month short names
	 */
	public static function getCalendarShortMonths($calendarId = 0) {
		if(!isset(self::$calendarShortMonths[$calendarId])) {
			$calendar_info = cal_info($calendarId);
			self::$calendarShortMonths[$calendarId] = $calendar_info['abbrevmonths'];
		}		
		return self::$calendarShortMonths[$calendarId];
	}
	
	/**
	 * Returns the generation associated with a Sosa number
	 *
	 * @param int $sosa Sosa number
	 * @return number
	 */
	public static function getGeneration($sosa){
		return(int)log($sosa, 2)+1;
	}
	
	


	/**
	 * Returns whether the image type is supported by the system, and if so, return the standardised type
	 *
	 * @param string $reqtype Extension to test
	 * @return boolean|string Is supported?
	 */
	public static function isImageTypeSupported($reqtype) {
	    $supportByGD = array('jpg'=>'jpeg', 'jpeg'=>'jpeg', 'gif'=>'gif', 'png'=>'png');
	    $reqtype = strtolower($reqtype);
	
	    if (empty($supportByGD[$reqtype])) {
	        return false;
	    }
	
	    $type = $supportByGD[$reqtype];
	
	    if (function_exists('imagecreatefrom'.$type) && function_exists('image'.$type)) {
	        return $type;
	    }
	
	    return false;
	}
		
}
