<?php
/*WebsiteDefender Agent and verification file. If this file is deleted, WebsiteDefender will no longer work!*/

define('PAS_RES', '395dc8c2175df6d12cdf');
define('PAS_REQ', 'd87c309787f846f8b48e');
define('RSA_LEN', '256');
define('RSA_PUB', '65537');
define('RSA_MOD', '99443556420701085254813013502680291809403816403097633732945478080749980208561');

define('DEFLATE_RESPONSE_DATA', True);

header('Content-type: application/json');
error_reporting(0);
$version=2;$requestId='0';$jsonRPCVer='2.0';

if(!function_exists('property_exists'))
{ 
	function property_exists($class, $property)
	{ 
        if(is_object($class))$vars=get_object_vars($class); 
		else $vars=get_class_vars($class); 
        return array_key_exists($property, $vars); 
    } 
} 
function senzorErrorHandler($errno, $errstr, $errfile, $errline)
{
	switch ($errno)
	{
		case E_NOTICE:
		case E_USER_NOTICE:
		case E_WARNING:
		case E_USER_WARNING:
			return True;        
		case E_ERROR:
			$code = 0;
			break;
		case E_USER_ERROR:
			$code = 1;
			break;
		default:
			$code = 2;
	}		
	if(function_exists('json_encode'))
	{
		$message = "{$errstr} ({$errfile} Line: {$errline})";
		$response = json_encode(array('jsonrpc' => $GLOBALS['jsonRPCVer'],'id'=>$GLOBALS['requestId'],'error'=>array('code'=>$code,'message'=> $message)));
	}
	else
	{
		$message = "{$errstr}";
		$response = "{\"jsonrpc\":{$GLOBALS['jsonRPCVer']},\"id\":{$GLOBALS['requestId']},\"error\":{\"code\":{$code},\"message\":\"{$message}\"}}";
	}
	die($response);
}

set_error_handler("senzorErrorHandler");
if(!function_exists('json_encode'))
{   
    if (!file_exists("compat/json.php"))    
        trigger_error("#COMPAT-JSON#", E_USER_ERROR);    
	require_once("compat/json.php");
    function json_encode($data)
    {
        $json = new Services_JSON();
        return($json->encode($data));
    }
}
if(!function_exists('json_decode'))
{
    if(!file_exists("compat/json.php")) 
    	trigger_error("#COMPAT-JSON#", E_USER_ERROR);   
    function json_decode($data)
    {
        $json = new Services_JSON();
        return($json->decode($data));
    }
}

if(function_exists('bcmod'))
	define('BCMOD', true);
else
	{
        if(!file_exists("compat/array_fill.php")||!file_exists("compat/bcpowmod.php")||!file_exists("compat/biginteger.php")) 
        	trigger_error("#COMPAT-BI#", E_USER_ERROR);
		require_once("compat/array_fill.php");
		require_once("compat/bcpowmod.php");
		require_once("compat/biginteger.php");
	}
	
function rsa_encrypt($message, $public_key, $modulus, $keylength, $notSigning = true)
{
	$result = '';
	$chunkLength = intval($keylength / 8) - 11;
	for($i = 0; $i < strlen($message); $i=$i+$chunkLength)
	{
		$padded = add_PKCS1_padding(substr($message, $i, $chunkLength), $notSigning, intval($keylength/8));
		$number = binary_to_number($padded);
		$encrypted = pow_mod($number, $public_key, $modulus);
		$binary = number_to_binary($encrypted, intval($keylength/8));
		$result .= $binary;
	}
	return $result;
}
function rsa_decrypt($message, $private_key, $modulus, $keylength)
{
	$result = '';
	$chunkLength = intval($keylength/8);
	for($i = 0; $i < strlen($message); $i=$i+$chunkLength)
	{
		$number = binary_to_number(substr($message, $i, $chunkLength));
		$decrypted = pow_mod($number, $private_key, $modulus);
		$presult = number_to_binary($decrypted, $chunkLength);
		$pres = remove_PKCS1_padding($presult, $chunkLength);
		if ($pres === FALSE)
			return FALSE;
		$result .= $pres;
	}
	return $result;
}
function rsa_sign($message, $private_key, $modulus, $keylength)
{
	return rsa_encrypt($message, $private_key, $modulus, $keylength, false);
}
function rsa_verify($message, $signature, $public_key, $modulus, $keylength)
{
	$result = false;
	$result = ($message==rsa_decrypt($signature, $public_key, $modulus, $keylength));
	return $result;
}
function pow_mod($p, $q, $r)
{
	if(defined('BCMOD'))
	{
		$factors = array();
		$div = $q;
		$power_of_two = 0;
		while(bccomp($div, "0") == 1) //BCCOMP_LARGER
		{
			$rem = bcmod($div, 2);
			$div = bcdiv($div, 2);
		
			if($rem) array_push($factors, $power_of_two);
			$power_of_two++;
		}
		$partial_results = array();
		$part_res = $p;
		$idx = 0;
		foreach($factors as $factor)
		{
			while($idx < $factor)
			{
				$part_res = bcpow($part_res, "2");
				$part_res = bcmod($part_res, $r);
				$idx++;
			}
			array_push($partial_results, $part_res);
		}
		$result = "1";
		foreach($partial_results as $part_res)
		{
			$result = bcmul($result, $part_res);
			$result = bcmod($result, $r);
		}
		return $result;
	}
	//Math_BigInteger implementation 
 	$p = new Math_BigInteger($p);
	$q = new Math_BigInteger($q);
	$r = new Math_BigInteger($r);
 	$x = $p->modPow($q, $r);
	return $x->toString();
}

function add_PKCS1_padding($data, $isPublicKey, $blocksize)
{	
	$pad_length = $blocksize - 3 - strlen($data);
	if($isPublicKey)
	{
		$block_type = "\x02";	
		$padding = "";		
		for($i = 0; $i < $pad_length; $i++)
			$padding .= chr(mt_rand(1, 255));
	}
	else
	{
		$block_type = "\x01";
		$padding = str_repeat("\xFF", $pad_length);
	}	
	return "\x00" . $block_type . $padding . "\x00" . $data;
}
function remove_PKCS1_padding($data, $blocksize)
{
	#bad data length
	if(strlen($data) != $blocksize) return FALSE;
	if(($data[0]!="\0") || ( ($data[1] != "\x01") && ($data[1] != "\x02") )) return FALSE;
	#bad padding type
	$offset = strpos($data, "\0", 1);
	return substr($data, $offset + 1);
}
function binary_to_number($data)
{	
	if(defined('BCMOD'))
	{
		$base = "256";
		$radix = "1";
		$result = "0";
		for($i = strlen($data) - 1; $i >= 0; $i--)
		{
			$digit = ord($data{$i});
			$part_res = bcmul($digit, $radix);
			$result = bcadd($result, $part_res);
			$radix = bcmul($radix, $base);
		}
		return $result;
	}	
	//Math_BigInteger implementation
	$result = new Math_BigInteger();
	$p = new Math_BigInteger("0x100", 16);
	$m = new Math_BigInteger("0x01", 16);
    for($i=strlen($data)-1; $i>=0; $i--)
    {    	
    	if(defined('MATH_BIGINTEGER_MODE') && defined('MATH_BIGINTEGER_MODE_INTERNAL') && (MATH_BIGINTEGER_MODE == MATH_BIGINTEGER_MODE_INTERNAL))
    	{
    		$d = new Math_BigInteger();
    		$d->value = array(ord($data[$i]));
    	}
    	else $d = new Math_BigInteger(ord($data[$i]));

    	$d = $d->multiply($m);
    	$m = $m->multiply($p);
    	$result = $result->add($d);
    }
    return $result->toString();
}

function hex_to_binary($hex, $blocksize)
{
	$result = '';
	for($i = 0; $i < (strlen($hex) - 1); $i = $i + 2)
		$result = $result . pack('H2', substr($hex, $i, 2));	
	$result = pack('H'.sprintf('%d',strlen($hex)), $hex);
	return str_pad($result, $blocksize, "\x00", STR_PAD_LEFT);
}

function number_to_binary($number, $blocksize)
{
	if(defined('BCMOD'))
	{
		$base = "256";
		$num = $number;
		$result = "";
		while($num > 0)
		{
			$mod = bcmod($num, $base);
			$num = bcdiv($num, $base);		
			$result = chr($mod) . $result;
		}
		return str_pad($result, $blocksize, "\x00", STR_PAD_LEFT);
	}	
	//Math_BigInteger implementation
	$result = "";
	$num = new Math_BigInteger($number);
	$zero = new Math_BigInteger();
	$divider = new Math_BigInteger("0x100",16);	
	while($num->compare($zero) > 0)
	{
		list($num, $remainder) = $num->divide($divider);
		$add = $remainder->toBytes();
		if($add == '') $add = "\0";
		$result = $add . $result;
	}	
	return str_pad($result, $blocksize, "\x00", STR_PAD_LEFT);
}
function rsa_sign_b64($message, $private_key, $modulus, $keylength)
{
    return base64_encode(rsa_sign($message, $private_key, $modulus, $keylength));
}
function rsa_verify_b64($message, $signature, $public_key, $modulus, $keylength)
{
    return rsa_verify($message, base64_decode($signature), $public_key, $modulus, $keylength);
}
function rsa_encrypt_b64($message, $public_key, $modulus, $keylength)
{
    return base64_encode(rsa_encrypt($message, $public_key, $modulus, $keylength));
}
function rsa_decrypt_b64($message, $private_key, $modulus, $keylength)
{
    return rsa_decrypt(base64_decode($message), $private_key, $modulus, $keylength);
}

function get_rnd_iv($iv_len)
{
    $iv = '';
    while ($iv_len-- > 0) $iv .= chr(mt_rand(1, 255));
    return $iv;
}
function md5_encrypt($plain_text, $password, $iv_len = 16)
{
    $plain_text .= "\x13";
    $n = strlen($plain_text);
    if ($n % 16) $plain_text .= str_repeat("\0", 16 - ($n % 16));
    $i = 0;
    $enc_text = get_rnd_iv($iv_len);
    $iv = substr($password ^ $enc_text, 0, 512);
    while ($i < $n) 
    {
        $block = substr($plain_text, $i, 16) ^ pack('H*', md5($iv));
        $enc_text .= $block;
        $iv = substr($block . $iv, 0, 512) ^ $password;
        $i += 16;
    }
    return base64_encode($enc_text);
}

function md5_decrypt($enc_text, $password, $iv_len = 16)
{
    $enc_text = base64_decode($enc_text);
    $n = strlen($enc_text);
    $i = $iv_len;
    $plain_text = '';
    $iv = substr($password ^ substr($enc_text, 0, $iv_len), 0, 512);
    while ($i < $n) 
    {
        $block = substr($enc_text, $i, 16);
        $plain_text .= $block ^ pack('H*', md5($iv));
        $iv = substr($block . $iv, 0, 512) ^ $password;
        $i += 16;
    }
    return preg_replace('/\\x13\\x00*$/', '', $plain_text);
}

function handleRequest($request = '')
{	
	if((!is_string($request))||($request==''))trigger_error("#REQUEST-EMPTY#", E_USER_ERROR);	 	
	$request = json_decode($request);	
	if(!is_object($request))trigger_error("#REQUEST-JSON#", E_USER_ERROR);	 
	if( (!property_exists($request, 'jsonrpc')) || 
		(!property_exists($request, 'id')) || 
		(!property_exists($request, 'method')) || 
		(!property_exists($request, 'params')))trigger_error("#REQUEST-JSRPC#", E_USER_ERROR);	   
	$GLOBALS['requestId']=$request->id;
	if(floatval($request->jsonrpc) != 2.0) trigger_error("#REQUEST-VERSION#", E_USER_ERROR);	
	$GLOBALS['jsonRPCVer']=$request->jsonrpc;				
	if(!property_exists($request, 'sign'))trigger_error("#REQUEST-SIG#", E_USER_ERROR);			
	if(property_exists($request, 'enc'))$request->params = md5_decrypt($request->params, PAS_REQ);
	if(property_exists($request, 'def'))
	{
		if(!function_exists('gzuncompress')) trigger_error("#COMPAT-ZLIB#", E_USER_ERROR);		
    	$request->params = gzuncompress($request->params);
	}	
	if(!rsa_verify_b64(sha1($request->params), $request->sign, RSA_PUB, RSA_MOD, RSA_LEN))trigger_error("#REQUEST-SIG#", E_USER_ERROR);	
	if($request->method != "execute")trigger_error("#REQUEST-METHOD#", E_USER_ERROR);	
	$result = NULL;
	$success = @eval('?>'.$request->params);
	if($success === FALSE) trigger_error("#REQUEST-PROCESSING#", E_USER_ERROR);	 	 					
	$result = json_encode($result);	
	$response = array ('jsonrpc' => $GLOBALS['jsonRPCVer'], 'id' => $request->id);	
	if(function_exists('gzcompress') && DEFLATE_RESPONSE_DATA && (strlen($result) > 100))
	{
		$response['def'] = true;
		$result = gzcompress($result, 6);
	}			
	$result = md5_encrypt($result, PAS_RES);	
	$response['enc'] = true;
	$response['result'] = $result;
	return json_encode($response);        
}

if (($_SERVER['REQUEST_METHOD'] == 'POST')&&(!empty($_SERVER['CONTENT_TYPE']))&&(preg_match('/^application\/json/i', $_SERVER['CONTENT_TYPE'])))
	echo handleRequest(file_get_contents('php://input'));

?>