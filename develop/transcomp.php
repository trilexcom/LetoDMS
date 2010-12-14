<?php
/* Determine all languages keys used in the php files */
$output = array();
if(exec('sgrep -o "%r\n" \'"tMLText(\"" __ "\""\' */*.php|sort|uniq -c', &$output)) {
	$allkeys = array();
	foreach($output as $line) {
		$data = explode(' ', trim($line));
		$allkeys[$data[1]] = $data[0];
	}
}

/* Reading languages */
foreach(array('English', 'German', 'Italian', 'Slovak', 'Czech') as $lang) {
	include('languages/'.$lang.'/lang.inc');
	ksort($text);
	$langarr[$lang] = $text;
}

/* Check for missing phrases in each language */
echo "List of missing keys\n";
echo "-----------------------------\n";
foreach(array_keys($allkeys) as $key) {
	foreach(array('English', 'German', 'Italian', 'Slovak', 'Czech') as $lang) {
		if(!isset($langarr[$lang][$key])) {
			echo "Missing key '".$key."' in language ".$lang."\n";
		}
	}
}
echo "\n";

/* Check for phrases not used anymore */
echo "List of superflous keys\n";
echo "-----------------------------\n";
foreach(array('English', 'German', 'Italian', 'Slovak', 'Czech') as $lang) {
	$n = 0;
	foreach($langarr[$lang] as $key=>$value) {
		if(!isset($allkeys[$key])) {
			echo "Unused key '".$key."' in language ".$lang."\n";
			$n++;
		}
	}
	echo $n." superflous keys found\n";
}

exit;

$fpout = fopen('php://stdout', 'w');
foreach(array_keys($langarr['English']) as $key) {
	$data = array($key, $langarr['English'][$key], $langarr['German'][$key]);
	fputcsv($fpout, $data);
}
?>
