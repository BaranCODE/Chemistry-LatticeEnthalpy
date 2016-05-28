<?php
class QueryThread extends Thread {
    public $engine;
	public $input;
	public $type;
	public $response;
	
    public function run() {
		if ($this->type == "atom") $this->response = $this->engine->getResults($this->input . " element", array("podstate" => "Chemical:ElementData__More"));
		else if ($this->type == "compound") $this->response = $this->engine->getResults($this->input, array("podstate" => "Thermodynamics:ChemicalData__More"));
		else if ($this->type == "oxidation state") $this->response = $this->engine->getResults($this->input . " oxidation state");
		if ( $this->response->isError() ) $this->response = null;
		if ( count($response->getPods()) == 0) $this->response = null;
    }
}
?>