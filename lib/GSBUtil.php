<?php
namespace TSIPBlocker;

if ( ! defined( 'ABSPATH' ) ) exit;
 
class GSBUtil {
    public static function parseUrl($url) {
		$strict = '/^(?:([^:\/?#]+):)?(?:\/\/\/?((?:(([^:@]*):?([^:@]*))?@)?([^:\/?#]*)(?::(\d*))?))?(((?:\/(\w:))?((?:[^?#\/]*\/)*)([^?#]*))(?:\?([^#]*))?(?:#(.*))?)/';
		$loose = '/^(?:(?![^:@]+:[^:@\/]*@)([^:\/?#.]+):)?(?:\/\/\/?)?((?:(([^:@]*):?([^:@]*))?@)?([^:\/?#]*)(?::(\d*))?)(((?:\/(\w:))?(\/(?:[^?#](?![^?#\/]*\.[^?#\/.]+(?:[?#]|$)))*\/?)?([^?#\/]*))(?:\?([^#]*))?(?:#(.*))?)/';
		preg_match($loose, $url, $match);
		if(empty($match)){
			//As odd as its sounds, we'll fall back to strict (as technically its more correct and so may salvage completely mangled urls)
			unset($match);
			preg_match($strict, $url, $match);
		}

		$parts = array("source"=>'',"scheme"=>'',"authority"=>'',"userinfo"=>'',"user"=>'',"password"=>'',"host"=>'',"port"=>'',"relative"=>'',"path"=>'',"drive"=>'',"directory"=>'',"file"=>'',"query"=>'',"fragment"=>'');
        switch (count ($match)) {  
            case 15: $parts['fragment'] = $match[14];
            case 14: $parts['query'] = $match[13];
            case 13: $parts['file'] =  $match[12];
            case 12: $parts['directory'] =  $match[11];
            case 11: $parts['drive'] =  $match[10];
            case 10: $parts['path'] =  $match[9];
            case 9: $parts['relative'] =  $match[8];
            case 8: $parts['port'] =  $match[7];
            case 7: $parts['host'] =  $match[6];
            case 6: $parts['password'] =  $match[5];
            case 5: $parts['user'] =  $match[4];
            case 4: $parts['userinfo'] =  $match[3];
            case 3: $parts['authority'] =  $match[2];
            case 2: $parts['scheme'] =  $match[1];
            case 1: $parts['source'] =  $match[0];
		}
	    return $parts;
	}

    // https://developers.google.com/safe-browsing/v4/urls-hashing#suffixprefix-expressions
    public static function canonicalizezUrl($url) {
		//Remove line feeds, return carriages, tabs, vertical tabs
		$finalurl = trim(str_replace(array("\x09","\x0A","\x0D","\x0B"),'',$url));
		//URL Encode for easy extraction
		$finalurl = GSBUtil::flexURLEncode($finalurl,true);
		//Now extract hostname & path
		$parts = GSBUtil::parseUrl($finalurl);
		$hostname = $parts['host'];
		$path = $parts['path'];
		$query = $parts['query'];
		$lasthost = "";
		$lastpath = "";
		$lastquery = "";
		//Remove all hex coding (loops max of 50 times to stop craziness but should never
		//reach that)
		for ($i = 0; $i < 50; $i++) {
		    $hostname = rawurldecode($hostname);
		    $path = rawurldecode($path);
		    $query = rawurldecode($query);
		    if($hostname==$lasthost&&$path==$lastpath&&$query==$lastquery)
			    break;
		    $lasthost = $hostname;
		    $lastpath = $path;
		    $lastquery = $query;
		}
		//Deal with hostname first
		//Replace all leading and trailing dots
		$hostname = trim($hostname,'.');
		//Replace all consecutive dots with one dot
		$hostname = preg_replace("/\.{2,}/",".",$hostname);
		//Make it lowercase
		$hostname = strtolower($hostname);
		//See if its a valid IP
		$hostnameip = GSBUtil::isValidIP($hostname);
		if($hostnameip){
			$usingip = true;
			$usehost = $hostnameip;
		}else{
			$usingip = false;
			$usehost = $hostname;
		}
		//The developer guide has lowercasing and validating IP other way round but its more efficient to
		//have it this way
		//Now we move onto canonicalizing the path
		$pathparts = explode('/',$path);
		foreach($pathparts as $key=>$value){
			if($value==".."){
                if($key!=0){
                    unset($pathparts[$key-1]);
                    unset($pathparts[$key]);
                }else{						
                    unset($pathparts[$key]);
                }
			}else if($value=="."||empty($value)){
				unset($pathparts[$key]);
			}
		}
		if(substr($path,-1,1)=="/"){
            $append = "/";
        }else{
            $append = false;
        }

		$path = "/".implode("/",$pathparts);
		if($append&&substr($path,-1,1)!="/"){
            $path .= $append;
        }
			
		$usehost = GSBUtil::flexURLEncode($usehost);
		$path = GSBUtil::flexURLEncode($path);
		$query = GSBUtil::flexURLEncode($query);
		if(empty($parts['scheme'])) {
            $parts['scheme'] = 'http';
        }
			
		$canurl = $parts['scheme'].'://';
		$realurl = $canurl;
		if(!empty($parts['userinfo'])){
            $realurl .= $parts['userinfo'].'@';
        }
			
		$canurl .= $usehost;
		$realurl .= $usehost;
		if(!empty($parts['port'])){
			$canurl .= ':'.$parts['port'];
			$realurl .= ':'.$parts['port'];
		}
		$canurl .= $path;
		$realurl .= $path;
		if(substr_count($finalurl,"?")>0){
			$canurl .= '?'.$parts['query'];
			$realurl .= '?'.$parts['query'];
		}
		if(!empty($parts['fragment'])){
            $realurl .= '#'.$parts['fragment'];
        }
			
		return array("GSBURL"=>$canurl,"CleanURL"=>$realurl,"Parts"=>array("Host"=>$usehost,"Path"=>$path,"Query"=>$query,"IP"=>$usingip));
        // return $canurl;
	}

    function flexURLEncode($url,$ignorehash=false){
		//Had to write another layer as built in PHP urlencode() escapes all non alpha-numeric
		//google states to only urlencode if its below 32 or above or equal to 127 (some of those
		//are non alpha-numeric and so urlencode on its own won't work).
		$urlchars = preg_split('//', $url, -1, PREG_SPLIT_NO_EMPTY);
		if(count($urlchars)>0){
			foreach($urlchars as $key=>$value){				
				$ascii = ord($value);
				if($ascii<=32||$ascii>=127||($value=='#'&&!$ignorehash)||$value=='%'){
                    $urlchars[$key] = rawurlencode($value);
                }					
			}
			return implode('',$urlchars);
		}else{
            return $url;
        }			
	}

    public static function sha256($data){
		return hash('sha256',$data);
	}

    public static function isValidIP($ip)
    {
        //First do a simple check, if it passes this no more needs to be done	
        if (GSBUtil::is_ip($ip)){
            return $ip;
        }            

        //Its a toughy... eerm perhaps its all in hex?
        $checkhex = GSBUtil::hexIPtoIP($ip);
        if ($checkhex){
            return $checkhex;
        }

        //If we're still here it wasn't hex... maybe a DWORD format?
        $checkdword = GSBUtil::hexIPtoIP(dechex($ip));
        if ($checkdword){
            return $checkdword;
        }

        //Nope... maybe in octal or a combination of standard, octal and hex?!
        $ipcomponents = explode('.', $ip);
        $ipcomponents[0] = GSBUtil::hexoct2dec($ipcomponents[0]);
        if (count($ipcomponents) == 2) {
            //The writers of the RFC docs certainly didn't think about the clients! This could be a DWORD mixed with an IP part
            if ($ipcomponents[0] <= 255 && is_int($ipcomponents[0]) && is_int($ipcomponents[1])) {
                $threeparts = dechex($ipcomponents[1]);
                $hexplode = preg_split('//', $threeparts, -1, PREG_SPLIT_NO_EMPTY);
                if (count($hexplode) > 4) {
                    $newip = $ipcomponents[0] . '.' . GSBUtil::iphexdec($hexplode[0] . $hexplode[1]) . '.' . GSBUtil::iphexdec($hexplode[2] . $hexplode[3]) . '.' . GSBUtil::iphexdec($hexplode[4] . $hexplode[5]);
                    //Now check if its valid
                    if (GSBUtil::is_ip($newip)){
                        return $newip;
                    }
                }
            }
        }
        $ipcomponents[1] = $this->hexoct2dec($ipcomponents[1]);
        if (count($ipcomponents) == 3) {
            //Guess what... it could also be a DWORD mixed with two IP parts!
            if (($ipcomponents[0] <= 255 && is_int($ipcomponents[0])) && ($ipcomponents[1] <= 255 && is_int($ipcomponents[1])) && is_int($ipcomponents[2])) {
                $twoparts = dechex($ipcomponents[2]);
                $hexplode = preg_split('//', $twoparts, -1, PREG_SPLIT_NO_EMPTY);
                if (count($hexplode) > 3) {
                    $newip = $ipcomponents[0] . '.' . $ipcomponents[1] . '.' . $this->iphexdec($hexplode[0] . $hexplode[1]) . '.' . $this->iphexdec($hexplode[2] . $hexplode[3]);
                    //Now check if its valid
                    if ($this->is_ip($newip)){
                        return $newip;
                    }
                        
                }
            }
        }
        //If not it may be a combination of hex and octal
        if (count($ipcomponents) >= 4) {
            $tmpcomponents = array($ipcomponents[2], $ipcomponents[3]);

            foreach ($tmpcomponents as $key => $value) {
                if (!$tmpcomponents[$key] = $this->hexoct2dec($value)) {
                    return false;
                }
            }

            array_unshift($tmpcomponents, $ipcomponents[0], $ipcomponents[1]);
            //Convert back to IP form
            $newip = implode('.', $tmpcomponents);

            //Now check if its valid
            if ($this->is_ip($newip)) {
                return $newip;
            }
        }

        //Well its not an IP that we can recognise... theres only so much we can do!
        return false;
    }

    public static function testUrlCanonicalization() {
        $cases = array(
            "http://host/%25%32%35" => "http://host/%25",
            "http://host/%25%32%35%25%32%35" => "http://host/%25%25",
            "http://host/%2525252525252525" => "http://host/%25",
            "http://host/asdf%25%32%35asd" => "http://host/asdf%25asd",
            "http://host/%%%25%32%35asd%%" => "http://host/%25%25%25asd%25%25",
            "http://www.google.com/" => "http://www.google.com/",
            "http://%31%36%38%2e%31%38%38%2e%39%39%2e%32%36/%2E%73%65%63%75%72%65/%77%77%77%2E%65%62%61%79%2E%63%6F%6D/" => "http://168.188.99.26/.secure/www.ebay.com/",
            "http://195.127.0.11/uploads/%20%20%20%20/.verify/.eBaysecure=updateuserdataxplimnbqmn-xplmvalidateinfoswqpcmlx=hgplmcx/" => "http://195.127.0.11/uploads/%20%20%20%20/.verify/.eBaysecure=updateuserdataxplimnbqmn-xplmvalidateinfoswqpcmlx=hgplmcx/",
            "http://host%23.com/%257Ea%2521b%2540c%2523d%2524e%25f%255E00%252611%252A22%252833%252944_55%252B" => 'http://host%23.com/~a!b@c%23d$e%25f^00&11*22(33)44_55+',
            "http://3279880203/blah" => "http://195.127.0.11/blah",
            "http://www.google.com/blah/.." => "http://www.google.com/",
            "www.google.com/" => "http://www.google.com/",
            "www.google.com" => "http://www.google.com/",
            "http://www.evil.com/blah#frag" => "http://www.evil.com/blah",
            "http://www.GOOgle.com/" => "http://www.google.com/",
            "http://www.google.com.../" => "http://www.google.com/",
            "http://www.google.com/foo\tbar\rbaz\n2" => "http://www.google.com/foobarbaz2",
            "http://www.google.com/q?" => "http://www.google.com/q?",
            "http://www.google.com/q?r?" => "http://www.google.com/q?r?",
            "http://www.google.com/q?r?s" => "http://www.google.com/q?r?s",
            "http://evil.com/foo#bar#baz" => "http://evil.com/foo",
            "http://evil.com/foo;" => "http://evil.com/foo;",
            "http://evil.com/foo?bar;" => "http://evil.com/foo?bar;",
            "http://\x01\x80.com/" => "http://%01%80.com/",
            "http://notrailingslash.com" => "http://notrailingslash.com/",
            "http://www.gotaport.com:1234/" => "http://www.gotaport.com:1234/",
            "  http://www.google.com/  " => "http://www.google.com/",
            "http:// leadingspace.com/" => "http://%20leadingspace.com/",
            "http://%20leadingspace.com/" => "http://%20leadingspace.com/",
            "%20leadingspace.com/" => "http://%20leadingspace.com/",
            "https://www.securesite.com/" => "https://www.securesite.com/",
            "http://host.com/ab%23cd" => "http://host.com/ab%23cd",
            "http://host.com//twoslashes?more//slashes" => "http://host.com/twoslashes?more//slashes"
		);

        foreach($cases as $input=>$expected ) {
            if(GBSClient::canonicalizezUrl($input) != $expected){
                error_log("Failing test ". $input ." -> ". $expected);
            }
        }

        error_log("Done");
    }
}