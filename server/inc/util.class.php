<?php
use Bricky\Template;
/**
 *
 * @author Sein
 *        
 *         Bunch of useful static functions.
 */
class Util{
	
	public static function getAgentById($agentId){
		global $DB;
		
		$res = $DB->query("SELECT agentName FROM Agent WHERE agentId=".$DB->quote($agentId));
		$entry = $res->fetch();
		return $entry['agentName'];
	}
	
	public static function validUtf($str){
		$len = strlen($str);
		for($i = 0; $i < $len; $i++){
			$c = ord($str[$i]);
			if ($c > 128) {
				if (($c > 247)) return false;
				elseif ($c > 239) $bytes = 4;
				elseif ($c > 223) $bytes = 3;
				elseif ($c > 191) $bytes = 2;
				else return false;
				if (($i + $bytes) > $len) return false;
				while ($bytes > 1) {
					$i++;
					$b = ord($str[$i]);
					if ($b < 128 || $b > 191) return false;
					$bytes--;
				}
			}
		}
		return true;
	}
	
	static function number($num){
		$value = "$num";
		$string = $value[0];
		for($x=1;$x<strlen($value);$x++){
			if((strlen($value)-$x)%3==0){
				$string .= "'";
			}
			$string .= $value[$x];
		}
		return $string;
	}

	/**
	 * Checks if a given email is of valid syntax
	 *
	 * @param string $email
	 *        	email address to check
	 * @return true if valid email, false if not
	 */
	public static function isValidEmail($email){
		return filter_var($email, FILTER_VALIDATE_EMAIL);
	}
	
	public static function getUsernameById($id){
		global $FACTORIES;
		
		$user = $FACTORIES::getUserFactory()->get($id);
		if($user === null){
			return "Unknown-$id";
		}
		return $user->getUsername();
	}
	
	public static function subtract($x, $y){
		return ($x - $y);
	}
	
	public static function sectotime($soucet) {
		// convert seconds to human readable format
		$vysledek = "";
		if($soucet > 86400){
			$dnu = floor($soucet / 86400);
			if($dnu > 0){
				$vysledek .= $dnu . "d ";
			}
			$soucet = $soucet % 86400;
		}
		$vysledek .= gmdate("H:i:s", $soucet);
		return $vysledek;
	}
	
	public static function showperc($part,$total,$decs=2) {
			// show nicely formated percentage
		if($total > 0){
			$vys = round(($part / $total) * 100, $decs);
			if($vys == 100 && $part < $total){
				$vys -= 1 / (10 ^ $decs);
			}
			if($vys == 0 && $part > 0){
				$vys += 1 / (10 ^ $decs);
			}
		}
		else{
			$vys = 0;
		}
		$vysnew = Util::niceround($vys, $decs);
		return $vysnew;
	}
	
	public static function niceround($num, $dec){
		// round to specific amount of decimal places
		$stri = strval(round($num, $dec));
		if($dec > 0){
			$pozice = strpos($stri, ".");
			if($pozice === false){
				$stri .= ".00";
			}
			else{
				while(strlen($stri) - $pozice <= $dec){
					$stri .= "0";
				}
			}
		}
		return $stri;
	}

	/**
	 * This sends a given email with text and subject to the address.
	 *
	 * @param string $address
	 *        	email address of the receiver
	 * @param string $subject
	 *        	subject of the email
	 * @param string $text
	 *        	html content of the email
	 * @return true on success, false on failure
	 */
	public static function sendMail($address, $subject, $text){
		$header = "Content-type: text/html; charset=utf8\r\n";
		$header .= "From: Hashtopussy <noreply@hashtopussy>\r\n";
		if(!mail($address, $subject, $text, $header)){
			return false;
		}
		else{
			return true;
		}
	}

	/**
	 * Generates a random string with mixedalphanumeric chars
	 *
	 * @param int $length
	 *        	length of random string to generate
	 * @return string random string
	 */
	public static function randomString($length){
		$charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$result = "";
		for($x = 0; $x < $length; $x++){
			$result .= $charset[rand(0, strlen($charset) - 1)];
		}
		return $result;
	}

	public static function createPrefixedString($table, $dict){
		$concat = "";
		$counter = 0;
		$size = count($dict);
		
		foreach($dict as $key => $val){
			if($counter < $size - 1){
				$concat = $concat . "`" . $table . "`" . "." . "`" . $key . "`" . " AS " . $val . ",";
				$counter = $counter + 1;
			}
			else{
				$concat = $concat . "`" . $table . "`" . "." . "`" . $key . "`" . " AS " . $val;
				$counter = $counter + 1;
			}
		}
		
		return $concat;
	}

	public static function startsWith($search, $pattern){
		if(strpos($search, $pattern) === 0){
			return true;
		}
		else{
			return false;
		}
	}

	public static function endsWith($haystack, $needle){
		// search forward starting from end minus needle length characters
		return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
	}

	public static function deleteDuplicatesFromJoinResult($dict){
		$pkStack = array();
		$nonDuplicates = array();
		foreach($dict as $elem){
			if(!in_array($elem->getId(), $pkStack)){
				array_push($pkStack, $elem->getId());
				array_push($nonDuplicates, $elem);
			}
		}
		return $nonDuplicates;
	}
}
