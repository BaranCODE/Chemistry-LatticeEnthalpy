<?php
$compound; // the chemical formula of the compound, WITHOUT subscripts
$compound_raw; // the chemical formula of the compound, with subscripts, as entered by user
$atoms = array();
$atom_current = "";
$ions = array(); // first element is cation (positive), second anion (negative)
$ion_quantity; // atom symbol is key, number of that atom in compound is value
$reaction_formation; $enthalpy_formation;
$reaction_affinity; $enthalpy_affinity;
$reaction_ionization; $enthalpy_ionization;
$reaction_atomization_cation; $enthalpy_atomization_cation;
$reaction_atomization_anion; $enthalpy_atomization_anion;
$reaction_lattice;

function scan($type, $title, $data){
	if ($type == "compound") scanCompound($title, $data);
	else if ($type == "oxidation state") scanIons($title, $data);
	else if ($type == "atom") scanAtom($title, $data);
}

function scanCompound($title, $data){
	global $compound;
	global $enthalpy_formation;
	global $reaction_formation;
	global $reaction_lattice;
	global $formations;
	global $compound_raw;
	switch ($title){
		case "Input interpretation":
			$data = ucwords($data);
			// EXCEPTIONS; HANDLE MANUALLY
			if (strpos($data, "CaF2") !== FALSE){
				$data = "Calcium Fluoride"; // wolfram alpha doesn't know its name or formula; setting compound formula and formation enthalpy here because they won't execute below
				echo "<b><ins>" . $data . "</ins></b><br>"; // printing here because needs to be before formation
				$compound = "CaF";
				$reaction_formation = "CaF<sub>2</sub> (s)";
				$reaction_lattice = "CaF<sub>2</sub> (s)";
				$formation = "-1219.6 kJ/mol";
				echo "Enthalpy of formation: " . $formation . "<br>";
				$enthalpy_formation = str_replace("-", "", $formation);
				break;
			} else if ($compound_raw == "CsF"){
				$data = "Caesium Fluoride"; // wolfram alpha doesn't know its name or formula; setting compound formula and formation enthalpy here because they won't execute below
				echo "<b><ins>" . $data . "</ins></b><br>"; // printing here because needs to be before formation
				$compound = "CsF";
				$reaction_formation = "CsF (s)";
				$reaction_lattice = "CsF (s)";
				$formation = "-555 kJ/mol";
				echo "Enthalpy of formation: " . $formation . "<br>";
				$enthalpy_formation = str_replace("-", "", $formation);
				break;
			}
			
			echo "<b><ins>" . $data . "</ins></b><br>";
			
			if ($compound_raw == "CsBr"){ // we have to handle its formation here, because it won't execute below, but it should come after its name is printed
				$formation = "-395 kJ/mol";
				echo "Enthalpy of formation: " . $formation . "<br>";
				$enthalpy_formation = str_replace("-", "", $formation);
			}
			break;
		case "Chemical names and formulas":
			if ($data[0] == "formula"){
				if ($compound_raw == "CsF") break; // we already handled it
				
				$formula = str_replace("_", "", $data[1]);
				$formula = preg_replace("/(\d)/", "<sub>$1</sub>", $formula);
				$reaction_formation = $formula . " (s)";
				$reaction_lattice = $formula . " (s)";
				$compound = preg_replace("/\W.*\z/", "", $formula);
			}
			break;
		case "Thermodynamic properties":
			if (strpos($data[0], "molar heat of formation") !== FALSE){
				$formation = $data[2];
				if (isset($formations[$compound_raw])){
					$formation = $formations[$compound_raw] . " kJ/mol";
				}
				echo "Enthalpy of formation: " . $formation . "<br>";
				$enthalpy_formation = str_replace("-", "", $formation);
			}
			break;
	}
}

function scanIons($title, $data){
	global $ion_margin_coefficient;
	global $ions;
	global $atoms;
	global $compound_raw;
	global $ion_quantity;
	switch ($title){
		case "Result":
			// EXCEPTIONS; HANDLE MANUALLY
			if ($compound_raw == "AgBr"){ // wolfram alpha doesn't know oxidation states for AgBr, doing it manually
				$atoms[] = "Ag"; $atoms[] = "Br";
				$ions[0] = "Ag<sup>+</sup>";
				$ions[1] = "Br<sup>-</sup>";
				$ion_quantity["Ag"] = 1;
				$ion_quantity["Br"] = 1;
				echo "<li style='margin: 0px; margin-top: -10px;'>1 Ag<sup>+</sup></li>";
				echo "<li style='margin: 0px; margin-top: 5px;'>1 Br<sup>-</sup></li>";
				break;
			}
			if ($compound_raw == "CsF"){ // wolfram alpha doesn't know oxidation states for CsF, doing it manually
				$atoms[] = "Cs"; $atoms[] = "F";
				$ions[0] = "Cs<sup>+</sup>";
				$ions[1] = "F<sup>-</sup>";
				$ion_quantity["Cs"] = 1;
				$ion_quantity["F"] = 1;
				echo "<li style='margin: 0px; margin-top: -10px;'>1 Cs<sup>+</sup></li>";
				echo "<li style='margin: 0px; margin-top: 5px;'>1 F<sup>-</sup></li>";
				break;
			}
			
			$charge = strrev(str_replace("1", "", $data[1])); // remove 1 from charge, so '-1' becomes '-'; reverse string, so '-2' becomes '2-'
			$atom = explode(" ", $data[0])[0];
			$ion = $atom . "<sup>" . $charge . "</sup>";
			if (strpos($ion, "+") !== FALSE) $ions[0] = $ion;
			else $ions[1] = $ion;
			$atoms[] = $atom;
			
			$buffer = "";
			$detected = false;
			foreach (str_split($compound_raw) as $char){
				if (ctype_upper($char)) $buffer = "";
				if ($detected){
					if (is_numeric($char)) $ion_quantity[$atom] = $char; // save quantity of the ion, if explicitly written in formula as subscript
					$detected = false;
					$buffer = "";
				}
				$buffer .= $char;
				if ($buffer == $atom) $detected = true;
			}
			if (isset($ion_quantity[$atom]) == FALSE) $ion_quantity[$atom] = 1; // handle default case; no subscript
			
			echo "<li style='margin: 0px; margin-top: " . ($ion_margin_coefficient * -15 + 5) . "px;'>" . $ion_quantity[$atom] . " " . $ion . "</li>";
			$ion_margin_coefficient = 0;
			break;
	}
}

function scanAtom($title, $data){
	global $atom_current;
	global $diatomic;
	global $reaction_formation;
	global $ion_quantity;
	global $ions;
	global $reaction_affinity; global $enthalpy_affinity;
	global $reaction_ionization; global $enthalpy_ionization;
	global $EA;
	global $atomizations;
	global $reaction_atomization_cation; global $enthalpy_atomization_cation;
	global $reaction_atomization_anion; global $enthalpy_atomization_anion;
	global $reaction_lattice;
	switch ($title){
		case "Input interpretation":
			echo "<b><ins>" . ucwords(explode(" ", $data)[0]) . "</ins></b><br>";
			break;
		case "Thermodynamic properties":
			if (strpos($data[0], "phase") !== FALSE){
				$atom = $atom_current;
				$coefficientFrac = getAtomCoefficientFraction($atom);
				$atom = checkDiatomic($atom);
				echo "Standard state: " . $atom . " (" . $data[1] . ")<br>";
				$atom = $coefficientFrac . " " . $atom . " (" . $data[1][0] . ")";
				if (strpos($reaction_formation, "-->") == FALSE) // check if any products had been written in the reaction
					$reaction_formation = $reaction_formation . " --> " . $atom;
				else
					$reaction_formation = $reaction_formation . " + " . $atom;
				
				
				$atomization = $atomizations[$atom_current];
				echo "Atomization enthalpy: " . $atomization . " kJ/mol<br>"; // THE ENTHALPY HERE IS PER ATOM
				$coefficient = getAtomCoefficient($atom_current);
				if ($coefficient == 1) $coefficient = "";
				else $atomization = $atomization . " x " . $coefficient;
				
				if (preg_replace("/\W.*\z/", "", $ions[0]) == $atom_current){ // check if the atom is the cation (positive)
					$enthalpy_atomization_cation = $atomization;
					$reaction_atomization_cation = $atom . " --> " . $coefficient . " " . $atom_current . " (g)";
				} else {
					$enthalpy_atomization_anion = $atomization;
					$reaction_atomization_anion = $atom . " --> " . $coefficient . " " . $atom_current . " (g)";
				}
			}
			break;
		case "Reactivity":
			if (preg_replace("/\W.*\z/", "", $ions[0]) == $atom_current){ // check if the atom is the cation (positive)
				if (strpos($data[0], "ionization energies") !== FALSE){
					$charge = preg_replace(array("|.+?(?=<sup>)<sup>|", "|</sup>|"), "", $ions[0]);
					if ($charge[0] == "+") $charge = 1;
					else $charge = (int)$charge[0];
					for ($i = 1; $i <= $charge; $i++){
						echo addOrdinalNumberSuffix($i) . " ionization energy: " . $data[$i] . "<br>";
						$enthalpy_ionization += (int)$data[$i];
					}
					$coefficient = getAtomCoefficient($atom_current);
					if ($coefficient == 1) $coefficient = "";
					else $enthalpy_ionization = $enthalpy_ionization . " x " . $coefficient;
					$electrons = $charge; if ($coefficient !== "") $electrons = $coefficient * $electrons;
					$reaction_ionization = $coefficient . " " . $atom_current . " (g) --> " . $coefficient . " " . $ions[0] . " (g) + " . $electrons . "e";
					
					// write the lattice enthalpy reaction
					if (strpos($reaction_lattice, "-->") == FALSE) // check if any products had been written in the reaction
						$reaction_lattice = $reaction_lattice . " --> " . $coefficient . " " . $ions[0] . " (g)";
					else
						$reaction_lattice = $reaction_lattice . " + " . $coefficient . " " . $ions[0] . " (g)";
				}
			} else { // it is the anion (negative)
				if (strpos($data[0], "affinity") !== FALSE){
					if ($atom_current == "O" || $atom_current == "S") $enthalpy_affinity = $EA[$atom_current];
					else $enthalpy_affinity = "-" . explode(" ", $data[1])[0];
					echo "Electron affinity: " . $enthalpy_affinity . " kJ/mol";
					$charge = preg_replace(array("|.+?(?=<sup>)<sup>|", "|</sup>|"), "", $ions[1]);
					if ($charge[0] == "-") $charge = 1;
					else $charge = (int)$charge[0];
									////////////// WHAT ABOUT SECOND ELECTRON AFFINITY, FOR ELEMENTS OTHER THAN O AND S??? /////////////////
					$coefficient = getAtomCoefficient($atom_current);
					if ($coefficient == 1) $coefficient = "";
					else $enthalpy_affinity = $enthalpy_affinity . " x " . $coefficient;
					$electrons = $charge; if ($coefficient !== "") $electrons = $coefficient * $electrons;
					$reaction_affinity = $coefficient . " " . $atom_current . " (g) + " . $electrons . "e --> " . $coefficient . " " . $ions[1] . " (g)";
					
					// write the lattice enthalpy reaction
					if (strpos($reaction_lattice, "-->") == FALSE) // check if any products had been written in the reaction
						$reaction_lattice = $reaction_lattice . " --> " . $coefficient . " " . $ions[1] . " (g)";
					else
						$reaction_lattice = $reaction_lattice . " + " . $coefficient . " " . $ions[1] . " (g)";
				}
			}
			break;
	}
}

// gets the coefficient of the atom; doesn't check for diatomics
function getAtomCoefficient($atom){
	global $ion_quantity;
	$coefficient = $ion_quantity[$atom];
	return $coefficient;
}

// gets the coefficient of the atom, as a fraction (string); checks for diatomics
function getAtomCoefficientFraction($atom){
	global $diatomic;
	$coefficient = getAtomCoefficient($atom);
	if (in_array($atom, $diatomic)) $coefficient = $coefficient / 2;
	if ($coefficient == 1) $coefficient = "";
	else if (is_float($coefficient)){
		$components = convertDecimalToFraction($coefficient);
		$coefficient = $components[0] . "/" . $components[1];
	}
	return $coefficient;
}

function checkDiatomic($atom){
	global $diatomic;
	if (in_array($atom, $diatomic)){
		$atom = $atom . "<sub>2</sub>";
	}
	return $atom;
}

// used to parse and if necessary, multiply any reaction enthalpies with their coefficients
function parseAndMultiply($enthalpy){
	if (strpos($enthalpy, " x ")){
		$parts = explode(" x ", $enthalpy);
		$enthalpy = (float)$parts[0] * (float)$parts[1];
		return " = " . $enthalpy;
	} else return "";
}

// first parses and multiplies, like above function, but without the "looks". Then adds.
// Used for calculating sum of enthalpies, for Hess's Law
function multiplyAndAdd(){
	$args = func_get_args();
	$enthalpy = 0;
	foreach ($args as $arg){
		$arg = str_replace(" kJ/mol", "", $arg);
		if (strpos($arg, " x ")){
			$parts = explode(" x ", $arg);
			$arg = (float)$parts[0] * (float)$parts[1];
		}
		$enthalpy += (float)$arg;
	}
	return $enthalpy;
}



// By Frederik Krautwald
// Taken from http://stackoverflow.com/questions/1954018/php-convert-decimal-into-fraction-and-back
function convertDecimalToFraction($decimal){
    $bottom = 1;
    while (fmod($decimal, 1) != 0.0) {
        $decimal *= 2;
        $bottom *= 2;
    }
    return [
        (int)sprintf('%.0f', $decimal),
        (int)sprintf('%.0f', $bottom),
    ];
}

// Taken from http://www.if-not-true-then-false.com/2010/php-1st-2nd-3rd-4th-5th-6th-php-add-ordinal-number-suffix/
function addOrdinalNumberSuffix($num) {
	if (!in_array(($num % 100),array(11,12,13))){
	  switch ($num % 10) {
		// Handle 1st, 2nd, 3rd
		case 1:  return $num.'st';
		case 2:  return $num.'nd';
		case 3:  return $num.'rd';
	  }
	}
	return $num.'th';
}
?>