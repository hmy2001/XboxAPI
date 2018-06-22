<?php

declare(strict_types=1);

class XboxAPI{
	private $curl = null, $isLogined = false;

	public function __construct(){
		$this->curl = new CURLWrapper();
	}

	private function findStr(string $haystack, string $needle, string $finishNeedle = "", int $start = 0): string{
		$firstPos = strpos($haystack, $needle, $start) + strlen($needle);
		$endPos = strpos($haystack, $finishNeedle, $firstPos);

		return substr($haystack, $firstPos, $endPos - $firstPos);
	}

	public function login(string $mailAddress, string $password): bool{
		$result = $this->curl->getURL("https://login.live.com/login.srf?wa=wsignin1.0&rpsnv=13&ct=".time()."&rver=6.5.6509.0&wp=MBI_SSL&wreply=https:%2F%2Faccount.xbox.com:443%2Fpassport%2FsetCookies.ashx%3Frru%3Dhttps%253a%252f%252faccount.xbox.com%252fja-JP%252fAccount%252fSignin&lc=1041&id=292543&cbcxt=0&lw=1&cobrandid=90011&fl=email");

		if(isset($result["body"])){
			$body = $result["body"];

			$postURL = $this->findStr($body, "urlPost:'", "',");
			$PPFT = $this->findStr($body, "name=\"PPFT\" id=\"i0327\" value=\"", "\"");
			$PPSX = $this->findStr($body, "name=\"PPSX\" data-bind=\"value: svr.bl\" value=\"", "\"");

			$data = [
				"13" => "0",
				"login" => $mailAddress,
				"loginfmt" => $mailAddress,
				"type" => "11",
				"LoginOptions" => "3",
				"passwd" => $password,
				"ps" => "2",
				"PPFT" => $PPFT,
				"PPSX" => $PPSX,
				"NewUser" => "1",
				"fspost" => "0",
				"i21" => "0",
				"CookieDisclosure" => "0",
				"IsFidoSupported" => "1",
				"i2" => "1",
				"i17" => "0",
				"i18" => "__ConvergedLoginPaginatedStrings|1,__ConvergedLogin_PCore|1,",
				"i19" => "5297",
			];
			$result = $this->curl->postURL($postURL, $data);

			if(isset($result["body"])){
				$body = $result["body"];

				if(substr($body, 0, 22) === "<html><head><noscript>"){
					$postURL = $this->findStr($body, "id=\"fmHF\" action=\"", "\"");
					$pprid = $this->findStr($body, "id=\"pprid\" value=\"", "\"");
					$NAP = $this->findStr($body, "id=\"NAP\" value=\"", "\"");
					$ANON = $this->findStr($body, "id=\"ANON\" value=\"", "\"");
					$t = $this->findStr($body, "id=\"t\" value=\"", "\"");

					$data = [
						"pprid" => $pprid,
						"NAP" => $NAP,
						"ANON" => $ANON,
						"t" => $t,
					];
					$result = $this->curl->postURL($postURL, $data);

					if(isset($result["header"])){
						$this->isLogined = true;

						return true;
					}
				}
			}
		}

		return false;
	}

	//TODO: add function
	
}

class CURLWrapper{
	private $cookieFile = null, $cookieFileName = "";

	public function __construct(){
		$this->cookieFile = tmpfile();

		$meta = stream_get_meta_data($this->cookieFile);
		$this->cookieFileName = $meta["uri"];
	}

	public function __destruct(){
		fclose($this->cookieFile);
	}

	public function getURL(string $url): array{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFileName);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFileName);

		$response = curl_exec($ch);

		if(curl_errno($ch) !== CURLE_OK){
			$errorText = curl_error($ch);
			curl_close($ch);

			return ["error" => $errorText];
		}else{
			$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$header = explode("\r\n", substr($response, 0, $header_size));
			$body = substr($response, $header_size);

			curl_close($ch);

			return ["header" => $header, "body" => $body];
		}
	}

	public function postURL(string $url, array $postData): array{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFileName);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFileName);

		$response = curl_exec($ch);

		if(curl_errno($ch) !== CURLE_OK){
			$errorText = curl_error($ch);
			curl_close($ch);

			return ["error" => $errorText];
		}else{
			$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$header = explode("\r\n", substr($response, 0, $header_size));
			$body = substr($response, $header_size);

			curl_close($ch);

			return ["header" => $header, "body" => $body];
		}
	}

}