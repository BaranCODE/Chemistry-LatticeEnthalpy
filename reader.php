<?php
// Parses the plaintext response from Wolfram Alpha
function readResponse($type, $response){
	foreach ( $response->getPods() as $pod ) {
		$title = $pod->attributes['title'];
		foreach ($pod->getSubpods() as $subpod ) {
			$lines = $subpod->plaintext;
			if (strpos($lines, "\n") !== FALSE)
				$lines = explode("\n", $lines);
			else
				$lines = array($lines);
			foreach ($lines as $line){
				if (strpos($line, " | ") !== FALSE)
					$line = explode(" | ", $line);
				scan($type, $title, $line);
			}
		}
	}
}
?>