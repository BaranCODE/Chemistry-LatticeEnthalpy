<?php
/**************************************
* Smart Lattice Enthalpy Calculator
* By BaranCODE
***************************************/

$appID = 'WOLFRAM-ALPHA-APP-ID';

include './wa_wrapper/WolframAlphaEngine.php';
include './data.php';
include './functions.php';
include './reader.php';
include './queryThread.php';

$engine = new WolframAlphaEngine($appID);

// TODO: ADD POPUP DEFINITIONS
?>

<html>
<head>
  <meta charset="UTF-8">
  <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Open+Sans">
  <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Lato:400,900">
  <link rel="stylesheet" type="text/css" href="./style.css">
</head>
<body>

<div style="position: absolute; right: 20px;">
<a href="https://github.com/BaranCODE/Chemistry-LatticeEnthalpy" style="text-decoration: none; color: black;"><img src="./images/github.png" alt="" style="float: left; margin-top: 15px; margin-right: 5px;">
<h4>GitHub</h4></a>
</div>

<br>
<div class="title">
<h1>Lattice&nbsp;&nbsp;Enthalpy&nbsp;&nbsp;Calculator</h1><h3>By&nbsp;&nbsp;BaranCODE</h3>
</div>
<div class="subtitle">
<h4>An interactive web application that uses the Born-Haber cycle<br> and Hess's Law to calculate and teach students about lattice enthalpies.</h4>
</div>
<br><br>

<div class="container">

<form method='POST' action='#'>
Enter a solid ionic compound:&nbsp;&nbsp;
<input type="text" name="q" value="
<?php
$queryIsSet = isset($_REQUEST['q']);
if ($queryIsSet) echo $_REQUEST['q'];
?>
">&nbsp;&nbsp; <input type="submit" name="Search" value="Search">
</form>
<br>
<img src="./images/bornhaber.png" alt="">

<?php
if (!$queryIsSet) die();

$compound_raw = $_REQUEST['q'];

$thread1 = new QueryThread();
$thread1->engine = $engine;
$thread1->input = $_REQUEST['q'];
$thread1->type = "compound";
$thread1->start();

$thread2 = new QueryThread();
$thread2->engine = $engine;
$thread2->input = $_REQUEST['q'];
$thread2->type = "oxidation state";
$thread2->start();

$thread1->join();
$response1 = $thread1->response;
$thread2->join();
$response2 = $thread2->response;
?>

<br><hr>
<h1>Data</h1>

<div style="width: 100%;">
	<div style="float: left; width: 50%;">
		<?php
		readResponse("compound", $response1);
		echo "<br>Consists of the ions:<ul>";
		$ion_margin_coefficient = 1;
		readResponse("oxidation state", $response2);
		echo "</ul>";
		?>
	</div>
	<div style="float: left; width: 50%;">
		<?php
		$atom_threads = array();
		foreach ($atoms as $atom){
			$thread = new QueryThread();
			$thread->engine = $engine;
			$thread->input = $atom;
			$thread->type = "atom";
			$thread->start();
			$atom_threads[$atom] = $thread;
		}
		foreach ($atoms as $atom){
			$thread = $atom_threads[$atom];
			$atom_current = $atom;
			$thread->join();
			$response = $thread->response;
			readResponse("atom", $response);
			echo "<br><br>";
		}
		?>
	</div>
	<br style="clear: left;"/>
</div>
<br><hr>
<h1>Reactions</h1>
<h4>
<div style="width: 100%;">
	<div style="float: left; width: 50%;">
		<?php
		echo $reaction_formation . "<br>";
		echo $reaction_atomization_cation . "<br>";
		echo $reaction_atomization_anion . "<br>";
		echo $reaction_ionization . "<br>";
		echo $reaction_affinity . "<br><hr align='left' width='50%' style='border-width: 2px;'>";
		echo $reaction_lattice;
		?>
	</div>
	<div style="float: left; width: 50%;">
		<?php
		echo "&#916;H<sub>f</sub> = " . $enthalpy_formation;
		echo parseAndMultiply($enthalpy_formation) . "<br>";
		echo "&#916;H<sub>a</sub> = " . $enthalpy_atomization_cation;
		echo parseAndMultiply($enthalpy_atomization_cation) . " kJ/mol<br>";
		echo "&#916;H<sub>a</sub> = " . $enthalpy_atomization_anion;
		echo parseAndMultiply($enthalpy_atomization_anion) . " kJ/mol<br>";
		echo "&#916;H<sub>IE</sub> = " . $enthalpy_ionization;
		echo parseAndMultiply($enthalpy_ionization) . " kJ/mol<br>";
		echo "&#916;H<sub>EA</sub> = " . $enthalpy_affinity;
		echo parseAndMultiply($enthalpy_affinity) . " kJ/mol<br><hr align='left' width='50%' style='border-width: 2px;'>";
		
		$enthalpy = multiplyAndAdd($enthalpy_formation, $enthalpy_atomization_cation, $enthalpy_atomization_anion, $enthalpy_ionization, $enthalpy_affinity);
		echo "&#916;H<sub>LATTICE</sub> = ";
		?>
		<form id="answerForm">
		<input type="text" id="answer" value="">
		<input type="submit" value="Check Answer">
		<div id="response"></div>
		</form>
		<script>
		document.getElementById('answerForm').onsubmit = function() {
			if (document.getElementById("answer").value == <?php echo $enthalpy; ?>)
				document.getElementById("response").innerHTML = "<span style='color: #1e8449;'>Correct!</span>";
			else
				document.getElementById("response").innerHTML = "<span style='color: #900C3F;'>Try again, or <a href='#' onclick='showAnswer();' style='color: #900C3F;'>view answer</a></span>";
			return false;
		}
		function showAnswer(){
			document.getElementById("response").innerHTML = "<div style='border: 1px solid #8a8a8a; background-color: #bababa; margin-top: 15px; padding: 5px;'>&#916;H<sub>LATTICE</sub> = <?php echo explode(" ", $enthalpy_formation)[0] . " + " . $enthalpy_atomization_cation . " + " . $enthalpy_atomization_anion . " + " . $enthalpy_ionization . " + " . $enthalpy_affinity . " = " . $enthalpy; ?> kJ/mol</div>";
		}
		</script>
	</div>
	<br style="clear: left;"/>
</div>
</h4>

</div>
</body>
</html>
