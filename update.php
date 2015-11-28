<?php
new SteamTracker(Count($argv) === 2 ? $argv[1] : '');

class SteamTracker {
	private $AppStart;
	private $CurrentTime;
	private $UseCache = true;
	private $Requests = [];
	private $URLsToFetch = [];
	private $Options = Array(
	CURLOPT_USERAGENT => 'SuitedButler',
	CURLOPT_ENCODING => 'gzip',
	CURLOPT_HEADER => 1,
	CURLOPT_AUTOREFERER => 0,
	CURLOPT_RETURNTRANSFER => 1,
	CURLOPT_FOLLOWLOCATION => 0,
	CURLOPT_TIMEOUT => 30,
	CURLOPT_CONNECTTIMEOUT => 10,
	CURLOPT_COOKIESESSION => 1,
	CURLOPT_BINARYTRANSFER => 1,
	CURLOPT_FORBID_REUSE => 1,
	CURLOPT_FRESH_CONNECT => 1
);

public function __construct($Option)
{
	$this->AppStart = MicroTime(true);
	if ($Option === 'force') {
		$this->UseCache = false;
	}

	$UrlsPath = __DIR__ . '/urls.json';
	if (!File_Exists($UrlsPath)) {
		$this->Log('{lightred}Missing ' . $UrlsPath);
		Exit;
	}

	$this->CurrentTime = Time();
	$Data = File_Get_Contents($UrlsPath);
	// Strip comments

	$Data = Preg_Replace('#^([\s]?//.*)#m', '', $Data);
	echo $Data;
	$Data = JSON_Decode($Data, true);
	foreach($Data as $File => $URL) {
		$this->URLsToFetch[] = Array(
		'URL' => $URL,
		'File' => $File
	);
}

unset($Data, $URL, $File);
$Tries = 5;
do {
	$URLs = $this->URLsToFetch;
	$this->Log('{yellow}' . Count($URLs) . ' urls to be fetched...');
	$this->URLsToFetch = Array();
	$this->Fetch($URLs, $Tries);
}

while (!Empty($this->URLsToFetch) && $Tries-- > 0);
$this->Log('{lightblue}Done');
}

private function GenerateURL($URL) {
	return Str_Replace(Array(
	'__TIME__'
	) , Array(
	'_=' . $this->CurrentTime
	) , $URL);
}

private function HandleResponse($File, $Data) {
	if (SubStr($File, -4) === '.css' || SubStr($File, -3) === '.js') {
		$Data = preg_replace('/[&\?]v=[a-zA-Z0-9\.\-\_]{3,}/', '?v=valveisgoodatcaching', $Data);
	}

	$File = __DIR__ . '/' . $File;
	$Folder = dirname($File);
	if (!is_dir($Folder)) {
		$this->Log('{lightblue}Creating ' . $Folder);
		mkdir($Folder, 0755, true);
	}

	if (File_Exists($File) && StrCmp(File_Get_Contents($File) , $Data) === 0) {
		return false;
	}

	File_Put_Contents($File, $Data);
	return true;
}

private function Fetch($URLs, $Tries) {
	$this->Requests = Array();
	$Master = cURL_Multi_Init();
	$WindowSize = 10;
	if ($WindowSize > Count($URLs)) {
		$WindowSize = Count($URLs);
	}

	for ($i = 0; $i < $WindowSize; $i++) {
		$URL = Array_Shift($URLs);
		$this->CreateHandle($Master, $URL);
	}

	unset($URL, $WindowSize, $i);
	do {
		while (($Exec = cURL_Multi_Exec($Master, $Running)) === CURLM_CALL_MULTI_PERFORM);
		if ($Exec !== CURLM_OK) {
			break;
		}

		while ($Done = cURL_Multi_Info_Read($Master)) {
			$Slave = $Done['handle'];
			$URL = cURL_GetInfo($Slave, CURLINFO_EFFECTIVE_URL);
			$Code = cURL_GetInfo($Slave, CURLINFO_HTTP_CODE);
			$Data = cURL_Multi_GetContent($Slave);
			$Request = $this->Requests[(int)$Slave];
			$HeaderSize = cURL_GetInfo($Slave, CURLINFO_HEADER_SIZE);
			$Header = SubStr($Data, 0, $HeaderSize);
			$Data = SubStr($Data, $HeaderSize);
			if (isset($Done['error'])) {
				$this->Log('{yellow}cURL Error: {yellow}' . $Done['error'] . '{normal} - ' . $URL);
				$this->URLsToFetch[] = Array(
				'URL' => $URL,
				'File' => $Request
			);
		}
		else
		if ($Code === 304) {
			$this->Log('{yellow}Not Modified{normal} - ' . $URL);
		}
		else
		if ($Code !== 200) {
			$this->Log('{yellow}HTTP Error ' . $Code . '{normal} - ' . $URL);
			if ($Code !== 404) {
				$this->URLsToFetch[] = Array(
				'URL' => $URL,
				'File' => $Request
			);
		}
	}
	else {
		$LengthExpected = cURL_GetInfo($Slave, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
		$LengthDownload = cURL_GetInfo($Slave, CURLINFO_SIZE_DOWNLOAD);
		if ($LengthExpected !== $LengthDownload) {
			$this->Log('{lightred}Wrong Length {normal}(' . $LengthDownload . ' != ' . $LengthExpected . '){normal} - ' . $URL);
			$this->URLsToFetch[] = Array(
			'URL' => $URL,
			'File' => $Request
		);
	}
	else {
		if (Preg_Match('/^ETag: (.+)$/m', $Header, $Test) === 1) {
			$this->ETags[$Request] = Trim($Test[1]);
		}

		if ($this->HandleResponse($Request, $Data) === true) {
			$this->Log('{green}Fetched{normal} - ' . $URL);
		}
		else {
			$this->Log('{green}Not Modified{normal} - ' . $URL);
		}
	}
}

if (Count($URLs)) {
	$URL = Array_Shift($URLs);
	$this->CreateHandle($Master, $URL);
}

cURL_Multi_Remove_Handle($Master, $Slave);
cURL_Close($Slave);
unset($Request, $Slave);
}

if ($Running) {
	cURL_Multi_Select($Master, 5);
}
}

while ($Running);
cURL_Multi_Close($Master);
}

private function CreateHandle($Master, $URL) {
	$Slave = cURL_Init();
	$File = $URL['File'];
	$Options = $this->Options;
	$Options[CURLOPT_URL] = $this->GenerateURL($URL['URL']);
	$this->Requests[(int)$Slave] = $File;
	if ($this->UseCache) {
		if (File_Exists($File)) {
			$Options[CURLOPT_HTTPHEADER] = Array(
			'If-Modified-Since: ' . GMDate('D, d M Y H:i:s \G\M\T', FileMTime($File))
		);
	}
}

cURL_SetOpt_Array($Slave, $Options);
cURL_Multi_Add_Handle($Master, $Slave);
return $Slave;
}

private function Log($String) {
	$Log = '[';
	$Log.= Number_Format(MicroTime(true) - $this->AppStart, 2);
	$Log.= 's] ';
	$Log.= $String;
	$Log.= '{normal}';
	$Log.= PHP_EOL;
	$Log = Str_Replace(Array(
	'{normal}',
	'{green}',
	'{yellow}',
	'{lightred}',
	'{lightblue}'
	) , Array(
	"\033[0m",
	"\033[0;32m",
	"\033[1;33m",
	"\033[1;31m",
	"\033[1;34m"
	) , $Log);
	echo $Log;
}
}
